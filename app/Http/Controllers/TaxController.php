<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\ArInvoice;
use App\Models\CompanyBranch;
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

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }
}
