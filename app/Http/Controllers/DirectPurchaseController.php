<?php

namespace App\Http\Controllers;

use App\Models\DirectPurchase;
use App\Models\DirectPurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DirectPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = DirectPurchase::with('supplier', 'createdBy');
        
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', "%{$request->search}%")
                  ->orWhere('supplier_name', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('purchase_type')) {
            $query->where('purchase_type', $request->purchase_type);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }
        
        $perPage = $request->get('per_page', 10);
        $purchases = $query->orderBy('purchase_date', 'desc')->paginate($perPage);
        
        return view('direct-purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->orderBy('name')->get();
        $products = Product::active()->orderBy('name')->get();
        
        return view('direct-purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        Log::info('Direct Purchase Request:', $request->all());
        
        $validated = $request->validate([
            'purchase_type' => 'required|in:cash,foc',
            'reference_po' => 'nullable|string|max:100',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'required|string|max:255',
            'supplier_phone' => 'nullable|string|max:20',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);
        
        Log::info('Validation passed');
        
        DB::beginTransaction();
        
        try {
            $subtotal = 0;
            $items = [];
            $isFoc = $request->purchase_type === DirectPurchase::TYPE_FOC;
            
            foreach ($request->items as $item) {
                $price = $isFoc ? 0 : $item['price'];
                $subtotalItem = $item['quantity'] * $price;
                $subtotal += $subtotalItem;
                
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $subtotalItem,
                    'notes' => $item['notes'] ?? null,
                ];
            }
            
            $invoiceNumber = DirectPurchase::generateInvoiceNumber($request->purchase_type);
            Log::info('Invoice Number: ' . $invoiceNumber);
            Log::info('User ID: ' . Auth::id());
            Log::info('User: ' . Auth::user()->name);
            
            $purchase = DirectPurchase::create([
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $request->supplier_id,
                'supplier_name' => $request->supplier_name,
                'supplier_phone' => $request->supplier_phone,
                'purchase_date' => $request->purchase_date,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'purchase_type' => $request->purchase_type,
                'reference_po' => $request->reference_po,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);
            
            Log::info('Purchase created with ID: ' . $purchase->id);
            
            foreach ($items as $item) {
                $item['direct_purchase_id'] = $purchase->id;
                DirectPurchaseItem::create($item);
                Log::info('Item created for product: ' . $item['product_id']);
                
                $product = Product::find($item['product_id']);
                $reason = $isFoc 
                    ? 'FOC (Free of Charge) dari supplier ' . $request->supplier_name . ' - ' . ($item['notes'] ?? '')
                    : 'Direct Purchase #' . $purchase->invoice_number;
                
                if ($isFoc) {
                    $product->addFromFoc($item['quantity'], $reason);
                } else {
                    $product->addFromDirectPurchase($item['quantity'], $purchase->id, $reason);
                }
            }
            
            DB::commit();
            
            Log::info('Direct Purchase completed successfully');
            
            $message = $isFoc 
                ? '? FOC (Free of Charge) berhasil dicatat. Stock gratis bertambah!'
                : '? Pembelian langsung berhasil dicatat. Stock bertambah!';
            
            return redirect()->route('direct-purchases.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Direct Purchase Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', '? Gagal mencatat: ' . $e->getMessage());
        }
    }

    public function show(DirectPurchase $directPurchase)
    {
        $directPurchase->load('supplier', 'items.product', 'createdBy');
        
        return view('direct-purchases.show', compact('directPurchase'));
    }

    public function destroy(DirectPurchase $directPurchase)
    {
        $directPurchase->delete();
        
        return redirect()->route('direct-purchases.index')
            ->with('success', '? Pembelian langsung berhasil dihapus');
    }
}