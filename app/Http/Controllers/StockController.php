<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductWarehouseStock;
use App\Models\StockAdjustmentRequest;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Display stock list.
     */
    public function index(Request $request)
    {
        $query = Product::with('stock', 'unit')
            ->where('is_active', true);
        
        // Search
        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('category', 'like', "%{$request->search}%");
            });
        }
        
        // Filter by stock status
        if ($request->filled('stock_status')) {
            if ($request->stock_status == 'out_of_stock') {
                $query->where(function ($query) {
                    $query->whereDoesntHave('stock', function($q) {
                        $q->where('quantity', '>', 0);
                    })->orWhereHas('stock', function($q) {
                        $q->where('quantity', 0);
                    });
                });
            } elseif ($request->stock_status == 'low_stock') {
                $query->whereHas('stock', function($q) {
                    $q->whereRaw('quantity <= min_stock')
                        ->where('quantity', '>', 0);
                });
            } elseif ($request->stock_status == 'in_stock') {
                $query->whereHas('stock', function($q) {
                    $q->where('quantity', '>', 0);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $products = $query->orderBy('name')->paginate($perPage);
        
        return view('stock.index', compact('products'));
    }

    /**
     * Show stock detail for a product.
     */
    public function show(Product $product)
    {
        $stock = $product->stock;
        $warehouseStocks = ProductWarehouseStock::with('warehouse')
            ->where('product_id', $product->id)
            ->whereHas('warehouse')
            ->get()
            ->sortBy([
                fn (ProductWarehouseStock $stock) => $stock->warehouse?->is_default ? 0 : 1,
                fn (ProductWarehouseStock $stock) => $stock->warehouse?->sort_order ?? 999,
                fn (ProductWarehouseStock $stock) => $stock->warehouse?->name ?? '',
            ]);
        $movements = StockMovement::where('product_id', $product->id)
            ->with(['order', 'purchaseOrder', 'directPurchase', 'warehouse', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('stock.show', compact('product', 'stock', 'warehouseStocks', 'movements'));
    }

    /**
     * Show form to add stock.
     */
    public function addStockForm(Product $product)
    {
        return view('stock.add', compact('product'));
    }

    /**
     * Process add stock.
     */
    public function addStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $product->addStock(
                $validated['quantity'],
                null,
                $validated['reason'] ?? 'Tambah stok via admin'
            );
            
            DB::commit();
            
            return redirect()->route('stock.show', $product)
                ->with('success', 'Stok berhasil ditambahkan. Jumlah: ' . $validated['quantity']);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambah stok: ' . $e->getMessage());
        }
    }

    /**
     * Show form to reduce stock.
     */
    public function reduceStockForm(Product $product)
    {
        return view('stock.reduce', compact('product'));
    }

    /**
     * Process reduce stock.
     */
    public function reduceStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);
        
        $currentStock = $product->current_stock;
        
        if ($currentStock < $validated['quantity']) {
            return back()->with('error', 'Stok tidak mencukupi. Tersedia: ' . $currentStock);
        }
        
        DB::beginTransaction();
        
        try {
            $product->reduceStock(
                $validated['quantity'],
                null,
                $validated['reason'] ?? 'Pengurangan stok via admin'
            );
            
            DB::commit();
            
            return redirect()->route('stock.show', $product)
                ->with('success', 'Stok berhasil dikurangi. Jumlah: ' . $validated['quantity']);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengurangi stok: ' . $e->getMessage());
        }
    }

    /**
     * Show adjustment form.
     */
    public function adjustmentForm(Product $product)
    {
        return view('stock.adjustment', compact('product'));
    }

    /**
     * Process stock adjustment.
     */
    public function adjustment(Request $request, Product $product, ApprovalWorkflowService $approvalWorkflowService)
    {
        $validated = $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $currentQuantity = (int) $product->current_stock;
            $newQuantity = (int) $validated['new_quantity'];

            $stockAdjustmentRequest = StockAdjustmentRequest::create([
                'product_id' => $product->id,
                'current_quantity' => $currentQuantity,
                'new_quantity' => $newQuantity,
                'quantity_difference' => $newQuantity - $currentQuantity,
                'reason' => $validated['reason'],
                'requested_by' => auth()->id(),
            ]);

            $approvalRequest = $approvalWorkflowService->request([
                'approval_type' => ApprovalRequest::TYPE_STOCK_ADJUSTMENT,
                'title' => 'Approval Penyesuaian Stok ' . $product->name,
                'description' => sprintf(
                    'Request %s mengubah stok dari %s menjadi %s.',
                    $stockAdjustmentRequest->request_number,
                    number_format($currentQuantity, 0, ',', '.'),
                    number_format($newQuantity, 0, ',', '.')
                ),
                'request_note' => $validated['reason'],
                'payload' => [
                    'request_number' => $stockAdjustmentRequest->request_number,
                    'product' => $product->name,
                    'current_quantity' => $currentQuantity,
                    'new_quantity' => $newQuantity,
                    'quantity_difference' => $newQuantity - $currentQuantity,
                    'reason' => $validated['reason'],
                ],
            ], $stockAdjustmentRequest);

            $stockAdjustmentRequest->forceFill([
                'approval_request_id' => $approvalRequest->id,
            ])->save();
            
            DB::commit();
            
            return redirect()->route('approval-requests.show', $approvalRequest)
                ->with('success', 'Request penyesuaian stok berhasil dibuat. Stok akan berubah setelah disetujui.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat request penyesuaian stok: ' . $e->getMessage());
        }
    }

    /**
     * Get low stock products (for notification).
     */
    public function lowStock()
    {
        $products = Product::whereHas('stock', function($q) {
                $q->whereRaw('quantity <= min_stock');
            })
            ->with('stock', 'unit')
            ->get();
        
        return view('stock.low-stock', compact('products'));
    }

    /**
     * Display all stock movements report.
     */
    public function movements(Request $request)
    {
        $query = StockMovement::with([
            'product', 
            'order', 
            'purchaseOrder', 
            'directPurchase', 
            'outboundFoc', 
            'outboundReturn', 
            'warehouse',
            'createdBy'
        ]);
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        // Filter by type (in/out/adjustment)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by source type
        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        $perPage = $request->get('per_page', 20);
        $movements = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        // Get data for filters
        $products = Product::active()->orderBy('name')->get();
        $types = StockMovement::TYPES;
        $sourceTypes = StockMovement::SOURCE_TYPES;
        $warehouses = Warehouse::active()->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name')->get();
        
        return view('stock.movements', compact('movements', 'products', 'types', 'sourceTypes', 'warehouses'));
    }
}
