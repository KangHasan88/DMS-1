<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\DeliveryTimeSlot;
use Illuminate\Http\Request;

class DeliveryTimeSlotController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;

        $timeSlots = DeliveryTimeSlot::with('companyBranch')
            ->when($branchScopeId, fn ($query) => $query->forCompanyBranch($branchScopeId))
            ->when($canFilterBranches && $request->filled('company_branch_id'), fn ($query) => $query->forCompanyBranch((int) $request->company_branch_id))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->search($request->search)
            ->orderByRaw('company_branch_id is not null')
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->paginate($request->get('per_page', 10))
            ->withQueryString();

        $companyBranches = $this->availableCompanyBranches();

        return view('delivery-time-slots.index', compact('timeSlots', 'companyBranches', 'canFilterBranches'));
    }

    public function create()
    {
        $companyBranches = $this->availableCompanyBranches();
        $branchLocked = (bool) $this->currentBranchScopeId();

        return view('delivery-time-slots.create', compact('companyBranches', 'branchLocked'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_branch_id' => 'nullable|exists:company_branches,id',
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'period_label' => 'nullable|string|max:40',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['company_branch_id'] = $this->resolveCompanyBranchId($validated['company_branch_id'] ?? null);
        $validated['is_active'] = true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        DeliveryTimeSlot::create($validated);

        return redirect()->route('delivery-time-slots.index')
            ->with('success', 'Slot waktu pengiriman berhasil ditambahkan');
    }

    public function edit(DeliveryTimeSlot $deliveryTimeSlot)
    {
        $this->authorizeBranchTimeSlot($deliveryTimeSlot);

        $companyBranches = $this->availableCompanyBranches($deliveryTimeSlot->company_branch_id);
        $branchLocked = (bool) $this->currentBranchScopeId();

        return view('delivery-time-slots.edit', compact('deliveryTimeSlot', 'companyBranches', 'branchLocked'));
    }

    public function update(Request $request, DeliveryTimeSlot $deliveryTimeSlot)
    {
        $this->authorizeBranchTimeSlot($deliveryTimeSlot);

        $validated = $request->validate([
            'company_branch_id' => 'nullable|exists:company_branches,id',
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'period_label' => 'nullable|string|max:40',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['company_branch_id'] = $this->resolveCompanyBranchId($validated['company_branch_id'] ?? $deliveryTimeSlot->company_branch_id);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $deliveryTimeSlot->update($validated);

        return redirect()->route('delivery-time-slots.index')
            ->with('success', 'Slot waktu pengiriman berhasil diperbarui');
    }

    private function currentBranchScopeId(): ?int
    {
        return auth()->user()?->scopedCompanyBranchId();
    }

    private function availableCompanyBranches(?int $currentBranchId = null)
    {
        $query = CompanyProfile::defaultProfile()
            ->branches()
            ->where(function ($query) use ($currentBranchId) {
                $query->where('is_active', true);

                if ($currentBranchId) {
                    $query->orWhere('id', $currentBranchId);
                }
            });

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

    private function authorizeBranchTimeSlot(DeliveryTimeSlot $timeSlot): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && $timeSlot->company_branch_id && (int) $timeSlot->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }
}
