<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierPayment::with(['supplier', 'paidBy'])
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

        $payments = $query->paginate($request->get('per_page', 10))->withQueryString();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('supplier-payments.index', compact('payments', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ap_invoice_id' => 'required|exists:ap_invoices,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', array_keys(SupplierPayment::METHOD_LIST)),
            'amount' => 'required|integer|min:1',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $invoice = ApInvoice::with(['supplier'])
            ->forUserBranch()
            ->findOrFail($validated['ap_invoice_id']);

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
            'paidBy',
            'allocations.apInvoice.purchaseOrder',
        ]);

        return view('supplier-payments.show', compact('supplierPayment'));
    }

    private function authorizeBranchAccess(SupplierPayment $payment): void
    {
        $branchScopeId = Auth::user()?->scopedCompanyBranchId();

        abort_if(
            $branchScopeId && (int) $payment->company_branch_id !== (int) $branchScopeId,
            403
        );
    }
}
