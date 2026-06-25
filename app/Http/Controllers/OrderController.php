<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\DeliveryTimeSlot;
use App\Services\OrderReturnablePackagingService;
use App\Services\ProductBonusService;
use App\Services\ProductDiscountService;
use App\Services\ProductPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $branchScopeId = $this->currentBranchScopeId();
        $companyBranches = $this->availableCompanyBranches();
        $query = Order::with('user', 'delivery', 'salesperson', 'createdBy');

        if ($this->isCustomerOnly()) {
            $query->where('user_id', Auth::id());
        } elseif ($branchScopeId) {
            $query->where('company_branch_id', $branchScopeId);
        } elseif ($request->filled('company_branch_id')) {
            $query->where('company_branch_id', $request->company_branch_id);
        }
        
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

        if ($request->filled('salesperson_id')) {
            $query->where('salesperson_id', $request->salesperson_id);
        }
        
        if ($request->filled('fulfillment_type')) {
            $query->where('fulfillment_type', $request->fulfillment_type);
        }

        if ($request->filled('payment_timing')) {
            $query->where('payment_timing', $request->payment_timing);
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
        $canFilterBranches = !$this->isCustomerOnly() && !$branchScopeId;
        $orderSources = Order::ORDER_SOURCE_LIST;
        $salespeople = $this->availableSalesOwnersQuery()->with('companyBranch')->orderBy('name')->get();
        $fulfillmentTypes = [
            Order::FULFILLMENT_JIT => 'BLJ (Beli langsung jual)',
            Order::FULFILLMENT_STOCK => 'Stock (Ambil dari Gudang)',
        ];
        $paymentTimings = Order::PAYMENT_TIMING_LIST;
        
        return view('orders.index', compact('orders', 'statuses', 'orderSources', 'fulfillmentTypes', 'paymentTimings', 'companyBranches', 'branchScopeId', 'canFilterBranches', 'salespeople'));
    }

    public function create(Request $request)
    {
        $products = Product::active()->orderBy('name')->get();
        $companyProfile = CompanyProfile::defaultProfile();
        $companyBranches = $this->availableCompanyBranches($companyProfile);
        $defaultCompanyBranchId = $this->defaultCompanyBranchId($companyProfile);
        $branchLocked = (bool) $this->currentBranchScopeId();
        $customers = $this->availableCustomerUsersQuery()
            ->with('customer.activeAddresses', 'customer.activeSalesAssignment.salesperson', 'customer.activeSalesAssignment.salesTerritory')
            ->orderBy('name')
            ->get();
        $salespeople = $this->availableSalesOwnersQuery()
            ->with('companyBranch')
            ->orderBy('name')
            ->get();
        $deliveryTimeSlots = $this->availableDeliveryTimeSlots($defaultCompanyBranchId);
        
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
        $defaultPaymentTiming = $request->get('payment_timing', Order::PAYMENT_TIMING_POST_PAID);
        $orderRequestToken = old('order_request_token', (string) Str::uuid());
        
        return view('orders.create', compact(
            'products', 
            'customers', 
            'productsWithStock',
            'defaultDeliveryDate',
            'defaultFulfillmentType',
            'defaultPaymentTiming',
            'companyBranches',
            'defaultCompanyBranchId',
            'branchLocked',
            'orderRequestToken',
            'salespeople',
            'deliveryTimeSlots'
        ));
    }

    public function store(Request $request)
    {
        if (!$request->filled('order_request_token')) {
            $request->merge(['order_request_token' => (string) Str::uuid()]);
        }

        if ($this->isCustomerOnly()) {
            $request->merge([
                'user_id' => Auth::id(),
                'order_source' => Order::ORDER_SOURCE_APP,
                'discount_type' => Order::DISCOUNT_NONE,
                'discount_value' => 0,
                'requires_packing' => false,
                'packing_fee' => 0,
                'shipping_type' => Order::SHIPPING_NONE,
                'shipping_rate' => 0,
                'include_ppn' => false,
            ]);
        } else {
            $request->merge(['order_source' => Order::ORDER_SOURCE_ADMIN]);
        }

        $validated = $request->validate([
            'order_request_token' => 'required|string|max:64',
            'user_id' => 'required|exists:users,id',
            'company_branch_id' => 'nullable|exists:company_branches,id',
            'delivery_date' => 'required|date|after_or_equal:today',
            'delivery_time_slot' => 'required|string',
            'address' => 'required|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'invoice_address_id' => 'nullable|exists:customer_addresses,id',
            'shipping_address_id' => 'nullable|exists:customer_addresses,id',
            'salesperson_id' => 'nullable|exists:users,id',
            'shipping_same_as_invoice' => 'boolean',
            'requires_packing' => 'nullable|boolean',
            'packing_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'order_source' => 'required|in:' . implode(',', array_keys(Order::ORDER_SOURCE_LIST)),
            'fulfillment_type' => 'required|in:' . Order::FULFILLMENT_STOCK . ',' . Order::FULFILLMENT_JIT,
            'payment_timing' => 'nullable|in:' . Order::PAYMENT_TIMING_PRE_PAID . ',' . Order::PAYMENT_TIMING_POST_PAID,
            'payment_method' => 'nullable|in:gateway,manual,wallet',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.notes' => 'nullable|string',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'required|in:none,percent,nominal',
            'discount_value' => 'required_if:discount_type,percent,nominal|numeric|min:0',
            'shipping_type' => 'nullable|in:none,flat,weight,distance',
            'shipping_weight' => 'required_if:shipping_type,weight|nullable|numeric|min:0',
            'shipping_distance' => 'required_if:shipping_type,distance|nullable|numeric|min:0',
            'shipping_rate' => 'nullable|numeric|min:0',
            'include_ppn' => 'boolean',
            'ppn_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $existingOrder = Order::where('request_token', $validated['order_request_token'])->first();
        if ($existingOrder) {
            return redirect()->route('orders.show', $existingOrder)
                ->with('success', "Order sudah tersimpan. Nomor order: {$existingOrder->order_number}");
        }
        
        DB::beginTransaction();
        
        try {
            $subtotal = 0;
            $orderItems = [];
            $totalItemDiscount = 0;
            $customerUser = User::with('customer')->findOrFail($request->user_id);
            $customer = $customerUser->customer;
            $companyBranchId = $this->resolveCompanyBranchId($request->company_branch_id);
            $this->ensureCustomerMatchesBranch($customer, $companyBranchId);
            $pricing = app(ProductPricingService::class);
            $discounts = app(ProductDiscountService::class);
            
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $price = $pricing->resolvePrice($product, $customer, $companyBranchId);
                
                $itemDiscount = 0;
                if (isset($item['discount_percent']) && $item['discount_percent'] > 0) {
                    $itemDiscount = ($price * $item['discount_percent'] / 100) * $quantity;
                } else {
                    $itemDiscount = $discounts->resolveItemDiscount($product, $price, $quantity, $customer, $companyBranchId)['amount'];
                }
                $totalItemDiscount += $itemDiscount;
                
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
            $shippingType = $request->shipping_type ?: Order::SHIPPING_NONE;
            $shippingRate = (float) ($request->shipping_rate ?? 0);
            $shippingWeight = $request->shipping_weight;
            $shippingDistance = $request->shipping_distance;
            if ($shippingType === Order::SHIPPING_NONE) {
                $shippingRate = 0;
                $shippingWeight = null;
                $shippingDistance = null;
            }
            $shippingTypeForStorage = $shippingType === Order::SHIPPING_NONE ? Order::SHIPPING_FLAT : $shippingType;
            $requiresPacking = $request->boolean('requires_packing');
            $packingFee = $requiresPacking ? ($request->packing_fee ?? 0) : 0;
            $totals = Order::calculateTotals(
                $subtotal,
                $request->discount_type,
                $request->discount_value ?? 0,
                $shippingType,
                $shippingWeight,
                $shippingDistance,
                $shippingRate,
                $packingFee,
                $request->boolean('include_ppn'),
                $request->ppn_rate ?? 11
            );
            
            $paymentTiming = $request->payment_timing ?: Order::PAYMENT_TIMING_POST_PAID;
            $paymentMethod = $request->payment_method;
            if ($request->order_source !== Order::ORDER_SOURCE_APP && !$paymentMethod) {
                $paymentMethod = Order::PAYMENT_MANUAL;
            }

            $salespersonId = $this->resolveSalespersonId(
                $request->filled('salesperson_id')
                    ? (int) $request->salesperson_id
                    : $customer?->activeSalesAssignment?->salesperson_id,
                $request->order_source,
                $companyBranchId
            );
            [$invoiceAddress, $shippingAddress] = $this->resolveOrderAddresses($customer, $request);
            $shippingSameAsInvoice = $request->boolean('shipping_same_as_invoice');
            $shippingSnapshot = $shippingAddress ? $shippingAddress->address : $request->address;
            $creditWarning = null;
            $stockWarning = null;

            if ($customer) {
                $this->ensureCustomerCreditAllowsOrder($customer, (int) $totals['grand_total']);

                if ($customer->usesCreditTerm() && $customer->isCreditWatchlisted()) {
                    $creditWarning = 'Customer masuk watchlist kredit. Order tetap dibuat, mohon cek pembayaran/outstanding.';
                }
            }

            $initialStatus = $paymentTiming === Order::PAYMENT_TIMING_PRE_PAID
                ? Order::STATUS_PENDING_PAYMENT
                : ($request->fulfillment_type === Order::FULFILLMENT_STOCK ? Order::STATUS_CHECKING_STOCK : Order::STATUS_PROCURING);
            
            $order = Order::create([
                'user_id' => $request->user_id,
                'created_by' => Auth::id(),
                'salesperson_id' => $salespersonId,
                'company_branch_id' => $companyBranchId,
                'order_number' => Order::generateOrderNumber(),
                'request_token' => $validated['order_request_token'],
                'delivery_date' => $request->delivery_date,
                'delivery_time_slot' => $request->delivery_time_slot,
                'address' => $shippingSnapshot,
                'invoice_address_id' => $invoiceAddress ? $invoiceAddress->id : null,
                'shipping_address_id' => $shippingAddress ? $shippingAddress->id : null,
                'invoice_address_snapshot' => $invoiceAddress ? $invoiceAddress->address : null,
                'shipping_address_snapshot' => $shippingSnapshot,
                'shipping_recipient_name' => $shippingAddress ? $shippingAddress->recipient_name : null,
                'shipping_recipient_phone' => $shippingAddress ? $shippingAddress->recipient_phone : null,
                'shipping_same_as_invoice' => $shippingSameAsInvoice,
                'latitude' => $shippingAddress && $shippingAddress->latitude ? $shippingAddress->latitude : $request->latitude,
                'longitude' => $shippingAddress && $shippingAddress->longitude ? $shippingAddress->longitude : $request->longitude,
                'delivery_fee' => $totals['shipping_cost'],
                'packing_fee' => $packingFee,
                'requires_packing' => $requiresPacking,
                'subtotal' => $subtotal,
                'total' => $totals['grand_total'],
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value ?? 0,
                'discount_amount' => $totals['discount_amount'],
                'shipping_type' => $shippingTypeForStorage,
                'shipping_weight' => $shippingWeight,
                'shipping_distance' => $shippingDistance,
                'shipping_rate' => $shippingRate,
                'include_ppn' => $request->boolean('include_ppn'),
                'ppn_rate' => $totals['ppn_rate'],
                'ppn_amount' => $totals['ppn_amount'],
                'grand_total' => $totals['grand_total'],
                'status' => $initialStatus,
                'notes' => $request->notes,
                'order_source' => $request->order_source,
                'fulfillment_type' => $request->fulfillment_type,
                'payment_timing' => $paymentTiming,
                'payment_method' => $paymentMethod,
                'admin_notes' => trim('Order dibuat oleh ' . Auth::user()->name . "\n" . ($creditWarning ?? '')),
            ]);
            
            foreach ($orderItems as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }

            if ($paymentTiming === Order::PAYMENT_TIMING_POST_PAID && $order->useStockMode()) {
                $order->load('items.product.stock');
                $order->updateStatus(Order::STATUS_CHECKING_STOCK, 'Mengalokasikan stok');
                $allAvailable = $order->processStockReduction();

                if (!$allAvailable) {
                    $stockWarning = 'Beberapa item tidak tersedia di stock dan telah ditandai untuk refund.';
                }

                if ($allAvailable) {
                    $order->updateStatus(Order::STATUS_PICKING, 'Stok dialokasikan, picking dimulai');
                }
            }
            
            DB::commit();
            
            $message = "Order berhasil dibuat. Nomor order: {$order->order_number}\n";
            $message .= "Subtotal: Rp " . number_format($subtotal, 0, ',', '.') . "\n";
            if ($totals['discount_amount'] > 0) {
                $message .= "Diskon: -Rp " . number_format($totals['discount_amount'], 0, ',', '.') . "\n";
            }
            $message .= "Ongkir: Rp " . number_format($totals['shipping_cost'], 0, ',', '.') . "\n";
            $message .= "Packing: Rp " . number_format($packingFee, 0, ',', '.') . "\n";
            if ($totals['ppn_amount'] > 0) {
                $message .= "PPN: Rp " . number_format($totals['ppn_amount'], 0, ',', '.') . "\n";
            }
            $message .= "Total: Rp " . number_format($totals['grand_total'], 0, ',', '.');
            
            $redirect = redirect()->route('orders.show', $order)
                ->with('success', $message);

            $warningMessage = trim(implode("\n", array_filter([$creditWarning, $stockWarning])));
            if ($warningMessage !== '') {
                $redirect->with('warning', $warningMessage);
            }

            return $redirect;
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat order: ' . $e->getMessage());
        }
    }

    public function show(Order $order)
    {
        $this->authorizeCustomerOrder($order);

        $order->load('user', 'items.product.returnablePackage', 'delivery', 'companyBranch', 'salesperson', 'createdBy');
        $returnablePackagePlan = app(OrderReturnablePackagingService::class)->packagePlan($order);
        $bonusPlan = $order->items
            ->map(function ($item) use ($order) {
                $rule = $item->product
                    ? app(ProductBonusService::class)->resolveBonus($item->product, $item->quantity, $order->user?->customer, $order->company_branch_id)
                    : null;

                return $rule ? [
                    'item' => $item,
                    'rule' => $rule,
                    'bonus_product' => $rule->bonusProduct,
                    'bonus_quantity' => $rule->bonus_quantity,
                ] : null;
            })
            ->filter()
            ->values();
        
        return view('orders.show', compact('order', 'returnablePackagePlan', 'bonusPlan'));
    }

    public function edit(Order $order)
    {
        $this->authorizeCustomerOrder($order);

        if (!$order->canEditOrder()) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order tidak dapat diubah karena sudah selesai/dibatalkan');
        }
        
        $order->loadMissing('items.product');
        $itemProductIds = $order->items->pluck('product_id')->filter()->unique();
        $products = Product::query()
            ->where('is_active', true)
            ->when($itemProductIds->isNotEmpty(), fn ($query) => $query->orWhereIn('id', $itemProductIds))
            ->orderBy('name')
            ->get();
        $activeProducts = $products->where('is_active', true)->values();
        $companyProfile = CompanyProfile::defaultProfile();
        $companyBranches = $this->availableCompanyBranches($companyProfile);
        $defaultCompanyBranchId = $order->company_branch_id ?: $this->defaultCompanyBranchId($companyProfile);
        $branchLocked = (bool) $this->currentBranchScopeId();
        $customers = $this->availableCustomerUsersQuery()->orderBy('name')->get();
        $deliveryTimeSlots = $this->availableDeliveryTimeSlots($defaultCompanyBranchId);
        
        $productsWithStock = [];
        foreach ($products as $product) {
            $stock = ProductStock::where('product_id', $product->id)->first();
            $productsWithStock[$product->id] = [
                'product' => $product,
                'stock' => $stock ? $stock->quantity : 0,
                'has_stock' => $stock && $stock->quantity > 0
            ];
        }
        
        return view('orders.edit', compact('order', 'products', 'activeProducts', 'customers', 'productsWithStock', 'companyBranches', 'defaultCompanyBranchId', 'branchLocked', 'deliveryTimeSlots'));
    }

    public function update(Request $request, Order $order)
    {
        $this->authorizeCustomerOrder($order);

        if (!$order->canEditOrder()) {
            return back()->with('error', 'Order tidak dapat diubah karena sudah selesai/dibatalkan');
        }
        
        $validated = $request->validate([
            'company_branch_id' => 'nullable|exists:company_branches,id',
            'delivery_date' => 'required|date',
            'delivery_time_slot' => 'required|string',
            'address' => 'required|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'requires_packing' => 'nullable|boolean',
            'packing_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.notes' => 'nullable|string',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'required|in:none,percent,nominal',
            'discount_value' => 'required_if:discount_type,percent,nominal|numeric|min:0',
            'shipping_type' => 'nullable|in:none,flat,weight,distance',
            'shipping_weight' => 'required_if:shipping_type,weight|nullable|numeric|min:0',
            'shipping_distance' => 'required_if:shipping_type,distance|nullable|numeric|min:0',
            'shipping_rate' => 'nullable|numeric|min:0',
            'include_ppn' => 'boolean',
            'ppn_rate' => 'nullable|numeric|min:0|max:100',
        ]);
        
        DB::beginTransaction();
        
        try {
            $existingItemIds = $order->items->pluck('id')->toArray();
            $newItemIds = [];
            $subtotal = 0;
            $order->loadMissing('user.customer');
            $companyBranchId = $this->resolveCompanyBranchId($request->company_branch_id);
            $this->ensureCustomerMatchesBranch($order->user?->customer, $companyBranchId);
            $pricing = app(ProductPricingService::class);
            $discounts = app(ProductDiscountService::class);
            
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $price = $pricing->resolvePrice($product, $order->user?->customer, $companyBranchId);
                
                $itemDiscount = 0;
                if (isset($item['discount_percent']) && $item['discount_percent'] > 0) {
                    $itemDiscount = ($price * $item['discount_percent'] / 100) * $quantity;
                } else {
                    $itemDiscount = $discounts->resolveItemDiscount($product, $price, $quantity, $order->user?->customer, $companyBranchId)['amount'];
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
            
            $editDeliveryFee = $request->delivery_fee;
            $shippingType = $request->shipping_type ?: ($editDeliveryFee !== null ? ((float) $editDeliveryFee > 0 ? Order::SHIPPING_FLAT : Order::SHIPPING_NONE) : ($order->shipping_type ?: Order::SHIPPING_NONE));
            $shippingRate = (float) ($request->shipping_rate ?? $editDeliveryFee ?? $order->shipping_rate ?? 0);
            $shippingWeight = $request->shipping_weight;
            $shippingDistance = $request->shipping_distance;
            if ($shippingType === Order::SHIPPING_NONE) {
                $shippingRate = 0;
                $shippingWeight = null;
                $shippingDistance = null;
            }
            $shippingTypeForStorage = $shippingType === Order::SHIPPING_NONE ? Order::SHIPPING_FLAT : $shippingType;
            $requiresPacking = $request->boolean('requires_packing');
            $packingFee = $requiresPacking ? ($request->packing_fee ?? $order->packing_fee ?? 0) : 0;
            $totals = Order::calculateTotals(
                $subtotal,
                $request->discount_type,
                $request->discount_value ?? 0,
                $shippingType,
                $shippingWeight,
                $shippingDistance,
                $shippingRate,
                $packingFee,
                $request->boolean('include_ppn'),
                $request->ppn_rate ?? 11
            );
            
            $order->update([
                'company_branch_id' => $companyBranchId,
                'delivery_date' => $request->delivery_date,
                'delivery_time_slot' => $request->delivery_time_slot,
                'address' => $request->address,
                'notes' => $request->notes,
                'delivery_fee' => $totals['shipping_cost'],
                'packing_fee' => $packingFee,
                'subtotal' => $subtotal,
                'total' => $totals['grand_total'],
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value ?? 0,
                'discount_amount' => $totals['discount_amount'],
                'shipping_type' => $shippingTypeForStorage,
                'shipping_weight' => $shippingWeight,
                'shipping_distance' => $shippingDistance,
                'shipping_rate' => $shippingRate,
                'requires_packing' => $requiresPacking,
                'include_ppn' => $request->boolean('include_ppn'),
                'ppn_rate' => $totals['ppn_rate'],
                'ppn_amount' => $totals['ppn_amount'],
                'grand_total' => $totals['grand_total'],
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
        $this->authorizeCustomerOrder($order);

        if (!$order->canDeleteOrder()) {
            return back()->with('error', 'Order tidak dapat dihapus karena sudah diproses');
        }
        
        $order->delete();
        
        return redirect()->route('orders.index')
            ->with('success', 'Order berhasil dihapus');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeCustomerOrder($order);

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
            $this->authorizeOrderProcessor();

            DB::transaction(function () use ($order, $validated) {
                $order->restoreAllocatedStock($validated['notes'] ?? null);
                $order->updateStatus(Order::STATUS_CANCELLED, $validated['notes'] ?? 'Order dibatalkan');
            });

            return redirect()->route('orders.show', $order)
                ->with('success', 'Order dibatalkan dan stock yang sudah dialokasikan dikembalikan.');
        }
        
        // Handle status PAID
        if ($validated['status'] === Order::STATUS_PAID) {
            $this->authorizeFinanceProcessor();

            $order->updateStatus(Order::STATUS_PAID, $validated['notes'] ?? 'Pembayaran dikonfirmasi');
            
            return redirect()->route('orders.show', $order)
                ->with('success', 'Pembayaran dikonfirmasi.');
        }

        if ($validated['status'] === Order::STATUS_CHECKING_STOCK) {
            $this->authorizeStockAllocator();

            DB::transaction(function () use ($order) {
                $order->updateStatus(Order::STATUS_CHECKING_STOCK, 'Memeriksa ketersediaan stock');
                $allAvailable = $order->processStockReduction();

                if (!$allAvailable) {
                    session()->flash('warning', 'Beberapa item tidak tersedia di stock dan telah ditandai untuk refund.');
                }

                if ($allAvailable) {
                    $order->updateStatus(Order::STATUS_PICKING, 'Stok dialokasikan, picking dimulai');
                }
            });

            return redirect()->route('orders.show', $order)
                ->with('success', 'Stok berhasil dialokasikan.');
        }

        if ($validated['status'] === Order::STATUS_PICKING) {
            $this->authorizePickingProcessor();

            $order->updateStatus(Order::STATUS_PICKING, $validated['notes'] ?? 'Picking dimulai');

            return redirect()->route('orders.show', $order)
                ->with('success', 'Order masuk ke proses picking');
        }

        if ($validated['status'] === Order::STATUS_PROCURING) {
            $this->authorizeProcurementProcessor();

            $order->updateStatus(Order::STATUS_PROCURING, $validated['notes'] ?? 'Menunggu input data belanja BLJ');

            return redirect()->route('orders.show', $order)
                ->with('success', 'Order masuk ke proses belanja BLJ');
        }
        
        // Handle status REPACKING (untuk mode stock yang sudah di-check)
        if ($validated['status'] === Order::STATUS_REPACKING) {
            $this->authorizePackingProcessor();

            // Pastikan sudah melewati picking untuk mode stock
            if ($order->useStockMode() && $order->status !== Order::STATUS_PICKING) {
                return back()->with('error', 'Order harus dalam status Picking terlebih dahulu');
            }
            
            // Untuk mode BLJ, pastikan sudah procuring
            if ($order->useJitMode() && $order->status !== Order::STATUS_PROCURING) {
                return back()->with('error', 'Order harus dalam status Belanja BLJ terlebih dahulu');
            }
            
            $order->updateStatus(Order::STATUS_REPACKING, $validated['notes'] ?? 'Proses repack dimulai');
            return redirect()->route('orders.show', $order)
                ->with('success', 'Order masuk ke proses repack');
        }
        
        // Untuk status lainnya
        $this->authorizeOrderProcessor();

        if ($order->updateStatus($validated['status'], $validated['notes'] ?? null)) {
            return redirect()->route('orders.show', $order)
                ->with('success', 'Status order berhasil diupdate menjadi ' . Order::STATUS_LIST[$validated['status']]);
        }
        
        return back()->with('error', 'Gagal mengupdate status order');
    }

    public function confirmPayment(Request $request, Order $order)
    {
        $this->authorizeCustomerOrder($order);
        $this->authorizeFinanceProcessor();

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        if (!$order->canTransitionTo(Order::STATUS_PAID)) {
            return back()->with('error', 'Pembayaran belum bisa dikonfirmasi pada status ' . $order->status_label);
        }

        $order->updateStatus(Order::STATUS_PAID, $validated['notes'] ?? 'Pembayaran diterima');

        return redirect()->route('orders.show', $order)
            ->with('success', 'Pembayaran dikonfirmasi.');
    }
    
    public function processProcurement(Request $request, Order $order)
    {
        $this->authorizeCustomerOrder($order);
        $this->authorizeProcurementProcessor();

        if ($order->status !== Order::STATUS_PROCURING) {
            return back()->with('error', 'Order harus dalam status Belanja BLJ untuk input data');
        }
        
        if (!$order->useJitMode()) {
            return back()->with('error', 'Order ini menggunakan mode stock, bukan BLJ');
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
                if ($order->requiresPacking()) {
                    $order->updateStatus(Order::STATUS_REPACKING, 'Semua barang telah dibeli dari pasar');
                } else {
                    $order->updateStatus(Order::STATUS_READY, 'Semua barang telah dibeli dari pasar');
                }
            } else {
                $order->updateStatus(Order::STATUS_PROCURING, 'Proses belanja BLJ berjalan');
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
        $this->authorizeCustomerOrder($order);
        $this->authorizePackingProcessor();

        if (!$order->requiresPacking()) {
            $order->updateStatus(Order::STATUS_READY, 'Barang siap dikirim tanpa packing/repack');
            return redirect()->route('orders.show', $order)
                ->with('success', 'Order langsung lanjut ke siap kirim');
        }

        // Mode stock lanjut ke packing/repack lewat proses picking.
        if ($order->useStockMode()) {
            if ($order->status !== Order::STATUS_PICKING) {
                return back()->with('error', 'Order harus dalam status Picking untuk diproses packing/repack');
            }
        } elseif ($order->useJitMode()) {
            if ($order->status !== Order::STATUS_PROCURING) {
                return back()->with('error', 'Order harus dalam status Belanja BLJ untuk diproses packing/repack');
            }
        }
        
        $order->updateStatus(Order::STATUS_REPACKING, 'Tim mulai packing/repack barang');
        
        return redirect()->route('orders.show', $order)
            ->with('success', 'Order masuk ke proses packing/repack');
    }

    public function startPicking(Order $order)
    {
        $this->authorizeCustomerOrder($order);
        $this->authorizePickingProcessor();

        if (!$order->useStockMode()) {
            return back()->with('error', 'Picking hanya berlaku untuk mode stock');
        }

        if ($order->status !== Order::STATUS_CHECKING_STOCK) {
            return back()->with('error', 'Order harus dalam status Alokasi Stok untuk mulai picking');
        }

        $order->updateStatus(Order::STATUS_PICKING, 'Picking dimulai');

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order masuk ke proses picking');
    }

    public function markPicked(Order $order)
    {
        $this->authorizeCustomerOrder($order);
        $this->authorizePickingProcessor();

        if (!$order->useStockMode()) {
            return back()->with('error', 'Picking hanya berlaku untuk mode stock');
        }

        if ($order->status !== Order::STATUS_PICKING) {
            return back()->with('error', 'Order harus dalam status Picking');
        }

        if ($order->requiresPacking()) {
            $order->updateStatus(Order::STATUS_REPACKING, 'Picking selesai, lanjut packing/repack');

            return redirect()->route('orders.show', $order)
                ->with('success', 'Picking selesai. Order masuk ke proses packing/repack');
        }

        $order->updateStatus(Order::STATUS_READY, 'Picking selesai, barang siap dikirim');

        return redirect()->route('orders.show', $order)
            ->with('success', 'Picking selesai. Order siap dikirim');
    }
    
    public function markReady(Order $order)
    {
        $this->authorizeCustomerOrder($order);
        $this->authorizePackingProcessor();

        if ($order->requiresPacking()) {
            if ($order->status !== Order::STATUS_REPACKING) {
                return back()->with('error', 'Order harus dalam status Repack untuk siap kirim');
            }
        } elseif (!in_array($order->status, [Order::STATUS_PICKING, Order::STATUS_PROCURING], true)) {
            return back()->with('error', 'Order harus dalam proses sebelumnya untuk siap kirim');
        }
        
        $order->updateStatus(Order::STATUS_READY, 'Barang siap dikirim');
        
        return redirect()->route('orders.show', $order)
            ->with('success', 'Order siap untuk dikirim');
    }
    
    public function markShipped(Request $request, Order $order)
    {
        $this->authorizeCustomerOrder($order);
        $this->authorizeDeliveryProcessor();

        if ($order->status !== Order::STATUS_READY) {
            return back()->with('error', 'Order harus dalam status Siap Kirim untuk dikirim');
        }
        
        $validated = $request->validate([
            'tracking_code' => 'nullable|string|max:100',
        ]);
        
        $trackingCode = trim((string) ($validated['tracking_code'] ?? ''));
        $order->tracking_code = $trackingCode !== '' ? $trackingCode : null;
        $order->save();
        
        $order->updateStatus(Order::STATUS_SHIPPED, 'Barang dikirim ke customer');
        
        return redirect()->route('orders.show', $order)
            ->with('success', 'Order dalam pengiriman');
    }
    
    public function markDelivered(Order $order)
    {
        $this->authorizeCustomerOrder($order);
        $this->authorizeDeliveryProcessor();

        $isValidStatus = $order->payment_timing === Order::PAYMENT_TIMING_POST_PAID
            ? $order->status === Order::STATUS_PAID
            : $order->status === Order::STATUS_SHIPPED;

        if (!$isValidStatus) {
            return back()->with('error', $order->payment_timing === Order::PAYMENT_TIMING_POST_PAID
                ? 'Order harus dalam status Sudah Bayar untuk diselesaikan'
                : 'Order harus dalam status Dalam Pengiriman untuk diselesaikan');
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
        $this->authorizeCustomerOrder($item->order);
        $this->authorizeOrderProcessor();

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
        return $this->renderOrderDocument($order, config('invoice.document', []));
    }

    public function proformaInvoice(Order $order)
    {
        return $this->renderOrderDocument($order, [
            'title' => 'Proforma Invoice',
            'subtitle' => 'Dokumen estimasi tagihan sebelum invoice final',
        ]);
    }

    public function deliveryOrder(Order $order)
    {
        if (!$order->canViewDeliveryOrderDocument()) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Delivery Order baru bisa dicetak saat order sudah siap kirim atau sudah masuk proses pengiriman.');
        }

        return $this->renderOrderDocument($order, [
            'title' => 'Delivery Order',
            'subtitle' => 'Dokumen serah terima pengiriman barang',
        ]);
    }

    private function renderOrderDocument(Order $order, array $invoiceDocument)
    {
        $this->authorizeCustomerOrder($order);

        $order->load('user', 'items.product', 'companyBranch');

        $companyProfile = CompanyProfile::query()->where('is_active', true)->with('invoiceBranch')->first();
        $invoiceBranchModel = $order->companyBranch ?: ($companyProfile ? $companyProfile->defaultInvoiceBranch() : null);

        $invoiceCompany = $companyProfile
            ? $companyProfile->toInvoiceCompany()
            : config('invoice.company', []);

        $invoiceBranch = $invoiceBranchModel
            ? $invoiceBranchModel->toInvoiceBranch()
            : config('invoice.branch', []);

        return view('orders.invoice', compact('order', 'invoiceCompany', 'invoiceBranch', 'invoiceDocument'));
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

    public function getPriceInfo(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $customer = null;

        if ($request->filled('user_id')) {
            $customer = Customer::where('user_id', $request->user_id)->first();
        }

        $companyBranchId = $request->filled('company_branch_id')
            ? (int) $request->company_branch_id
            : null;
        $price = app(ProductPricingService::class)->resolvePrice($product, $customer, $companyBranchId);
        $quantity = max(1, (float) $request->get('quantity', 1));
        $discount = app(ProductDiscountService::class)->resolveItemDiscount($product, $price, $quantity, $customer, $companyBranchId);
        $discountAmount = (int) ($discount['amount'] ?? 0);
        $discountRule = $discount['rule'] ?? null;
        $bonusRule = app(ProductBonusService::class)->resolveBonus($product, $quantity, $customer, $companyBranchId);

        return response()->json([
            'price' => $price,
            'formatted_price' => 'Rp ' . number_format($price, 0, ',', '.'),
            'auto_discount_amount' => $discountAmount,
            'formatted_auto_discount' => 'Rp ' . number_format($discountAmount, 0, ',', '.'),
            'auto_discount_label' => $discountRule ? $discountRule->discount_label : null,
            'bonus_label' => $bonusRule ? $bonusRule->bonus_label : null,
            'bonus_product_id' => $bonusRule?->bonus_product_id,
            'bonus_quantity' => $bonusRule?->bonus_quantity,
        ]);
    }

    private function isCustomerOnly(): bool
    {
        $user = Auth::user();

        return $user && $user->hasRole('customer') && !$user->hasAnyRole(['super-admin', 'admin', 'manager', 'sales']);
    }

    private function resolveOrderAddresses(?Customer $customer, Request $request): array
    {
        if (!$customer) {
            return [null, null];
        }

        $invoiceAddress = $request->filled('invoice_address_id')
            ? CustomerAddress::where('customer_id', $customer->id)
                ->whereIn('type', [CustomerAddress::TYPE_INVOICE, CustomerAddress::TYPE_BOTH])
                ->find($request->invoice_address_id)
            : null;

        if (!$invoiceAddress) {
            $invoiceAddress = $customer->invoiceAddresses()->where('is_default_invoice', true)->first()
                ?: $customer->invoiceAddresses()->first();
        }

        if ($request->boolean('shipping_same_as_invoice')) {
            $shippingAddress = $invoiceAddress;
        } else {
            $shippingAddress = $request->filled('shipping_address_id')
                ? CustomerAddress::where('customer_id', $customer->id)
                    ->whereIn('type', [CustomerAddress::TYPE_SHIPPING, CustomerAddress::TYPE_BOTH])
                    ->find($request->shipping_address_id)
                : null;

            if (!$shippingAddress) {
                $shippingAddress = $customer->shippingAddresses()->where('is_default_shipping', true)->first()
                    ?: $customer->shippingAddresses()->first();
            }
        }

        return [$invoiceAddress, $shippingAddress];
    }

    private function resolveCompanyBranchId($requestedBranchId): ?int
    {
        if ($branchScopeId = $this->currentBranchScopeId()) {
            return $branchScopeId;
        }

        if ($requestedBranchId && CompanyBranch::whereKey($requestedBranchId)->where('is_active', true)->exists()) {
            return (int) $requestedBranchId;
        }

        return $this->defaultCompanyBranchId();
    }

    private function currentBranchScopeId(): ?int
    {
        return Auth::user()?->scopedCompanyBranchId();
    }

    private function availableCompanyBranches(?CompanyProfile $companyProfile = null)
    {
        $companyProfile = $companyProfile ?: CompanyProfile::defaultProfile();
        $query = $companyProfile->activeBranches();
        $branchScopeId = $this->currentBranchScopeId();

        if ($branchScopeId) {
            $query->whereKey($branchScopeId);
        }

        return $query->get();
    }

    private function defaultCompanyBranchId(?CompanyProfile $companyProfile = null): ?int
    {
        if ($branchScopeId = $this->currentBranchScopeId()) {
            return $branchScopeId;
        }

        $companyProfile = $companyProfile ?: CompanyProfile::query()->where('is_active', true)->first();
        if (!$companyProfile) {
            return null;
        }

        $defaultBranch = $companyProfile->defaultInvoiceBranch();

        return $defaultBranch ? $defaultBranch->id : null;
    }

    private function availableCustomerUsersQuery()
    {
        if ($this->isCustomerOnly()) {
            return User::query()->whereKey(Auth::id());
        }

        $query = User::query()
            ->role('customer')
            ->active()
            ->whereHas('customer');

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->whereHas('customer', function ($customerQuery) use ($branchScopeId) {
                $customerQuery->where('company_branch_id', $branchScopeId);
            });
        }

        return $query;
    }

    private function availableSalesOwnersQuery()
    {
        $query = User::query()
            ->active()
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->whereIn('name', ['sales', 'telesales']);
            });

        if ($branchScopeId = $this->currentBranchScopeId()) {
            $query->where(function ($userQuery) use ($branchScopeId) {
                $userQuery->whereNull('company_branch_id')
                    ->orWhere('company_branch_id', $branchScopeId);
            });
        }

        return $query;
    }

    private function availableDeliveryTimeSlots(?int $companyBranchId = null)
    {
        return DeliveryTimeSlot::active()
            ->forCompanyBranch($companyBranchId ?: $this->currentBranchScopeId())
            ->orderByRaw('company_branch_id is not null')
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();
    }

    private function resolveSalespersonId(?int $salespersonId, string $orderSource, ?int $companyBranchId): ?int
    {
        if (!$salespersonId && in_array($orderSource, [Order::ORDER_SOURCE_SFA, Order::ORDER_SOURCE_TELESALES], true) && Auth::user()?->isSales()) {
            $salespersonId = Auth::id();
        }

        if (!$salespersonId) {
            return null;
        }

        $salesperson = User::query()
            ->active()
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->whereIn('name', ['sales', 'telesales']);
            })
            ->findOrFail($salespersonId);

        if (($branchScopeId = $this->currentBranchScopeId()) && $salesperson->company_branch_id && (int) $salesperson->company_branch_id !== $branchScopeId) {
            throw new \Exception('Sales owner tidak terdaftar pada cabang user.');
        }

        if ($companyBranchId && $salesperson->company_branch_id && (int) $salesperson->company_branch_id !== (int) $companyBranchId) {
            throw new \Exception('Sales owner harus berada pada cabang yang sama dengan order.');
        }

        return $salesperson->id;
    }

    private function ensureCustomerMatchesBranch(?Customer $customer, ?int $companyBranchId): void
    {
        if (!$customer || !$companyBranchId || !$customer->company_branch_id) {
            return;
        }

        if ((int) $customer->company_branch_id !== (int) $companyBranchId) {
            throw new \Exception('Pelanggan tidak terdaftar pada cabang pengirim yang dipilih.');
        }
    }

    private function ensureCustomerCreditAllowsOrder(Customer $customer, int $grandTotal): void
    {
        if (!$customer->usesCreditTerm()) {
            return;
        }

        if ($customer->isCreditBlocked()) {
            throw new \Exception('Customer diblokir untuk order baru oleh kontrol kredit.');
        }

        $limit = (int) ($customer->credit_limit ?? 0);
        if ($limit > 0) {
            $outstanding = $customer->outstandingAmount();
            if (($outstanding + $grandTotal) > $limit) {
                throw new \Exception(
                    'Credit limit customer terlampaui. Limit: Rp ' . number_format($limit, 0, ',', '.') .
                    ', outstanding: Rp ' . number_format($outstanding, 0, ',', '.') .
                    ', order baru: Rp ' . number_format($grandTotal, 0, ',', '.')
                );
            }
        }

        $maxOutstanding = (int) ($customer->max_outstanding_orders ?? 0);
        if ($maxOutstanding > 0 && $customer->outstandingOrdersCount() >= $maxOutstanding) {
            throw new \Exception('Batas outstanding order customer sudah tercapai.');
        }
    }

    private function authorizeCustomerOrder(Order $order): void
    {
        if ($this->isCustomerOnly() && $order->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$this->isCustomerOnly() && ($branchScopeId = $this->currentBranchScopeId()) && (int) $order->company_branch_id !== $branchScopeId) {
            abort(403);
        }
    }

    private function authorizeOrderProcessor(): void
    {
        $user = Auth::user();

        if (!$user || !$user->canProcessFulfillment()) {
            abort(403);
        }
    }

    private function authorizeDeliveryProcessor(): void
    {
        $user = Auth::user();

        if (!$user || !$user->canProcessDeliveries()) {
            abort(403);
        }
    }

    private function authorizeStockAllocator(): void
    {
        $user = Auth::user();

        if (!$user || !$user->canAllocateStock()) {
            abort(403);
        }
    }

    private function authorizePickingProcessor(): void
    {
        $user = Auth::user();

        if (!$user || !$user->canPickOrders()) {
            abort(403);
        }
    }

    private function authorizePackingProcessor(): void
    {
        $user = Auth::user();

        if (!$user || !$user->canPackOrders()) {
            abort(403);
        }
    }

    private function authorizeProcurementProcessor(): void
    {
        $user = Auth::user();

        if (!$user || !$user->canProcessProcurement()) {
            abort(403);
        }
    }

    private function authorizeFinanceProcessor(): void
    {
        $user = Auth::user();

        if (!$user || !$user->canProcessFinance()) {
            abort(403);
        }
    }
}
