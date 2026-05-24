<?php

namespace App\Http\Controllers;

use App\Models\OutboundFoc;
use App\Models\OutboundFocItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OutboundFocController extends Controller
{
    public function index(Request $request)
    {
        $query = OutboundFoc::with('createdBy');
        
        if ($request->filled('search')) {
            $query->where('foc_number', 'like', "%{$request->search}%")
                  ->orWhere('customer_name', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }
        
        $perPage = $request->get('per_page', 10);
        $focs = $query->orderBy('foc_date', 'desc')->paginate($perPage);
        
        $reasons = OutboundFoc::REASONS;
        
        return view('outbound-focs.index', compact('focs', 'reasons'));
    }

    public function create()
    {
        $products = Product::active()->orderBy('name')->get();
        $reasons = OutboundFoc::REASONS;
        
        return view('outbound-focs.create', compact('products', 'reasons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'foc_date' => 'required|date',
            'reason' => 'required|in:' . implode(',', array_keys(OutboundFoc::REASONS)),
            'reason_detail' => 'nullable|string',
            'reference_order' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.notes' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $subtotal = 0;
            $items = [];
            
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $price = $product->price;
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
            
            $focNumber = OutboundFoc::generateFocNumber();
            
            $foc = OutboundFoc::create([
                'foc_number' => $focNumber,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'address' => $validated['address'],
                'foc_date' => $validated['foc_date'],
                'reason' => $validated['reason'],
                'reason_detail' => $validated['reason_detail'],
                'reference_order' => $validated['reference_order'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);
            
            foreach ($items as $item) {
                $item['outbound_foc_id'] = $foc->id;
                OutboundFocItem::create($item);
                
                $product = Product::find($item['product_id']);
                $product->reduceForFocOut(
                    $item['quantity'],
                    $foc->id,
                    'FOC Out: ' . $validated['reason'] . ' - ' . ($validated['reason_detail'] ?? '')
                );
            }
            
            DB::commit();
            
            return redirect()->route('outbound-focs.show', $foc)
                ->with('success', 'FOC berhasil dicatat. Stock berkurang!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencatat FOC: ' . $e->getMessage());
        }
    }

    public function show(OutboundFoc $outboundFoc)
    {
        $outboundFoc->load('items.product', 'createdBy');
        
        return view('outbound-focs.show', compact('outboundFoc'));
    }

    public function destroy(OutboundFoc $outboundFoc)
    {
        $outboundFoc->delete();
        
        return redirect()->route('outbound-focs.index')
            ->with('success', 'FOC berhasil dihapus');
    }
}