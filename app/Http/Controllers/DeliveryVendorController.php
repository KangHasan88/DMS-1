<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\DeliveryVendor;
use Illuminate\Http\Request;

class DeliveryVendorController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;

        $vendors = DeliveryVendor::with('companyBranch')
            ->forCompanyBranch($branchScopeId)
            ->when($canFilterBranches && $request->filled('company_branch_id'), fn ($query) => $query->where('company_branch_id', $request->company_branch_id))
            ->search($request->search)
            ->orderBy('name')
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        $companyBranches = $this->availableCompanyBranches();

        return view('delivery-vendors.index', compact('vendors', 'companyBranches', 'canFilterBranches'));
    }

    public function create()
    {
        $companyBranches = $this->availableCompanyBranches();
        $branchLocked = (bool) $this->currentBranchScopeId();

        return view('delivery-vendors.create', compact('companyBranches', 'branchLocked'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_branch_id' => 'nullable|exists:company_branches,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'vendor_type' => 'required|in:' . implode(',', [
                DeliveryVendor::TYPE_EXPEDITION,
                DeliveryVendor::TYPE_INSTANT,
                DeliveryVendor::TYPE_TRUCKING,
                DeliveryVendor::TYPE_CUSTOM,
            ]),
            'phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'payment_term' => 'required|in:' . implode(',', [
                DeliveryVendor::PAYMENT_TERM_CASH,
                DeliveryVendor::PAYMENT_TERM_INVOICE,
                DeliveryVendor::PAYMENT_TERM_WEEKLY,
                DeliveryVendor::PAYMENT_TERM_MONTHLY,
            ]),
            'notes' => 'nullable|string',
        ]);

        $validated['company_branch_id'] = $this->resolveCompanyBranchId($validated['company_branch_id'] ?? null);
        $validated['code'] = $validated['code'] ? strtoupper($validated['code']) : null;
        $validated['is_active'] = true;

        DeliveryVendor::create($validated);

        return redirect()->route('delivery-vendors.index')
            ->with('success', 'Ekspedisi berhasil ditambahkan');
    }

    public function edit(DeliveryVendor $deliveryVendor)
    {
        $this->authorizeBranchVendor($deliveryVendor);

        $companyBranches = $this->availableCompanyBranches();
        $branchLocked = (bool) $this->currentBranchScopeId();

        return view('delivery-vendors.edit', compact('deliveryVendor', 'companyBranches', 'branchLocked'));
    }

    public function update(Request $request, DeliveryVendor $deliveryVendor)
    {
        $this->authorizeBranchVendor($deliveryVendor);

        $validated = $request->validate([
            'company_branch_id' => 'nullable|exists:company_branches,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30',
            'vendor_type' => 'required|in:' . implode(',', [
                DeliveryVendor::TYPE_EXPEDITION,
                DeliveryVendor::TYPE_INSTANT,
                DeliveryVendor::TYPE_TRUCKING,
                DeliveryVendor::TYPE_CUSTOM,
            ]),
            'phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'payment_term' => 'required|in:' . implode(',', [
                DeliveryVendor::PAYMENT_TERM_CASH,
                DeliveryVendor::PAYMENT_TERM_INVOICE,
                DeliveryVendor::PAYMENT_TERM_WEEKLY,
                DeliveryVendor::PAYMENT_TERM_MONTHLY,
            ]),
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['company_branch_id'] = $this->resolveCompanyBranchId($validated['company_branch_id'] ?? $deliveryVendor->company_branch_id);
        $validated['code'] = $validated['code'] ? strtoupper($validated['code']) : null;
        $validated['is_active'] = $request->boolean('is_active');

        $deliveryVendor->update($validated);

        return redirect()->route('delivery-vendors.index')
            ->with('success', 'Ekspedisi berhasil diperbarui');
    }

    private function currentBranchScopeId(): ?int
    {
        return auth()->user()?->scopedCompanyBranchId();
    }

    private function availableCompanyBranches()
    {
        $query = CompanyProfile::defaultProfile()->activeBranches();

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->whereKey($branchScopeId);
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    private function resolveCompanyBranchId(?int $requestedBranchId): ?int
    {
        if ($branchScopeId = $this->currentBranchScopeId()) {
            return $branchScopeId;
        }

        return $requestedBranchId;
    }

    private function authorizeBranchVendor(DeliveryVendor $vendor): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && $vendor->company_branch_id && (int) $vendor->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }
}
