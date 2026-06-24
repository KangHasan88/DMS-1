<?php

namespace App\Http\Controllers;

use App\Models\ArInvoice;
use App\Models\CompanyBranch;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerPayment::with(['customer', 'customerUser', 'companyBranch', 'receivedBy'])
            ->latest('payment_date')
            ->latest('id');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where('company_branch_id', $branchScopeId);
        } elseif ($request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customerUser', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate($request->get('per_page', 10))->withQueryString();
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;
        $statuses = CustomerPayment::STATUS_LIST;

        return view('customer-payments.index', compact('payments', 'companyBranches', 'branchScopeId', 'canFilterBranches', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ar_invoice_id' => 'required|exists:ar_invoices,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', array_keys(CustomerPayment::METHOD_LIST)),
            'amount' => 'required|integer|min:1',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $invoice = ArInvoice::with(['companyBranch', 'customer', 'customerUser'])
            ->findOrFail($validated['ar_invoice_id']);

        $this->authorizeInvoiceBranch($invoice);

        try {
            $payment = CustomerPayment::receiveForInvoice($invoice, $validated, Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('ar-invoices.show', $invoice->fresh())
            ->with('success', 'Pembayaran ' . $payment->payment_number . ' berhasil dicatat.');
    }

    public function show(CustomerPayment $customerPayment)
    {
        $this->authorizePaymentBranch($customerPayment);

        $customerPayment->load([
            'customer',
            'customerUser',
            'companyBranch',
            'receivedBy',
            'voidedBy',
            'allocations.arInvoice.order',
        ]);

        return view('customer-payments.show', compact('customerPayment'));
    }

    public function void(Request $request, CustomerPayment $customerPayment)
    {
        $this->authorizePaymentBranch($customerPayment);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $customerPayment->voidPayment($validated['void_reason'], Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $allocation = $customerPayment->allocations()->with('arInvoice')->latest('id')->first();
        $route = $allocation?->arInvoice
            ? route('ar-invoices.show', $allocation->arInvoice)
            : route('customer-payments.show', $customerPayment->fresh());

        return redirect($route)
            ->with('success', 'Pembayaran customer berhasil di-void dan jurnal reversal berhasil diposting.');
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function authorizeInvoiceBranch(ArInvoice $invoice): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $invoice->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }

    private function authorizePaymentBranch(CustomerPayment $payment): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $payment->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }
}
