<?php

namespace App\Http\Controllers;

use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\CustomerSalesAssignment;
use App\Models\SalesTerritory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesCoverageController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $companyProfile = CompanyProfile::defaultProfile();
        $companyBranches = CompanyBranch::query()
            ->where('company_profile_id', $companyProfile->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $selectedBranchId = $branchScopeId ?: ($request->filled('company_branch_id') ? (int) $request->company_branch_id : null);

        $assignments = CustomerSalesAssignment::query()
            ->with('customer.companyBranch', 'salesperson.companyBranch', 'salesTerritory', 'companyBranch')
            ->active()
            ->when($selectedBranchId, fn ($query) => $query->where('company_branch_id', $selectedBranchId))
            ->when($request->filled('salesperson_id'), fn ($query) => $query->where('salesperson_id', $request->salesperson_id))
            ->when($request->filled('sales_territory_id'), fn ($query) => $query->where('sales_territory_id', $request->sales_territory_id))
            ->orderByDesc('start_date')
            ->paginate($request->get('per_page', 10));

        $assignmentTerritoryIds = $assignments->getCollection()
            ->pluck('sales_territory_id')
            ->filter()
            ->unique()
            ->values();
        $requestedTerritoryId = $request->filled('sales_territory_id') ? (int) $request->sales_territory_id : null;

        $territories = SalesTerritory::query()
            ->withCount(['activeCustomerAssignments as active_customers_count'])
            ->when($selectedBranchId, fn ($query) => $query->where('company_branch_id', $selectedBranchId))
            ->where(function ($query) use ($assignmentTerritoryIds, $requestedTerritoryId) {
                $query->active();

                if ($assignmentTerritoryIds->isNotEmpty()) {
                    $query->orWhereIn('id', $assignmentTerritoryIds);
                }

                if ($requestedTerritoryId) {
                    $query->orWhere('id', $requestedTerritoryId);
                }
            })
            ->orderBy('company_branch_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $activeTerritories = $territories->where('is_active', true)->values();

        $customers = Customer::query()
            ->with('companyBranch', 'activeSalesAssignment')
            ->active()
            ->when($selectedBranchId, fn ($query) => $query->where('company_branch_id', $selectedBranchId))
            ->orderBy('name')
            ->get();

        $salespeople = User::query()
            ->with('companyBranch')
            ->active()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['sales', 'telesales']))
            ->when($selectedBranchId, function ($query) use ($selectedBranchId) {
                $query->where(function ($userQuery) use ($selectedBranchId) {
                    $userQuery->whereNull('company_branch_id')
                        ->orWhere('company_branch_id', $selectedBranchId);
                });
            })
            ->orderBy('name')
            ->get();

        return view('sales-coverage.index', compact(
            'assignments',
            'territories',
            'activeTerritories',
            'customers',
            'salespeople',
            'companyBranches',
            'branchScopeId',
            'selectedBranchId'
        ));
    }

    public function storeTerritory(Request $request)
    {
        $validated = $request->validate([
            'company_branch_id' => ['required', 'exists:company_branches,id'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('sales_territories', 'code')
                    ->where('company_branch_id', $request->company_branch_id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->ensureBranchIsAllowed((int) $validated['company_branch_id']);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        SalesTerritory::create($validated);

        return redirect()->route('sales-coverage.index', ['company_branch_id' => $validated['company_branch_id']])
            ->with('success', 'Area sales berhasil ditambahkan');
    }

    public function assignCustomer(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'salesperson_id' => ['required', 'exists:users,id'],
            'sales_territory_id' => ['nullable', Rule::exists('sales_territories', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date'],
            'assignment_type' => ['required', 'in:permanent,temporary'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $customer = Customer::with('companyBranch')->findOrFail($validated['customer_id']);
            $salesperson = User::findOrFail($validated['salesperson_id']);
            $territory = isset($validated['sales_territory_id'])
                ? SalesTerritory::find($validated['sales_territory_id'])
                : null;

            $this->ensureBranchIsAllowed((int) $customer->company_branch_id);
            $this->ensureSalespersonMatchesBranch($salesperson, (int) $customer->company_branch_id);

            if ($territory && (int) $territory->company_branch_id !== (int) $customer->company_branch_id) {
                abort(422, 'Area sales harus sesuai cabang customer.');
            }

            CustomerSalesAssignment::query()
                ->where('customer_id', $customer->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'end_date' => now()->subDay()->toDateString(),
                ]);

            CustomerSalesAssignment::create([
                'customer_id' => $customer->id,
                'salesperson_id' => $salesperson->id,
                'sales_territory_id' => $territory?->id,
                'company_branch_id' => $customer->company_branch_id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['assignment_type'] === CustomerSalesAssignment::TYPE_TEMPORARY
                    ? ($validated['end_date'] ?? null)
                    : null,
                'assignment_type' => $validated['assignment_type'],
                'is_active' => true,
                'notes' => $validated['notes'] ?? null,
                'assigned_by' => Auth::id(),
            ]);
        });

        return redirect()->route('sales-coverage.index')
            ->with('success', 'Assignment customer ke sales berhasil disimpan');
    }

    public function endAssignment(CustomerSalesAssignment $assignment)
    {
        $this->ensureBranchIsAllowed((int) $assignment->company_branch_id);

        $assignment->update([
            'is_active' => false,
            'end_date' => now()->toDateString(),
        ]);

        return redirect()->route('sales-coverage.index')
            ->with('success', 'Assignment sales customer ditutup');
    }

    public function updateAssignment(Request $request, CustomerSalesAssignment $assignment)
    {
        $this->ensureBranchIsAllowed((int) $assignment->company_branch_id);

        $validated = $request->validate([
            'sales_territory_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($assignment) {
                    if ((int) $value === (int) $assignment->sales_territory_id) {
                        return;
                    }

                    if (! SalesTerritory::active()->whereKey($value)->exists()) {
                        $fail('Area sales harus aktif.');
                    }
                },
            ],
            'assignment_type' => ['required', 'in:permanent,temporary'],
            'end_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string'],
        ]);

        $territory = isset($validated['sales_territory_id'])
            ? SalesTerritory::find($validated['sales_territory_id'])
            : null;

        if ($territory && (int) $territory->company_branch_id !== (int) $assignment->company_branch_id) {
            abort(422, 'Area sales harus sesuai cabang assignment.');
        }

        if (
            ! empty($validated['end_date'])
            && $assignment->start_date
            && $validated['end_date'] < $assignment->start_date->toDateString()
        ) {
            return back()
                ->withErrors(['end_date' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.'])
                ->withInput();
        }

        $assignment->update([
            'sales_territory_id' => $territory?->id,
            'assignment_type' => $validated['assignment_type'],
            'end_date' => $validated['assignment_type'] === CustomerSalesAssignment::TYPE_TEMPORARY
                ? ($validated['end_date'] ?? null)
                : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('sales-coverage.index')
            ->with('success', 'Detail assignment berhasil diperbarui');
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function ensureBranchIsAllowed(int $branchId): void
    {
        $branchScopeId = $this->currentBranchScopeId();
        abort_if($branchScopeId && $branchId !== $branchScopeId, 403);
    }

    private function ensureSalespersonMatchesBranch(User $salesperson, int $branchId): void
    {
        if ($salesperson->company_branch_id && (int) $salesperson->company_branch_id !== $branchId) {
            abort(422, 'Sales owner harus sesuai cabang customer.');
        }
    }
}
