<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CompanyBranch;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $query = PurchaseOrder::with('supplier', 'createdBy', 'companyBranch', 'approvalRequest')
            ->forUserBranch();

        if (!$branchScopeId && $request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }
        
        // Search
        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('po_number', 'like', "%{$request->search}%")
                    ->orWhereHas('supplier', function($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%");
                    });
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        
        $perPage = $request->get('per_page', 10);
        $purchaseOrders = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        $statuses = PurchaseOrder::STATUS_LIST;
        $suppliers = Supplier::active()->orderBy('name')->get();
        
        return view('purchase-orders.index', compact('purchaseOrders', 'statuses', 'suppliers'));
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create()
    {
        $suppliers = Supplier::active()->orderBy('name')->get();
        $products = Product::with(['principal', 'unit'])->active()->orderBy('name')->get();
        
        return view('purchase-orders.create', compact('suppliers', 'products'));
    }

    /**
     * Show proposed purchase order recommendations based on inventory velocity.
     */
    public function proposed(Request $request)
    {
        $targetWeeks = max(1, min(12, (int) $request->get('target_weeks', 4)));
        $showAnalysis = $request->boolean('show_analysis');
        $salesWindowStart = now()->subDays(30)->startOfDay();

        $products = Product::with('principal', 'unit', 'stock')
            ->active()
            ->orderBy('name')
            ->get();

        $salesVelocity = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as sold_last_30_days'))
            ->whereIn('product_id', $products->pluck('id'))
            ->whereHas('order', function ($query) use ($salesWindowStart) {
                $query->whereIn('status', [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])
                    ->where('created_at', '>=', $salesWindowStart);
            })
            ->groupBy('product_id')
            ->pluck('sold_last_30_days', 'product_id');

        $analysisRows = $products->map(function (Product $product) use ($salesVelocity, $targetWeeks) {
                $stock = $product->stock;
                $currentStock = $stock?->quantity ?? 0;
                $minStock = $stock?->min_stock ?? 0;
                $soldLast30Days = (int) ($salesVelocity[$product->id] ?? 0);
                $weeklySalesAverage = $soldLast30Days > 0 ? $soldLast30Days / (30 / 7) : 0;
                $targetQuantity = max((int) ceil($weeklySalesAverage * $targetWeeks), $minStock);
                $recommendedQuantity = max(0, $targetQuantity - $currentStock);
                $weekCover = $weeklySalesAverage > 0 ? round($currentStock / $weeklySalesAverage, 1) : null;
                $reason = 'Stok cukup untuk target week-cover dan min stock.';

                if ($soldLast30Days <= 0 && $currentStock >= $minStock) {
                    $reason = 'Belum ada histori penjualan 30 hari dan stok masih memenuhi min stock.';
                } elseif ($currentStock < $minStock) {
                    $reason = 'Stok di bawah min stock.';
                } elseif ($recommendedQuantity > 0) {
                    $reason = 'Stok belum mencapai target week-cover.';
                }

                return [
                    'product' => $product,
                    'current_stock' => $currentStock,
                    'min_stock' => $minStock,
                    'sold_last_30_days' => $soldLast30Days,
                    'weekly_sales_average' => round($weeklySalesAverage, 1),
                    'week_cover' => $weekCover,
                    'target_quantity' => $targetQuantity,
                    'recommended_quantity' => $recommendedQuantity,
                    'estimated_price' => $product->base_price ?: $product->price,
                    'needs_reorder' => $recommendedQuantity > 0,
                    'reason' => $reason,
                ];
            })
            ->values();
        $recommendations = $analysisRows
            ->filter(fn (array $row) => $row['needs_reorder'])
            ->values();

        $proposalSummary = [
            'active_products' => $products->count(),
            'products_with_sales' => $salesVelocity->count(),
            'below_min_stock' => $products->filter(function (Product $product) {
                $stock = $product->stock;

                return $stock && $stock->quantity < $stock->min_stock;
            })->count(),
            'recommendations' => $recommendations->count(),
        ];

        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('purchase-orders.proposed', compact('recommendations', 'analysisRows', 'proposalSummary', 'suppliers', 'targetWeeks', 'showAnalysis'));
    }

    /**
     * Store a newly created purchase order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $subtotal = 0;
            $items = [];
            
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $subtotalItem = $item['quantity'] * $item['price'];
                $subtotal += $subtotalItem;
                
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotalItem,
                    'notes' => $item['notes'] ?? null,
                ];
            }
            
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePONumber(),
                'supplier_id' => $request->supplier_id,
                'company_branch_id' => $this->branchIdForWrite(),
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $request->notes,
                'internal_notes' => $request->internal_notes,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'approval_status' => PurchaseOrder::APPROVAL_NOT_REQUESTED,
                'created_by' => Auth::id(),
            ]);
            
            foreach ($items as $item) {
                $item['purchase_order_id'] = $purchaseOrder->id;
                PurchaseOrderItem::create($item);
            }
            
            DB::commit();
            
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase Order berhasil dibuat. Nomor PO: ' . $purchaseOrder->po_number);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat PO: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase order.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeBranchAccess($purchaseOrder);

        $purchaseOrder->load('supplier', 'items.product', 'createdBy', 'approvedBy', 'rejectedBy', 'approvalRequest');
        
        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    /**
     * Show the form for editing the specified purchase order.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeBranchAccess($purchaseOrder);

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT || $purchaseOrder->isApprovalPending()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'PO tidak dapat diedit karena sudah diajukan atau diproses');
        }
        
        $purchaseOrder->loadMissing('supplier', 'items.product');

        $currentSupplierIds = collect([$purchaseOrder->supplier_id])->filter();
        $currentProductIds = $purchaseOrder->items->pluck('product_id')->filter()->unique()->values();

        $suppliers = Supplier::query()
            ->where(function ($query) use ($currentSupplierIds) {
                $query->active();

                if ($currentSupplierIds->isNotEmpty()) {
                    $query->orWhereIn('id', $currentSupplierIds);
                }
            })
            ->orderBy('name')
            ->get();
        $products = Product::with(['principal', 'unit'])
            ->where(function ($query) use ($currentProductIds) {
                $query->active();

                if ($currentProductIds->isNotEmpty()) {
                    $query->orWhereIn('id', $currentProductIds);
                }
            })
            ->orderBy('name')
            ->get();
        $activeProducts = $products->where('is_active', true)->values();
        
        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers', 'products', 'activeProducts'));
    }

    /**
     * Update the specified purchase order in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeBranchAccess($purchaseOrder);

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT || $purchaseOrder->isApprovalPending()) {
            return back()->with('error', 'PO tidak dapat diupdate karena sudah diajukan atau diproses');
        }
        
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:purchase_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $subtotal = 0;
            $existingItemIds = $purchaseOrder->items->pluck('id')->toArray();
            $newItemIds = [];
            
            foreach ($request->items as $item) {
                $subtotalItem = $item['quantity'] * $item['price'];
                $subtotal += $subtotalItem;
                
                $itemData = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotalItem,
                    'notes' => $item['notes'] ?? null,
                ];
                
                if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                    $poItem = PurchaseOrderItem::find($item['id']);
                    $poItem->update($itemData);
                    $newItemIds[] = $poItem->id;
                } else {
                    $itemData['purchase_order_id'] = $purchaseOrder->id;
                    $newItem = PurchaseOrderItem::create($itemData);
                    $newItemIds[] = $newItem->id;
                }
            }
            
            // Delete removed items
            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            PurchaseOrderItem::whereIn('id', $itemsToDelete)->delete();
            
            $purchaseOrder->update([
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $request->notes,
                'internal_notes' => $request->internal_notes,
                'approval_status' => PurchaseOrder::APPROVAL_NOT_REQUESTED,
                'approval_request_id' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_note' => null,
            ]);
            
            DB::commit();
            
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', 'Purchase Order berhasil diupdate');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate PO: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified purchase order from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeBranchAccess($purchaseOrder);

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT || $purchaseOrder->isApprovalPending()) {
            return back()->with('error', 'PO tidak dapat dihapus karena sudah diproses');
        }
        
        $purchaseOrder->delete();
        
        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase Order berhasil dihapus');
    }
    
    /**
     * Approve purchase order.
     */
    public function approve(PurchaseOrder $purchaseOrder, ApprovalWorkflowService $approvalWorkflowService)
    {
        $this->authorizeBranchAccess($purchaseOrder);

        if (!$purchaseOrder->canApprove()) {
            return back()->with('error', 'PO tidak dapat diajukan approval');
        }

        DB::beginTransaction();

        try {
            $purchaseOrder->loadMissing('supplier', 'items.product');

            $approvalRequest = $approvalWorkflowService->request([
                'approval_type' => ApprovalRequest::TYPE_PURCHASE_ORDER,
                'company_branch_id' => $purchaseOrder->company_branch_id,
                'title' => 'Approval Purchase Order ' . $purchaseOrder->po_number,
                'description' => sprintf(
                    'PO %s ke %s dengan total Rp %s.',
                    $purchaseOrder->po_number,
                    $purchaseOrder->supplier?->name ?? '-',
                    number_format((int) $purchaseOrder->total, 0, ',', '.')
                ),
                'request_note' => $purchaseOrder->internal_notes ?: $purchaseOrder->notes,
                'payload' => [
                    'po_number' => $purchaseOrder->po_number,
                    'supplier' => $purchaseOrder->supplier?->name,
                    'order_date' => optional($purchaseOrder->order_date)->format('Y-m-d'),
                    'expected_delivery_date' => optional($purchaseOrder->expected_delivery_date)->format('Y-m-d'),
                    'total' => (int) $purchaseOrder->total,
                    'items' => $purchaseOrder->items->map(fn ($item) => [
                        'product' => $item->product?->name,
                        'quantity' => (int) $item->quantity,
                        'price' => (int) $item->price,
                        'subtotal' => (int) $item->subtotal,
                    ])->values()->all(),
                ],
            ], $purchaseOrder);

            $purchaseOrder->forceFill([
                'approval_request_id' => $approvalRequest->id,
                'approval_status' => PurchaseOrder::APPROVAL_PENDING,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_note' => null,
            ])->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal mengajukan approval PO: ' . $e->getMessage());
        }

        return redirect()->route('approval-requests.show', $approvalRequest)
            ->with('success', 'Approval PO berhasil diajukan. PO baru bisa diterima setelah disetujui.');
    }
    
    /**
     * Cancel purchase order.
     */
    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeBranchAccess($purchaseOrder);

        if ($purchaseOrder->status === PurchaseOrder::STATUS_RECEIVED) {
            return back()->with('error', 'PO yang sudah diterima tidak dapat dibatalkan');
        }

        if ($purchaseOrder->isApprovalPending()) {
            return back()->with('error', 'PO sedang menunggu approval dan tidak dapat dibatalkan');
        }
        
        $purchaseOrder->cancel();
        
        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO berhasil dibatalkan');
    }
    
    /**
     * Show receive form.
     */
    public function receiveForm(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeBranchAccess($purchaseOrder);

        if (!$purchaseOrder->canReceive()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'PO tidak dapat diterima');
        }
        
        $purchaseOrder->load('items.product');
        
        return view('purchase-orders.receive', compact('purchaseOrder'));
    }
    
    /**
     * Process receiving.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeBranchAccess($purchaseOrder);

        if (!$purchaseOrder->canReceive()) {
            return back()->with('error', 'PO tidak dapat diterima');
        }
        
        $validated = $request->validate([
            'received_date' => 'required|date',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $allReceived = true;
            $anyReceived = false;
            
            foreach ($validated['items'] as $itemData) {
                $poItem = $purchaseOrder->items()
                    ->with('product')
                    ->whereKey($itemData['id'])
                    ->first();

                if (!$poItem) {
                    throw new \Exception('Item PO tidak valid untuk dokumen ini');
                }

                $receiveQty = $itemData['received_quantity'];
                
                if ($receiveQty > 0) {
                    $anyReceived = true;
                    if ($receiveQty > $poItem->remaining_quantity) {
                        throw new \Exception("Quantity melebihi sisa PO untuk produk {$poItem->product->name}");
                    }
                    
                    $poItem->receive($receiveQty, $itemData['notes'] ?? null);
                }
                
                if (!$poItem->isFullyReceived()) {
                    $allReceived = false;
                }
            }
            
            if (!$anyReceived) {
                throw new \Exception('Tidak ada barang yang diterima');
            }
            
            // Update PO status
            $newStatus = $allReceived ? PurchaseOrder::STATUS_RECEIVED : PurchaseOrder::STATUS_PARTIALLY_RECEIVED;
            $purchaseOrder->update([
                'status' => $newStatus,
                'received_date' => $validated['received_date'],
            ]);
            
            DB::commit();
            
            $message = $allReceived 
                ? 'Semua barang telah diterima. Stock berhasil ditambahkan.'
                : 'Barang diterima sebagian. Stock berhasil ditambahkan.';
            
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menerima barang: ' . $e->getMessage());
        }
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function branchIdForWrite(): ?int
    {
        return $this->currentBranchScopeId()
            ?: CompanyBranch::where('is_invoice_default', true)->value('id')
            ?: CompanyBranch::orderBy('id')->value('id');
    }

    private function authorizeBranchAccess(PurchaseOrder $purchaseOrder): void
    {
        $branchScopeId = $this->currentBranchScopeId();

        abort_if(
            $branchScopeId && (int) $purchaseOrder->company_branch_id !== (int) $branchScopeId,
            403
        );
    }
}
