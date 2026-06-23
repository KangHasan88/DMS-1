<?php

namespace App\Http\Controllers;

use App\Models\CompanyBranch;
use App\Models\Customer;
use App\Models\ReturnablePackage;
use App\Models\ReturnablePackageBalance;
use App\Models\ReturnablePackageCategory;
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
        $categories = ReturnablePackageCategory::orderBy('sort_order')->orderBy('name')->get();
        $activeCategories = $categories->where('is_active', true)->values();
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
            'activeCategories',
            'movementTypes'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:returnable_packages,code'],
            'name' => ['required', 'string', 'max:150'],
            'returnable_package_category_id' => ['required', 'exists:returnable_package_categories,id'],
            'unit' => ['required', 'string', 'max:30'],
            'replacement_value' => ['nullable', 'integer', 'min:0'],
            'requires_serial_tracking' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], $this->validationMessages(), $this->validationAttributes());

        $category = ReturnablePackageCategory::findOrFail($validated['returnable_package_category_id']);

        ReturnablePackage::create([
            ...$validated,
            'category' => $category->code,
            'replacement_value' => (int) ($validated['replacement_value'] ?? 0),
            'requires_serial_tracking' => (bool) ($validated['requires_serial_tracking'] ?? false),
            'is_active' => true,
        ]);

        return redirect()->route('returnable-packages.index')
            ->with('success', 'Master kemasan berhasil dibuat.');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'category_code' => ['nullable', 'string', 'max:40', 'unique:returnable_package_categories,code'],
            'category_name' => ['required', 'string', 'max:100', 'unique:returnable_package_categories,name'],
        ], $this->validationMessages(), $this->validationAttributes());

        $code = $validated['category_code'] ?: str($validated['category_name'])->slug('_')->toString();
        $baseCode = $code;
        $suffix = 2;

        while (ReturnablePackageCategory::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $suffix++;
        }

        ReturnablePackageCategory::create([
            'code' => $code,
            'name' => $validated['category_name'],
            'is_active' => true,
            'sort_order' => (int) ReturnablePackageCategory::max('sort_order') + 1,
        ]);

        return redirect()->route('returnable-packages.index')
            ->with('success', 'Kategori kemasan berhasil ditambahkan.');
    }

    public function toggleCategory(ReturnablePackageCategory $category)
    {
        $category->update([
            'is_active' => !$category->is_active,
        ]);

        $message = $category->is_active
            ? 'Kategori kemasan berhasil diaktifkan.'
            : 'Kategori kemasan berhasil dinonaktifkan.';

        return redirect()->route('returnable-packages.index')
            ->with('success', $message);
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
        ], $this->validationMessages(), $this->validationAttributes());

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

    private function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'exists' => ':attribute tidak valid.',
            'unique' => ':attribute sudah digunakan.',
            'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute harus berupa angka bulat.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'boolean' => ':attribute harus bernilai ya atau tidak.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max karakter.',
            'in' => ':attribute tidak valid.',
        ];
    }

    private function validationAttributes(): array
    {
        return [
            'code' => 'kode',
            'name' => 'nama kemasan',
            'returnable_package_category_id' => 'kategori kemasan',
            'unit' => 'satuan',
            'replacement_value' => 'nilai pengganti',
            'requires_serial_tracking' => 'tracking nomor seri',
            'description' => 'catatan',
            'category_code' => 'kode kategori',
            'category_name' => 'nama kategori',
            'returnable_package_id' => 'kemasan',
            'customer_id' => 'customer',
            'company_branch_id' => 'cabang',
            'movement_type' => 'tipe mutasi',
            'movement_date' => 'tanggal',
            'quantity' => 'qty',
            'unit_value' => 'nilai per unit',
            'reference_number' => 'nomor referensi',
            'notes' => 'catatan',
        ];
    }
}
