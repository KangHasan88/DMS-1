<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\ChartAccount;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierPayment::with(['supplier', 'paidBy', 'chartAccount'])
            ->forUserBranch()
            ->latest('payment_date')
            ->latest('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate($request->get('per_page', 10))->withQueryString();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $statuses = SupplierPayment::STATUS_LIST;

        return view('supplier-payments.index', compact('payments', 'suppliers', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ap_invoice_id' => 'required|exists:ap_invoices,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', array_keys(SupplierPayment::METHOD_LIST)),
            'chart_account_id' => ['nullable', 'integer', 'exists:chart_accounts,id'],
            'amount' => 'required|integer|min:1',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $invoice = ApInvoice::with(['supplier'])
            ->forUserBranch()
            ->findOrFail($validated['ap_invoice_id']);

        $this->authorizeCashAccount($validated['chart_account_id'] ?? null, $invoice->company_branch_id);

        try {
            $payment = SupplierPayment::payForInvoice($invoice, $validated, Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('ap-invoices.show', $invoice->fresh())
            ->with('success', 'Pembayaran ' . $payment->payment_number . ' berhasil dicatat.');
    }

    public function show(SupplierPayment $supplierPayment)
    {
        $this->authorizeBranchAccess($supplierPayment);

        $supplierPayment->load([
            'supplier',
            'chartAccount',
            'paidBy',
            'voidedBy',
            'allocations.apInvoice.purchaseOrder',
        ]);

        return view('supplier-payments.show', compact('supplierPayment'));
    }

    public function void(Request $request, SupplierPayment $supplierPayment)
    {
        $this->authorizeBranchAccess($supplierPayment);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $supplierPayment->voidPayment($validated['void_reason'], Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $allocation = $supplierPayment->allocations()->with('apInvoice')->latest('id')->first();
        $route = $allocation?->apInvoice
            ? route('ap-invoices.show', $allocation->apInvoice)
            : route('supplier-payments.show', $supplierPayment->fresh());

        return redirect($route)
            ->with('success', 'Pembayaran supplier berhasil di-void dan jurnal reversal berhasil diposting.');
    }

    private function authorizeBranchAccess(SupplierPayment $payment): void
    {
        $branchScopeId = Auth::user()?->scopedCompanyBranchId();

        abort_if(
            $branchScopeId && (int) $payment->company_branch_id !== (int) $branchScopeId,
            403
        );
    }

    private function authorizeCashAccount(?int $accountId, ?int $branchId): void
    {
        if (!$accountId) {
            return;
        }

        $account = ChartAccount::whereKey($accountId)
            ->where('is_active', true)
            ->where('is_cash_account', true)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('company_branch_id')
                    ->when($branchId, fn ($branchQuery) => $branchQuery->orWhere('company_branch_id', $branchId));
            })
            ->first();

        abort_if(!$account, 422, 'Akun kas/bank tidak valid untuk cabang invoice ini.');
    }
}
