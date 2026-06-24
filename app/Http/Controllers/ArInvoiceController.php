<?php

namespace App\Http\Controllers;

use App\Models\ArInvoice;
use App\Models\CompanyBranch;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ArInvoice::with(['order', 'customer', 'customerUser', 'companyBranch', 'issuedBy'])
            ->latest('invoice_date')
            ->latest('id');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where('company_branch_id', $branchScopeId);
        } elseif ($request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customerUser', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate($request->get('per_page', 10))->withQueryString();
        $statuses = ArInvoice::STATUS_LIST;
        $companyBranches = CompanyBranch::where('is_active', true)->orderBy('name')->get();
        $branchScopeId = $this->currentBranchScopeId();
        $canFilterBranches = !$branchScopeId;

        $invoiceableOrders = Order::query()
            ->with(['user.customer', 'companyBranch'])
            ->whereDoesntHave('arInvoice')
            ->where(function ($orderQuery) {
                $orderQuery->where('status', Order::STATUS_DELIVERED)
                    ->orWhereHas('delivery', fn ($delivery) => $delivery->where('status', \App\Models\Delivery::STATUS_COMPLETED));
            })
            ->when($branchScopeId, fn ($orderQuery) => $orderQuery->where('company_branch_id', $branchScopeId))
            ->latest()
            ->limit(8)
            ->get()
            ->filter(fn (Order $order) => $order->isInvoiceableForAr());

        return view('ar-invoices.index', compact(
            'invoices',
            'statuses',
            'companyBranches',
            'branchScopeId',
            'canFilterBranches',
            'invoiceableOrders'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::with(['arInvoice', 'delivery', 'items.product', 'companyBranch', 'user.customer'])
            ->findOrFail($validated['order_id']);

        $this->authorizeBranch($order);

        try {
            $invoice = ArInvoice::issueFromOrder($order, Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('ar-invoices.show', $invoice)
            ->with('success', 'AR Invoice berhasil diterbitkan.');
    }

    public function show(ArInvoice $arInvoice)
    {
        $this->authorizeInvoiceBranch($arInvoice);

        $arInvoice->load([
            'items.product',
            'order.items',
            'customer',
            'customerUser',
            'companyBranch',
            'issuedBy',
            'paymentAllocations.customerPayment.receivedBy',
        ]);

        return view('ar-invoices.show', compact('arInvoice'));
    }

    public function void(Request $request, ArInvoice $arInvoice)
    {
        $this->authorizeInvoiceBranch($arInvoice);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $arInvoice->voidInvoice($validated['void_reason'], Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('ar-invoices.show', $arInvoice->fresh())
            ->with('success', 'AR Invoice berhasil di-void dan jurnal reversal berhasil diposting.');
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function authorizeBranch(Order $order): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $order->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }

    private function authorizeInvoiceBranch(ArInvoice $invoice): void
    {
        if (($branchScopeId = $this->currentBranchScopeId()) && (int) $invoice->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }
}
