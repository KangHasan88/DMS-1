<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\ChartAccount;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = ApInvoice::with(['purchaseOrder', 'supplier', 'issuedBy'])
            ->forUserBranch()
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
            ->with(['supplier', 'items'])
            ->forUserBranch()
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
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'item_prices' => ['nullable', 'array'],
            'item_prices.*' => ['nullable', 'integer', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'supplier_tax_invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_tax_invoice_date' => ['nullable', 'date'],
            'variance_note' => ['nullable', 'string', 'max:500'],
        ]);

        $purchaseOrder = PurchaseOrder::with(['apInvoice', 'items.product', 'supplier'])
            ->forUserBranch()
            ->findOrFail($validated['purchase_order_id']);

        $itemPrices = collect($validated['item_prices'] ?? [])
            ->mapWithKeys(fn ($price, $itemId) => [(int) $itemId => (int) $price])
            ->filter(fn ($price, $itemId) => $purchaseOrder->items->contains('id', $itemId));

        $hasPriceVariance = $purchaseOrder->items->contains(function ($item) use ($itemPrices) {
            return $itemPrices->has($item->id)
                && (int) $itemPrices->get($item->id) !== (int) $item->price;
        });

        if ($hasPriceVariance && blank($validated['variance_note'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['variance_note' => 'Catatan selisih wajib diisi jika harga invoice supplier berbeda dari PO.']);
        }

        $notes = 'Dibuat dari PO ' . $purchaseOrder->po_number;
        if (filled($validated['variance_note'] ?? null)) {
            $notes .= ' | Catatan matching: ' . $validated['variance_note'];
        }

        try {
            $invoice = ApInvoice::issueFromPurchaseOrder($purchaseOrder, Auth::user(), [
                'item_prices' => $itemPrices->all(),
                'tax_rate' => (float) ($validated['tax_rate'] ?? 0),
                'supplier_tax_invoice_number' => $validated['supplier_tax_invoice_number'] ?? null,
                'supplier_tax_invoice_date' => $validated['supplier_tax_invoice_date'] ?? null,
                'variance_note' => $validated['variance_note'] ?? null,
                'notes' => $notes,
            ]);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('ap-invoices.show', $invoice)
            ->with('success', 'AP Invoice berhasil diterbitkan.');
    }

    public function review(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder = PurchaseOrder::with(['apInvoice', 'items.product.unit', 'supplier', 'companyBranch'])
            ->forUserBranch()
            ->findOrFail($purchaseOrder->id);

        try {
            if (!$purchaseOrder->isInvoiceableForAp()) {
                throw new \InvalidArgumentException('PO belum memenuhi syarat untuk dibuat AP Invoice.');
            }
        } catch (\InvalidArgumentException $exception) {
            return redirect()->route('ap-invoices.index')->with('error', $exception->getMessage());
        }

        $receivedSubtotal = $purchaseOrder->items->sum(
            fn ($item) => (int) $item->received_quantity * (int) $item->price
        );
        $orderedSubtotal = (int) $purchaseOrder->total;
        $isMatched = $purchaseOrder->items->every(
            fn ($item) => (int) $item->received_quantity === (int) $item->quantity
        );

        return view('ap-invoices.review', compact(
            'purchaseOrder',
            'receivedSubtotal',
            'orderedSubtotal',
            'isMatched'
        ));
    }

    public function show(ApInvoice $apInvoice)
    {
        $this->authorizeBranchAccess($apInvoice);

        $apInvoice->load([
            'items.product',
            'items.purchaseOrderItem',
            'purchaseOrder.items',
            'supplier',
            'issuedBy',
            'paymentAllocations.supplierPayment.chartAccount',
            'paymentAllocations.supplierPayment.paidBy',
            'debitNotes.postedBy',
            'debitNotes.voidedBy',
        ]);

        $cashAccounts = $this->cashAccountsForBranch($apInvoice->company_branch_id);

        return view('ap-invoices.show', compact('apInvoice', 'cashAccounts'));
    }

    public function void(Request $request, ApInvoice $apInvoice)
    {
        $this->authorizeBranchAccess($apInvoice);

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $apInvoice->voidInvoice($validated['void_reason'], Auth::user());
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('ap-invoices.show', $apInvoice->fresh())
            ->with('success', 'AP Invoice berhasil di-void dan jurnal reversal berhasil diposting.');
    }

    private function authorizeBranchAccess(ApInvoice $invoice): void
    {
        $branchScopeId = Auth::user()?->scopedCompanyBranchId();

        abort_if(
            $branchScopeId && (int) $invoice->company_branch_id !== (int) $branchScopeId,
            403
        );
    }

    private function cashAccountsForBranch(?int $branchId)
    {
        return ChartAccount::query()
            ->where('is_active', true)
            ->where('is_cash_account', true)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('company_branch_id')
                    ->when($branchId, fn ($branchQuery) => $branchQuery->orWhere('company_branch_id', $branchId));
            })
            ->orderBy('code')
            ->get();
    }
}
