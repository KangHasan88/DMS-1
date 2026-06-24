<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\ArInvoice;
use App\Models\CompanyBranch;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxController extends Controller
{
    public function output(Request $request)
    {
        $query = ArInvoice::with(['customer', 'customerUser', 'companyBranch'])
            ->where('status', '!=', ArInvoice::STATUS_VOID)
            ->where(function ($taxQuery) {
                $taxQuery->where('ppn_amount', '>', 0)
                    ->orWhere('tax_status', '!=', ArInvoice::TAX_NOT_REQUIRED);
            })
            ->latest('invoice_date')
            ->latest('id');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where('company_branch_id', $branchScopeId);
        } elseif ($request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($request->filled('tax_status')) {
            $query->where('tax_status', $request->tax_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('tax_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customerUser', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate($request->get('per_page', 10))->withQueryString();
        $statuses = ArInvoice::TAX_STATUS_LIST;
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$this->currentBranchScopeId();
        $summary = [
            'tax_base_amount' => (clone $query)->sum('tax_base_amount'),
            'ppn_amount' => (clone $query)->sum('ppn_amount'),
            'count' => (clone $query)->count(),
        ];

        return view('tax.output', compact('invoices', 'statuses', 'companyBranches', 'canFilterBranches', 'summary'));
    }

    public function updateOutput(Request $request, ArInvoice $arInvoice)
    {
        $this->ensureBranchAccess($arInvoice->company_branch_id);

        if ($arInvoice->status === ArInvoice::STATUS_VOID) {
            return back()->with('error', 'Invoice void tidak bisa diupdate pajaknya.');
        }

        $validated = $request->validate([
            'tax_status' => ['required', Rule::in(array_keys(ArInvoice::TAX_STATUS_LIST))],
            'tax_invoice_number' => ['nullable', 'string', 'max:80'],
            'tax_invoice_date' => ['nullable', 'date'],
            'tax_transaction_code' => ['nullable', 'string', 'max:10'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_error_message' => ['nullable', 'string', 'max:500'],
        ]);

        $validated = array_merge([
            'tax_invoice_number' => null,
            'tax_invoice_date' => null,
            'tax_transaction_code' => null,
            'tax_error_message' => null,
        ], $validated);

        if ($validated['tax_status'] === ArInvoice::TAX_EXPORTED && !$arInvoice->tax_exported_at) {
            $validated['tax_exported_at'] = now();
        }

        if ($validated['tax_status'] === ArInvoice::TAX_APPROVED && !$arInvoice->tax_approved_at) {
            $validated['tax_approved_at'] = now();
        }

        $arInvoice->update($validated);

        return back()->with('success', 'Data pajak keluaran berhasil diperbarui.');
    }

    public function input(Request $request)
    {
        $query = ApInvoice::with(['supplier', 'companyBranch'])
            ->where('status', '!=', ApInvoice::STATUS_VOID)
            ->where(function ($taxQuery) {
                $taxQuery->where('ppn_amount', '>', 0)
                    ->orWhere('tax_status', '!=', ApInvoice::TAX_NOT_RECEIVED);
            })
            ->latest('invoice_date')
            ->latest('id');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where('company_branch_id', $branchScopeId);
        } elseif ($request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($request->filled('tax_status')) {
            $query->where('tax_status', $request->tax_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('supplier_tax_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate($request->get('per_page', 10))->withQueryString();
        $statuses = ApInvoice::TAX_STATUS_LIST;
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $canFilterBranches = !$this->currentBranchScopeId();
        $summary = [
            'tax_base_amount' => (clone $query)->sum('tax_base_amount'),
            'ppn_amount' => (clone $query)->sum('ppn_amount'),
            'count' => (clone $query)->count(),
        ];

        return view('tax.input', compact('invoices', 'statuses', 'companyBranches', 'canFilterBranches', 'summary'));
    }

    public function updateInput(Request $request, ApInvoice $apInvoice)
    {
        $this->ensureBranchAccess($apInvoice->company_branch_id);

        if ($apInvoice->status === ApInvoice::STATUS_VOID) {
            return back()->with('error', 'Invoice void tidak bisa diupdate pajaknya.');
        }

        $validated = $request->validate([
            'tax_status' => ['required', Rule::in(array_keys(ApInvoice::TAX_STATUS_LIST))],
            'supplier_tax_invoice_number' => ['nullable', 'string', 'max:80'],
            'supplier_tax_invoice_date' => ['nullable', 'date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_error_message' => ['nullable', 'string', 'max:500'],
        ]);

        $validated = array_merge([
            'supplier_tax_invoice_number' => null,
            'supplier_tax_invoice_date' => null,
            'tax_error_message' => null,
        ], $validated);

        if ($validated['tax_status'] === ApInvoice::TAX_EXPORTED && !$apInvoice->tax_exported_at) {
            $validated['tax_exported_at'] = now();
        }

        if ($validated['tax_status'] === ApInvoice::TAX_APPROVED && !$apInvoice->tax_approved_at) {
            $validated['tax_approved_at'] = now();
        }

        $apInvoice->update($validated);

        return back()->with('success', 'Data pajak masukan berhasil diperbarui.');
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function ensureBranchAccess(?int $companyBranchId): void
    {
        $branchScopeId = $this->currentBranchScopeId();

        abort_if($branchScopeId && (int) $companyBranchId !== (int) $branchScopeId, 404);
    }
}
