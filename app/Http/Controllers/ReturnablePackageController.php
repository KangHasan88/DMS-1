<?php

namespace App\Http\Controllers;

use App\Models\CompanyBranch;
use App\Models\Customer;
use App\Models\ReturnablePackage;
use App\Models\ReturnablePackageBalance;
use App\Models\ReturnablePackageMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnablePackageController extends Controller
{
    public function index(Request $request)
    {
        $packages = ReturnablePackage::query()
            ->withCount('movements')
            ->orderBy('name')
            ->get();

        $balances = ReturnablePackageBalance::query()
            ->with(['package', 'customer', 'companyBranch'])
            ->where('outstanding_quantity', '>', 0)
            ->when($branchScopeId = $this->currentBranchScopeId(), fn ($query) => $query->where('company_branch_id', $branchScopeId))
            ->latest('last_movement_at')
            ->limit(20)
            ->get();

        $movements = ReturnablePackageMovement::query()
            ->with(['package', 'customer', 'companyBranch', 'creator'])
            ->when($branchScopeId = $this->currentBranchScopeId(), fn ($query) => $query->where('company_branch_id', $branchScopeId))
            ->latest('movement_date')
            ->latest('id')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        $activePackages = ReturnablePackage::active()->orderBy('name')->get();
        $customers = Customer::query()
            ->where('is_active', true)
            ->when($branchScopeId = $this->currentBranchScopeId(), fn ($query) => $query->where('company_branch_id', $branchScopeId))
            ->orderBy('name')
            ->get();
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$this->currentBranchScopeId();
        $categories = ReturnablePackage::CATEGORY_LIST;
        $movementTypes = ReturnablePackageMovement::TYPE_LIST;

        return view('returnable-packages.index', compact(
            'packages',
            'balances',
            'movements',
            'activePackages',
            'customers',
            'companyBranches',
            'canFilterBranches',
            'categories',
            'movementTypes'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:returnable_packages,code'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:' . implode(',', array_keys(ReturnablePackage::CATEGORY_LIST))],
            'unit' => ['required', 'string', 'max:30'],
            'replacement_value' => ['nullable', 'integer', 'min:0'],
            'requires_serial_tracking' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        ReturnablePackage::create([
            ...$validated,
            'replacement_value' => (int) ($validated['replacement_value'] ?? 0),
            'requires_serial_tracking' => (bool) ($validated['requires_serial_tracking'] ?? false),
            'is_active' => true,
        ]);

        return redirect()->route('returnable-packages.index')
            ->with('success', 'Master kemasan berhasil dibuat.');
    }

    public function storeMovement(Request $request)
    {
        $validated = $request->validate([
            'returnable_package_id' => ['required', 'exists:returnable_packages,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
            'movement_type' => ['required', 'in:' . implode(',', array_keys(ReturnablePackageMovement::TYPE_LIST))],
            'movement_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_value' => ['nullable', 'integer', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $validated['company_branch_id'] = $branchScopeId;
        }

        $package = ReturnablePackage::findOrFail($validated['returnable_package_id']);
        $validated['unit_value'] = (int) ($validated['unit_value'] ?? $package->replacement_value);
        $validated['created_by'] = Auth::id();

        try {
            ReturnablePackageMovement::recordMovement($validated);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('returnable-packages.index')
            ->with('success', 'Mutasi kemasan berhasil dicatat.');
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }
}
