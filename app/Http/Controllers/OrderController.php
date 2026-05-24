<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user', 'delivery');
        
        if ($request->filled('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }
        
        if ($request->filled('customer_name')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->customer_name}%")
                  ->orWhere('email', 'like', "%{$request->customer_name}%")
                  ->orWhere('phone', 'like', "%{$request->customer_name}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('order_source')) {
            $query->where('order_source', $request->order_source);
        }
        
        if ($request->filled('fulfillment_type')) {
            $query->where('fulfillment_type', $request->fulfillment_type);
        }
        
        if ($request->filled('delivery_date')) {
            $query->whereDate('delivery_date', $request->delivery_date);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $perPage = $request->get('per_page', 10);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        $statuses = Order::STATUS_LIST;
        $orderSources = [
            Order::ORDER_SOURCE_APP => 'Aplikasi',
            Order::ORDER_SOURCE_ADMIN => 'Admin',
        ];
        $fulfillmentTypes = [
            Order::FULFILLMENT_JIT => 'Just In Time (Beli ke Pasar)',
            Order::FULFILLMENT_STOCK => 'Stock (Ambil dari Gudang)',
        ];
        
        return view('orders.index', compact('orders', 'statuses', 'orderSources', 'fulfillmentTypes'));
    }

    public function create(Request $request)
    {
        $products = Product::active()->orderBy('name')->get();
        $customers = User::role('customer')->active()->orderBy('name')->get();
        
        $productsWithStock = [];
        foreach ($products as $product) {
            $stock = ProductStock::where('product_id', $product->id)->first();
            $productsWithStock[$product->id] = [
                'product' => $product,
                'stock' => $stock ? $stock->quantity : 0,
                'has_stock' => $stock && $stock->quantity > 0
            ];
        }
        
        $defaultDeliveryDate = now()->addDay()->format('Y-m-d');
        $defaultFulfillmentType = $request->get('fulfillment_type', Order::FULFILLMENT_STOCK);
        
        return view('orders.create', compact(
            'products', 
            'customers', 
            'productsWithStock',
            'defaultDeliveryDate',
            'defaultFulfillmentType'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'delivery_date' => 'required|date|after_or_equal:today',
            'delivery_time_slot' => 'required|string',
            'address' => 'required|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'packing_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'order_source' => 'required|in:' . Order::ORDER_SOURCE_APP . ',' . Order::ORDER_SOURCE_ADMIN,
            'fulfillment_type' => 'required|in:' . Order::FULFILLMENT_STOCK . ',' . Order::FULFILLMENT_JIT,
            'payment_method' => 'nullable|in:gateway,manual,wallet',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.notes' => 'nullable|string',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'required|in:none,percent,nominal',
            'discount_value' => 'required_if:discount_type,percent,nominal|numeric|min:0',
            'shipping_type' => 'required|in:flat,weight,distance',
            'shipping_weight' => 'required_if:shipping_type,weight|nullable|numeric|min:0',
            'shipping_distance' => 'required_if:shipping_type,distance|nullable|numeric|min:0',
            'shipping_rate' => 'required|numeric|min:0',
            'include_ppn' => 'boolean',
            'ppn_rate' => 'nullable|numeric|min:0|max:100',
        ]);
        
        DB::beginTransaction();
        
        try {
            $subtotal = 0;
            $orderItems = [];
            $totalItemDiscount = 0;
            
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $price = $product->price;
                
                $itemDiscount = 0;
                if (isset($item['discount_percent']) && $item['discount_percent'] > 0) {
                    $itemDiscount = ($price * $item['discount_percent'] / 100) * $quantity;
                    $totalItemDiscount += $itemDiscount;
                }
                
                $subtotalItem = ($price * $quantity) - $itemDiscount;
                $subtotal += $subtotalItem;
                
                $itemData = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $price,
                    'discount' => $itemDiscount,
                    'quantity' => $quantity,
                    'subtotal' => $subtotalItem,
                    'notes' => $item['notes'] ?? null,
                    'fulfillment_status' => OrderItem::FULFILLMENT_PENDING,
                ];
                
                $orderItems[] = $itemData;
                
                if ($request->fulfillment_type === Order::FULFILLMENT_STOCK) {
                    $stock = ProductStock::where('product_id', $product->id)->first();
                    if (!$stock || $stock->quantity < $quantity) {
                        throw new \Exception("Stock {$product->name} tidak mencukupi. Tersedia: " . ($stock ? $stock->quantity : 0));
                    }
                }
            }
            
            // Hitung diskon order
            $orderDiscount = 0;
            if ($request->discount_type == 'percent') {
                $orderDiscount = $subtotal * $request->discount_value / 100;
            } elseif ($request->discount_type == 'nominal') {
                $orderDiscount = $request->discount_value;
            }
            
            $afterDiscount = $subtotal - $orderDiscount;
            
            // Hitung ongkos kirim
            $shippingCost = $request->shipping_rate;
            if ($request->shipping_type == 'weight' && $request->shipping_weight) {
                $shippingCost = $request->shipping_weight * $request->shipping_rate;
            } elseif ($request->shipping_type == 'distance' && $request->shipping_distance) {
                $shippingCost = $request->shipping_distance * $request->shipping_rate;
            }
            
            // Biaya repack/packing
            $packingFee = $request->packing_fee ?? 1000;
            
            // Hitung PPN
            $ppnAmount = 0;
            $ppnRate = $request->include_ppn ? ($request->ppn_rate ?? 11) : 0;
            if ($request->include_ppn) {
                $ppnAmount = ($afterDiscount + $shippingCost + $packingFee) * $ppnRate / 100;
            }
            
            $grandTotal = $afterDiscount + $shippingCost + $packingFee + $ppnAmount;
            
            $paymentMethod = $request->payment_method;
            if ($request->order_source === Order::ORDER_SOURCE_ADMIN && !$paymentMethod) {
                $paymentMethod = Order::PAYMENT_MANUAL;
            }
            
            $order = Order::create([
                'user_id' => $request->user_id,
                'order_number' => Order::generateOrderNumber(),
                'delivery_date' => $request->delivery_date,
                'delivery_time_slot' => $request->delivery_time_slot,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'delivery_fee' => $shippingCost,
                'packing_fee' => $packingFee,
                'subtotal' => $subtotal,
                'total' => $grandTotal,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'discount_amount' => $orderDiscount,
                'shipping_type' => $request->shipping_type,
                'shipping_weight' => $request->shipping_weight,
                'shipping_distance' => $request->shipping_distance,
                'shipping_rate' => $request->shipping_rate,
                'include_ppn' => $request->include_ppn ? true : false,
                'ppn_rate' => $ppnRate,
                'ppn_amount' => $ppnAmount,
                'grand_total' => $grandTotal,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'notes' => $request->notes,
                'order_source' => $request->order_source,
                'fulfillment_type' => $request->fulfillment_type,
                'payment_method' => $paymentMethod,
                'admin_notes' => 'Order dibuat oleh ' . Auth::user()->name,
            ]);
            
            foreach ($orderItems as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }
            
            DB::commit();
            
            $message = "Order berhasil dibuat. Nomor order: {$order->order_number}\n";
            $message .= "Subtotal: Rp " . number_format($subtotal, 0, ',', '.') . "\n";
            if ($orderDiscount > 0) {
                $message .= "Diskon: -Rp " . number_format($orderDiscount, 0, ',', '.') . "\n";
            }
            $message .= "Ongkir: Rp " . number_format($shippingCost, 0, ',', '.') . "\n";
            $message .= "Packing: Rp " . number_format($packingFee, 0, ',', '.') . "\n";
            if ($ppnAmount > 0) {
                $message .= "PPN: Rp " . number_format($ppnAmount, 0, ',', '.') . "\n";
            }
            $message .= "Total: Rp " . number_format($grandTotal, 0, ',', '.');
            
            return redirect()->route('orders.show', $order)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat order: ' . $e->getMessage());
        }
    }

    public function show(Order $order)
    {
        $order->load('user', 'items.product', 'delivery');
        
        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        if (!$order->canUpdateStatus()) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order tidak dapat diubah karena sudah selesai/dibatalkan');
        }
        
        $products = Product::active()->orderBy('name')->get();
        $customers = User::role('customer')->active()->orderBy('name')->get();
        
        $productsWithStock = [];
        foreach ($products as $product) {
            $stock = ProductStock::where('product_id', $product->id)->first();
            $productsWithStock[$product->id] = [
                'product' => $product,
                'stock' => $stock ? $stock->quantity : 0,
                'has_stock' => $stock && $stock->quantity > 0
            ];
        }
        
        return view('orders.edit', compact('order', 'products', 'customers', 'productsWithStock'));
    }

    public function update(Request $request, Order $order)
    {
        if (!$order->canUpdateStatus()) {
            return back()->with('error', 'Order tidak dapat diubah karena sudah selesai/dibatalkan');
        }
        
        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'delivery_time_slot' => 'required|string',
            'address' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.notes' => 'nullable|string',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'required|in:none,percent,nominal',
            'discount_value' => 'required_if:discount_type,percent,nominal|numeric|min:0',
            'shipping_type' => 'required|in:flat,weight,distance',
            'shipping_weight' => 'required_if:shipping_type,weight|nullable|numeric|min:0',
            'shipping_distance' => 'required_if:shipping_type,distance|nullable|numeric|min:0',
            'shipping_rate' => 'required|numeric|min:0',
            'include_ppn' => 'boolean',
            'ppn_rate' => 'nullable|numeric|min:0|max:100',
        ]);
        
        DB::beginTransaction();
        
        try {
            $existingItemIds = $order->items->pluck('id')->toArray();
            $newItemIds = [];
            $subtotal = 0;
            
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $price = $product->price;
                
                $itemDiscount = 0;
                if (isset($item['discount_percent']) && $item['discount_percent'] > 0) {
                    $itemDiscount = ($price * $item['discount_percent'] / 100) * $quantity;
                }
                
                $subtotalItem = ($price * $quantity) - $itemDiscount;
                $subtotal += $subtotalItem;
                
                $itemData = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $price,
                    'discount' => $itemDiscount,
                    'quantity' => $quantity,
                    'subtotal' => $subtotalItem,
                    'notes' => $item['notes'] ?? null,
                ];
                
                if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                    $orderItem = OrderItem::find($item['id']);
                    $orderItem->update($itemData);
                    $newItemIds[] = $orderItem->id;
                } else {
                    $itemData['order_id'] = $order->id;
                    $itemData['fulfillment_status'] = OrderItem::FULFILLMENT_PENDING;
                    $newItem = OrderItem::create($itemData);
                    $newItemIds[] = $newItem->id;
                }
            }
            
            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            OrderItem::whereIn('id', $itemsToDelete)->delete();
            
            // Hitung diskon order
            $orderDiscount = 0;
            if ($request->discount_type == 'percent') {
                $orderDiscount = $subtotal * $request->discount_value / 100;
            } elseif ($request->discount_type == 'nominal') {
                $orderDiscount = $request->discount_value;
            }
            
            $afterDiscount = $subtotal - $orderDiscount;
            
            // Hitung ongkos kirim
            $shippingCost = $request->shipping_rate;
            if ($request->shipping_type == 'weight' && $request->shipping_weight) {
                $shippingCost = $request->shipping_weight * $request->shipping_rate;
            } elseif ($request->shipping_type == 'distance' && $request->shipping_distance) {
                $shippingCost = $request->shipping_distance * $request->shipping_rate;
            }
            
            // Biaya repack/packing
            $packingFee = $order->packing_fee ?? 1000;
            
            // Hitung PPN
            $ppnAmount = 0;
            $ppnRate = $request->include_ppn ? ($request->ppn_rate ?? 11) : 0;
            if ($request->include_ppn) {
                $ppnAmount = ($afterDiscount + $shippingCost + $packingFee) * $ppnRate / 100;
            }
            
            $grandTotal = $afterDiscount + $shippingCost + $packingFee + $ppnAmount;
            
            $order->update([
                'delivery_date' => $request->delivery_date,
                'delivery_time_slot' => $request->delivery_time_slot,
                'address' => $request->address,
                'notes' => $request->notes,
                'delivery_fee' => $shippingCost,
                'packing_fee' => $packingFee,
                'subtotal' => $subtotal,
                'total' => $grandTotal,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'discount_amount' => $orderDiscount,
                'shipping_type' => $request->shipping_type,
                'shipping_weight' => $request->shipping_weight,
                'shipping_distance' => $request->shipping_distance,
                'shipping_rate' => $request->shipping_rate,
                'include_ppn' => $request->include_ppn ? true : false,
                'ppn_rate' => $ppnRate,
                'ppn_amount' => $ppnAmount,
                'grand_total' => $grandTotal,
            ]);
            
            DB::commit();
            
            return redirect()->route('orders.show', $order)
                ->with('success', 'Order berhasil diupdate');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate order: ' . $e->getMessage());
        }
    }

    public function destroy(Order $order)
    {
        if ($order->status !== Order::STATUS_PENDING_PAYMENT && $order->status !== Order::STATUS_CANCELLED) {
            return back()->with('error', 'Order tidak dapat dihapus karena sudah diproses');
        }
        
        $order->delete();
        
        return redirect()->route('orders.index')
            ->with('success', 'Order berhasil dihapus');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Order::STATUS_LIST)),
            'notes' => 'nullable|string',
        ]);

        if ($validated['status'] === $order->status) {
            return back()->with('error', 'Status order sudah berada di ' . Order::STATUS_LIST[$order->status]);
        }

        if (!$order->canTransitionTo($validated['status'])) {
            return back()->with('error', 'Perubahan status dari ' . $order->status_label . ' ke ' . Order::STATUS_LIST[$validated['status']] . ' tidak valid');
        }

        if ($validated['status'] === Order::STATUS_CANCELLED) {
            DB::transaction(function () use ($order, $validated) {
                $order->restoreAllocatedStock($validated['notes'] ?? null);
                $order->updateStatus(Order::STATUS_CANCELLED, $validated['notes'] ?? 'Order dibatalkan');
            });

            return redirect()->route('orders.show', $order)
                ->with('success', 'Order dibatalkan dan stock yang sudah dialokasikan dikembalikan.');
        }
        
        // Handle status PAID
        if ($validated['status'] === Order::STATUS_PAID) {
            $order->updateStatus(Order::STATUS_PAID, $validated['notes'] ?? 'Pembayaran dikonfirmasi');
            
            return redirect()->route('orders.show', $order)
                ->with('success', 'Pembayaran dikonfirmasi.');
        }

        if ($validated['status'] === Order::STATUS_CHECKING_STOCK) {
            DB::transaction(function () use ($order) {
                $order->updateStatus(Order::STATUS_CHECKING_STOCK, 'Memeriksa ketersediaan stock');
                $allAvailable = $order->processStockReduction();

                if (!$allAvailable) {
                    session()->flash('warning', 'Beberapa item tidak tersedia di stock dan telah ditandai untuk refund.');
                }
            });

            return redirect()->route('orders.show', $order)
                ->with('success', 'Stock berhasil dicek dan dialokasikan.');
        }

        if ($validated['status'] === Order::STATUS_PROCURING) {
            $order->updateStatus(Order::STATUS_PROCURING, $validated['notes'] ?? 'Menunggu input data belanja');

            return redirect()->route('orders.show', $order)
                ->with('success', 'Order masuk ke proses belanja');
        }
        
        // Handle status REPACKING (untuk mode stock yang sudah di-check)
        if ($validated['status'] === Order::STATUS_REPACKING) {
            // Pastikan sudah melewati checking_stock untuk mode stock
            if ($order->useStockMode() && $order->status !== Order::STATUS_CHECKING_STOCK) {
                return back()->with('error', 'Order harus dalam status Cek Stock terlebih dahulu');
            }
            
            // Untuk mode JIT, pastikan sudah procuring
            if ($order->useJitMode() && $order->status !== Order::STATUS_PROCURING) {
                return back()->with('error', 'Order harus dalam status Belanja terlebih dahulu');
            }
            
            $order->updateStatus(Order::STATUS_REPACKING, $validated['notes'] ?? 'Proses repack dimulai');
            return redirect()->route('orders.show', $order)
                ->with('success', 'Order masuk ke proses repack');
        }
        
        // Untuk status lainnya
        if ($order->updateStatus($validated['status'], $validated['notes'] ?? null)) {
            return redirect()->route('orders.show', $order)
                ->with('success', 'Status order berhasil diupdate menjadi ' . Order::STATUS_LIST[$validated['status']]);
        }
        
        return back()->with('error', 'Gagal mengupdate status order');
    }
    
    public function processProcurement(Request $request, Order $order)
    {
        if ($order->status !== Order::STATUS_PROCURING) {
            return back()->with('error', 'Order harus dalam status Belanja untuk input data');
        }
        
        if (!$order->useJitMode()) {
            return back()->with('error', 'Order ini menggunakan mode stock, bukan JIT');
        }
        
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:order_items,id',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.supplier_name' => 'nullable|string',
            'items.*.market_location' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $allProcured = true;
            
            foreach ($validated['items'] as $itemData) {
                $orderItem = OrderItem::find($itemData['id']);
                
                if ($orderItem->fulfillment_status === OrderItem::FULFILLMENT_PENDING) {
                    $orderItem->markAsProcured(
                        $itemData['purchase_price'],
                        $itemData['supplier_name'] ?? null,
                        $itemData['market_location'] ?? null
                    );
                    $allProcured = false;
                }
            }
            
            $remainingPending = $order->items()
                ->where('fulfillment_status', OrderItem::FULFILLMENT_PENDING)
                ->count();
            
            if ($remainingPending == 0) {
                $order->updateStatus(Order::STATUS_REPACKING, 'Semua barang telah dibeli dari pasar');
            } else {
                $order->updateStatus(Order::STATUS_PROCURING, 'Proses belanja berjalan');
            }
            
            DB::commit();
            
            return redirect()->route('orders.show', $order)
                ->with('success', 'Data pembelian berhasil dicatat');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }
    
    public function processRepack(Order $order)
    {
        // Cek status yang valid untuk proses repack
        if ($order->useStockMode()) {
            if ($order->status !== Order::STATUS_CHECKING_STOCK) {
                return back()->with('error', 'Order harus dalam status Cek Stock untuk diproses repack');
            }
        } elseif ($order->useJitMode()) {
            if ($order->status !== Order::STATUS_PROCURING) {
                return back()->with('error', 'Order harus dalam status Belanja untuk diproses repack');
            }
        }
        
        $order->updateStatus(Order::STATUS_REPACKING, 'Tim mulai repack barang');
        
        return redirect()->route('orders.show', $order)
            ->with('success', 'Order masuk ke proses repack');
    }
    
    public function markReady(Order $order)
    {
        if ($order->status !== Order::STATUS_REPACKING) {
            return back()->with('error', 'Order harus dalam status repacking untuk siap kirim');
        }
        
        $order->updateStatus(Order::STATUS_READY, 'Barang siap dikirim');
        
        return redirect()->route('orders.show', $order)
            ->with('success', 'Order siap untuk dikirim');
    }
    
    public function markShipped(Request $request, Order $order)
    {
        if ($order->status !== Order::STATUS_READY) {
            return back()->with('error', 'Order harus dalam status ready untuk dikirim');
        }
        
        $validated = $request->validate([
            'tracking_code' => 'nullable|string|max:100',
        ]);
        
        $order->tracking_code = $validated['tracking_code'];
        $order->save();
        
        $order->updateStatus(Order::STATUS_SHIPPED, 'Barang dikirim ke customer');
        
        return redirect()->route('orders.show', $order)
            ->with('success', 'Order dalam pengiriman');
    }
    
    public function markDelivered(Order $order)
    {
        if ($order->status !== Order::STATUS_SHIPPED) {
            return back()->with('error', 'Order harus dalam status shipped untuk diselesaikan');
        }
        
        $order->updateStatus(Order::STATUS_DELIVERED, 'Barang sampai ke customer');
        
        $customer = $order->user->customer;
        if ($customer) {
            $customer->updateStats();
        }
        
        return redirect()->route('orders.show', $order)
            ->with('success', 'Order selesai');
    }
    
    public function markItemUnavailable(Request $request, OrderItem $item)
    {
        $item->markAsUnavailable();
        
        return response()->json([
            'success' => true,
            'message' => 'Item ditandai tidak tersedia',
            'subtotal' => $item->order->subtotal,
            'total' => $item->order->total,
        ]);
    }
    
    public function invoice(Order $order)
    {
        $order->load('user', 'items.product');
        
        return view('orders.invoice', compact('order'));
    }
    
    public function getStockInfo($productId)
    {
        $stock = ProductStock::where('product_id', $productId)->first();
        
        return response()->json([
            'stock' => $stock ? $stock->quantity : 0,
            'has_stock' => $stock && $stock->quantity > 0,
            'min_stock' => $stock ? $stock->min_stock : 0,
        ]);
    }
}
