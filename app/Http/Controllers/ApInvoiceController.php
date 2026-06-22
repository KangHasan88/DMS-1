<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ApInvoice::with(['purchaseOrder', 'supplier', 'issuedBy'])
            ->latest('invoice_date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('purchaseOrder', fn ($po) => $po->where('po_number', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->paginate($request->get('per_page', 10))->withQueryString();
        $statuses = ApInvoice::STATUS_LIST;
        $suppliers = Supplier::active()->orderBy('name')->get();

        $invoiceablePurchaseOrders = PurchaseOrder::query()
            ->with(['supplier'])
            ->whereDoesntHave('apInvoice')
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->latest()
            ->limit(8)
            ->get()
            ->filter(fn (PurchaseOrder $purchaseOrder) => $purchaseOrder->isInvoiceableForAp());

        return view('ap-invoices.index', compact('invoices', 'statuses', 'suppliers', 'invoiceablePurchaseOrders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
        ]);

        $purchaseOrder = PurchaseOrder::with(['apInvoice', 'items.product', 'supplier'])
            ->findOrFail($validated['purchase_order_id']);

        try {
            $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('ap-invoices.show', $invoice)
            ->with('success', 'AP Invoice berhasil diterbitkan.');
    }

    public function show(ApInvoice $apInvoice)
    {
        $apInvoice->load([
            'items.product',
            'purchaseOrder.items',
            'supplier',
            'issuedBy',
            'paymentAllocations.supplierPayment.paidBy',
        ]);

        return view('ap-invoices.show', compact('apInvoice'));
    }
}
