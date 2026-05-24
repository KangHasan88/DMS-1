<?php

namespace App\Http\Controllers;

use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ConsignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Consignment::with('supplier', 'createdBy');
        
        if ($request->filled('search')) {
            $query->where('cn_number', 'like', "%{$request->search}%")
                  ->orWhereHas('supplier', function($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $perPage = $request->get('per_page', 10);
        $consignments = $query->orderBy('consignment_date', 'desc')->paginate($perPage);
        
        $statuses = Consignment::STATUS_LIST;
        
        return view('consignments.index', compact('consignments', 'statuses'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->orderBy('name')->get();
        $products = Product::active()->orderBy('name')->get();
        
        return view('consignments.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'consignment_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $items = [];
            $totalValue = 0;
            
            foreach ($request->items as $item) {
                $subtotal = $item['quantity'] * $item['price'];
                $totalValue += $subtotal;
                
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }
            
            $cnNumber = Consignment::generateCNNumber();
            
            $consignment = Consignment::create([
                'cn_number' => $cnNumber,
                'supplier_id' => $request->supplier_id,
                'consignment_date' => $request->consignment_date,
                'total_value' => $totalValue,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);
            
            foreach ($items as $item) {
                $item['consignment_id'] = $consignment->id;
                ConsignmentItem::create($item);
                
                // Tambah ke consignment stock
                $productStock = ProductStock::firstOrCreate(
                    ['product_id' => $item['product_id']],
                    ['quantity' => 0, 'consignment_quantity' => 0]
                );
                $productStock->consignment_quantity += $item['quantity'];
                $productStock->save();
                
                // Catat stock movement untuk consignment
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'source_type' => StockMovement::SOURCE_CONSIGNMENT,
                    'source_id' => $consignment->id,
                    'type' => StockMovement::TYPE_IN,
                    'quantity' => $item['quantity'],
                    'before_quantity' => $productStock->consignment_quantity - $item['quantity'],
                    'after_quantity' => $productStock->consignment_quantity,
                    'reason' => 'Consignment #' . $cnNumber,
                    'created_by' => Auth::id(),
                ]);
            }
            
            $consignment->updateStats();
            
            DB::commit();
            
            return redirect()->route('consignments.show', $consignment)
                ->with('success', 'Consignment berhasil dibuat. CN: ' . $cnNumber);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat consignment: ' . $e->getMessage());
        }
    }

    public function show(Consignment $consignment)
    {
        $consignment->load('supplier', 'items.product', 'payments', 'createdBy');
        
        return view('consignments.show', compact('consignment'));
    }

    public function destroy(Consignment $consignment)
    {
        if ($consignment->status !== Consignment::STATUS_ACTIVE) {
            return back()->with('error', 'Consignment tidak dapat dihapus karena sudah diproses');
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($consignment->items as $item) {
                $productStock = ProductStock::where('product_id', $item->product_id)->first();
                if ($productStock) {
                    $productStock->consignment_quantity -= $item->quantity;
                    $productStock->save();
                }
            }
            
            $consignment->delete();
            
            DB::commit();
            
            return redirect()->route('consignments.index')
                ->with('success', 'Consignment berhasil dihapus');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus consignment: ' . $e->getMessage());
        }
    }
    
    public function returnForm(Consignment $consignment)
    {
        if ($consignment->status === Consignment::STATUS_RETURNED) {
            return redirect()->route('consignments.show', $consignment)
                ->with('error', 'Barang sudah dikembalikan semua');
        }
        
        return view('consignments.return', compact('consignment'));
    }
    
    public function processReturn(Request $request, Consignment $consignment)
    {
        $validated = $request->validate([
            'return_date' => 'required|date',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:consignment_items,id',
            'items.*.return_quantity' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            foreach ($validated['items'] as $itemData) {
                $consignmentItem = ConsignmentItem::find($itemData['id']);
                $returnQty = $itemData['return_quantity'];
                
                if ($returnQty > 0) {
                    $remainingQty = $consignmentItem->quantity - $consignmentItem->sold_quantity - $consignmentItem->returned_quantity;
                    if ($returnQty > $remainingQty) {
                        throw new \Exception("Jumlah return melebihi sisa barang untuk produk {$consignmentItem->product->name}");
                    }
                    
                    $consignmentItem->returned_quantity += $returnQty;
                    $consignmentItem->save();
                    
                    // Kurangi consignment stock
                    $productStock = ProductStock::where('product_id', $consignmentItem->product_id)->first();
                    if ($productStock) {
                        $before = $productStock->consignment_quantity;
                        $productStock->consignment_quantity -= $returnQty;
                        $productStock->save();
                        
                        StockMovement::create([
                            'product_id' => $consignmentItem->product_id,
                            'source_type' => StockMovement::SOURCE_CONSIGNMENT_RETURN,
                            'source_id' => $consignment->id,
                            'type' => StockMovement::TYPE_OUT,
                            'quantity' => $returnQty,
                            'before_quantity' => $before,
                            'after_quantity' => $productStock->consignment_quantity,
                            'reason' => 'Return consignment #' . $consignment->cn_number,
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }
            
            $consignment->return_date = $validated['return_date'];
            $consignment->updateStats();
            
            DB::commit();
            
            return redirect()->route('consignments.show', $consignment)
                ->with('success', 'Return barang berhasil dicatat');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses return: ' . $e->getMessage());
        }
    }
}