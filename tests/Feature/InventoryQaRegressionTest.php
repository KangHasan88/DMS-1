<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\DirectPurchase;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OutboundFoc;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryQaRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_product_store_uses_unit_id_from_form(): void
    {
        $user = $this->superAdmin();
        $unit = Unit::firstOrCreate(
            ['code' => 'kg'],
            [
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'is_active' => true,
            ]
        );

        $this->actingAs($user)
            ->post('/products', [
                'name' => 'Bayam Test',
                'category' => 'Sayur',
                'unit_id' => $unit->id,
                'price' => 5000,
                'base_price' => 3000,
                'is_active' => '1',
            ])
            ->assertRedirect('/products');

        $this->assertDatabaseHas('products', [
            'name' => 'Bayam Test',
            'unit_id' => $unit->id,
        ]);
    }

    public function test_product_store_uses_master_product_category(): void
    {
        $user = $this->superAdmin('category-admin@example.test');
        $unit = Unit::firstOrCreate(
            ['code' => 'ikat'],
            [
                'name' => 'Ikat',
                'symbol' => 'ikt',
                'is_active' => true,
            ]
        );
        ProductCategory::create([
            'name' => 'Sayur Premium',
            'slug' => 'sayur-premium',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->post('/products', [
                '_token' => 'test-token',
                'name' => 'Kale Premium',
                'category' => 'Sayur Premium',
                'unit_id' => $unit->id,
                'price' => 25000,
                'base_price' => 15000,
                'is_active' => '1',
            ])
            ->assertRedirect('/products');

        $this->assertDatabaseHas('products', [
            'name' => 'Kale Premium',
            'category' => 'Sayur Premium',
        ]);
    }

    public function test_outbound_foc_rolls_back_when_stock_is_not_enough(): void
    {
        $user = $this->superAdmin();
        $product = Product::create([
            'name' => 'Kangkung Test',
            'category' => 'Sayur',
            'price' => 4000,
            'base_price' => 2500,
            'is_active' => true,
        ]);

        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->from('/outbound-focs/create')
            ->post('/outbound-focs', [
                'customer_name' => 'Customer Test',
                'foc_date' => now()->toDateString(),
                'reason' => OutboundFoc::REASON_SAMPLE,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ])
            ->assertRedirect('/outbound-focs/create')
            ->assertSessionHas('error');

        $this->assertSame(1, $product->stock()->first()->quantity);
        $this->assertDatabaseCount('outbound_focs', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_delivery_can_only_be_created_for_ready_orders(): void
    {
        $user = $this->superAdmin();
        $kurir = $this->superAdmin('kurir@example.test');
        $order = $this->createOrder(Order::STATUS_PAID);

        $this->actingAs($user)
            ->from('/deliveries/create')
            ->post('/deliveries', [
                'order_id' => $order->id,
                'kurir_id' => $kurir->id,
            ])
            ->assertRedirect('/deliveries/create')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('deliveries', 0);
        $this->assertSame(Order::STATUS_PAID, $order->refresh()->status);
    }

    public function test_all_registered_controller_methods_exist(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (!str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action);

            if (!method_exists($class, $method)) {
                $missing[] = $route->uri() . ' => ' . $action;
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_stock_transaction_documents_are_append_only(): void
    {
        $this->assertFalse(Route::has('direct-purchases.destroy'));
        $this->assertFalse(Route::has('outbound-focs.destroy'));
        $this->assertFalse(Route::has('outbound-returns.destroy'));
    }

    public function test_product_with_transaction_history_cannot_be_deleted(): void
    {
        $user = $this->superAdmin();
        $product = Product::create([
            'name' => 'Bayam History',
            'category' => 'Sayur',
            'price' => 5000,
            'base_price' => 3000,
            'is_active' => true,
        ]);
        $order = $this->createOrder(Order::STATUS_PENDING_PAYMENT);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 5000,
            'discount' => 0,
            'quantity' => 1,
            'subtotal' => 5000,
            'is_available' => true,
            'fulfillment_status' => OrderItem::FULFILLMENT_PENDING,
        ]);

        $this->actingAs($user)
            ->from('/products')
            ->delete(route('products.destroy', $product))
            ->assertRedirect('/products')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_supplier_with_direct_purchase_history_cannot_be_deleted(): void
    {
        $user = $this->superAdmin();
        $supplier = Supplier::create([
            'name' => 'Supplier History',
            'phone' => '081299900001',
            'category' => 'sayur',
            'is_active' => true,
        ]);

        DirectPurchase::create([
            'invoice_number' => 'DPTEST0001',
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'purchase_date' => now()->toDateString(),
            'subtotal' => 0,
            'total' => 0,
            'purchase_type' => DirectPurchase::TYPE_CASH,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->from('/suppliers')
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect('/suppliers')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_kurir_cannot_access_another_kurir_delivery(): void
    {
        $kurirA = $this->userWithRole('kurir', 'kurir-a@example.test');
        $kurirB = $this->userWithRole('kurir', 'kurir-b@example.test');
        $delivery = Delivery::create([
            'order_id' => $this->createOrder(Order::STATUS_SHIPPED)->id,
            'kurir_id' => $kurirB->id,
            'status' => Delivery::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        $this->actingAs($kurirA)
            ->get(route('deliveries.show', $delivery))
            ->assertForbidden();

        $this->actingAs($kurirA)
            ->post(route('deliveries.update-status', $delivery), [
                'status' => Delivery::STATUS_PICKED_UP,
            ])
            ->assertForbidden();
    }

    public function test_kurir_delivery_index_only_shows_own_deliveries(): void
    {
        $kurirA = $this->userWithRole('kurir', 'kurir-index-a@example.test');
        $kurirB = $this->userWithRole('kurir', 'kurir-index-b@example.test');
        $ownDelivery = Delivery::create([
            'order_id' => $this->createOrder(Order::STATUS_SHIPPED, 'KMGOWN0001')->id,
            'kurir_id' => $kurirA->id,
            'status' => Delivery::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);
        $otherDelivery = Delivery::create([
            'order_id' => $this->createOrder(Order::STATUS_SHIPPED, 'KMGOTHER0001')->id,
            'kurir_id' => $kurirB->id,
            'status' => Delivery::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        $this->actingAs($kurirA)
            ->get('/deliveries')
            ->assertOk()
            ->assertSee(route('deliveries.show', $ownDelivery), false)
            ->assertDontSee(route('deliveries.show', $otherDelivery), false);
    }

    public function test_kurir_delivery_search_does_not_escape_own_scope(): void
    {
        $kurirA = $this->userWithRole('kurir', 'kurir-search-a@example.test');
        $kurirB = $this->userWithRole('kurir', 'kurir-search-b@example.test');
        $ownDelivery = Delivery::create([
            'order_id' => $this->createOrder(Order::STATUS_SHIPPED, 'KMGSEARCHOWN')->id,
            'kurir_id' => $kurirA->id,
            'status' => Delivery::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);
        $otherDelivery = Delivery::create([
            'order_id' => $this->createOrder(Order::STATUS_SHIPPED, 'KMGSEARCHOTHER')->id,
            'kurir_id' => $kurirB->id,
            'status' => Delivery::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        $this->actingAs($kurirA)
            ->get('/deliveries?search=' . urlencode($kurirB->name))
            ->assertOk()
            ->assertDontSee(route('deliveries.show', $otherDelivery), false)
            ->assertDontSee('KMGSEARCHOTHER');

        $this->actingAs($kurirA)
            ->get('/deliveries?search=KMGSEARCHOWN')
            ->assertOk()
            ->assertSee(route('deliveries.show', $ownDelivery), false);
    }

    public function test_delivery_status_change_is_logged(): void
    {
        $kurir = $this->userWithRole('kurir', 'kurir-log@example.test');
        $delivery = Delivery::create([
            'order_id' => $this->createOrder(Order::STATUS_SHIPPED, 'KMGLOG0001')->id,
            'kurir_id' => $kurir->id,
            'status' => Delivery::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        $this->actingAs($kurir)
            ->post(route('deliveries.update-status', $delivery), [
                'status' => Delivery::STATUS_PICKED_UP,
                'notes' => 'Picked up in test',
            ])
            ->assertRedirect(route('deliveries.show', $delivery));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'deliveries',
            'event' => 'status_changed',
            'subject_type' => Delivery::class,
            'subject_id' => $delivery->id,
            'causer_id' => $kurir->id,
        ]);

        $log = ActivityLog::where('log_name', 'deliveries')->where('event', 'status_changed')->first();
        $this->assertSame(Delivery::STATUS_ASSIGNED, $log->properties['old_status']);
        $this->assertSame(Delivery::STATUS_PICKED_UP, $log->properties['new_status']);
    }

    public function test_purchase_order_receive_rejects_items_from_another_po(): void
    {
        $user = $this->superAdmin();
        $supplier = $this->supplier('Supplier PO Scope');
        $product = $this->product('PO Scope Product');
        $poA = $this->purchaseOrder('POSCOPEA0001', $supplier, $user);
        $poB = $this->purchaseOrder('POSCOPEB0001', $supplier, $user);
        $itemA = $this->purchaseOrderItem($poA, $product);
        $itemB = $this->purchaseOrderItem($poB, $product);

        $this->actingAs($user)
            ->from(route('purchase-orders.receive-form', $poA))
            ->post(route('purchase-orders.receive', $poA), [
                'received_date' => now()->toDateString(),
                'items' => [
                    [
                        'id' => $itemB->id,
                        'received_quantity' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('purchase-orders.receive-form', $poA))
            ->assertSessionHas('error');

        $this->assertSame(0, $itemA->fresh()->received_quantity);
        $this->assertSame(0, $itemB->fresh()->received_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_consignment_return_rejects_items_from_another_consignment(): void
    {
        $user = $this->superAdmin();
        $supplier = $this->supplier('Supplier CN Scope');
        $product = $this->product('CN Scope Product');
        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 0,
            'consignment_quantity' => 10,
        ]);
        $consignmentA = $this->consignment('CNSCOPEA0001', $supplier, $user);
        $consignmentB = $this->consignment('CNSCOPEB0001', $supplier, $user);
        $itemA = $this->consignmentItem($consignmentA, $product);
        $itemB = $this->consignmentItem($consignmentB, $product);

        $this->actingAs($user)
            ->from(route('consignments.return-form', $consignmentA))
            ->post(route('consignments.return', $consignmentA), [
                'return_date' => now()->toDateString(),
                'items' => [
                    [
                        'id' => $itemB->id,
                        'return_quantity' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('consignments.return-form', $consignmentA))
            ->assertSessionHas('error');

        $this->assertSame(0, $itemA->fresh()->returned_quantity);
        $this->assertSame(0, $itemB->fresh()->returned_quantity);
        $this->assertSame(10, $product->stock()->first()->consignment_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_proposed_purchase_order_recommends_reorder_from_week_cover(): void
    {
        $user = $this->superAdmin();
        $supplier = Supplier::create([
            'name' => 'Pemasok Proposed',
            'phone' => '081111111111',
            'category' => 'all',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Proposed',
            'category' => 'Sayur',
            'price' => 10000,
            'base_price' => 7000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 5,
            'min_stock' => 8,
        ]);
        $order = $this->createOrder(Order::STATUS_DELIVERED, 'KMGPROP0001');
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 10000,
            'quantity' => 10,
            'subtotal' => 100000,
            'fulfillment_status' => OrderItem::FULFILLMENT_FULFILLED,
        ]);

        $this->actingAs($user)
            ->get('/purchase-orders/proposed?target_weeks=4')
            ->assertOk()
            ->assertSee('Usulan Pembelian')
            ->assertSee('Produk Proposed')
            ->assertSee('Buat PO dari Usulan');

        $this->actingAs($user)
            ->post('/purchase-orders', [
                'supplier_id' => $supplier->id,
                'order_date' => now()->toDateString(),
                'expected_delivery_date' => now()->addDay()->toDateString(),
                'notes' => 'PO test dari usulan',
                'internal_notes' => 'Dibuat dari Usulan Pembelian.',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 5,
                        'price' => 7000,
                        'notes' => 'Stok 5, target 10',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => 35000,
            'total' => 35000,
        ]);
        $this->assertDatabaseHas('purchase_order_items', [
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 7000,
            'subtotal' => 35000,
        ]);
    }

    public function test_proposed_purchase_order_explains_when_no_reorder_is_needed(): void
    {
        $user = $this->superAdmin();
        $product = Product::create([
            'name' => 'Produk Aman',
            'category' => 'Sayur',
            'price' => 10000,
            'base_price' => 7000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 30,
            'min_stock' => 10,
        ]);

        $this->actingAs($user)
            ->get('/purchase-orders/proposed?target_weeks=12')
            ->assertOk()
            ->assertSee('Produk Aktif')
            ->assertSee('Ada Penjualan 30 Hari')
            ->assertSee('Di Bawah Min Stock')
            ->assertSee('Usulan Reorder')
            ->assertSee('Tidak ada produk yang perlu reorder untuk target 12 minggu.')
            ->assertSee('Stok saat ini masih memenuhi min stock dan target week-cover');
    }

    public function test_stock_opname_completes_and_adjusts_product_stock(): void
    {
        $user = $this->superAdmin();
        $product = Product::create([
            'name' => 'Produk Opname',
            'category' => 'Sayur',
            'price' => 10000,
            'base_price' => 7000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 10,
            'min_stock' => 2,
        ]);

        $this->actingAs($user)
            ->post('/stock-opnames', [
                'opname_date' => now()->toDateString(),
                'notes' => 'Opname test',
            ])
            ->assertRedirect('/stock-opnames');

        $stockOpname = StockOpname::with('items')->firstOrFail();
        $item = $stockOpname->items->firstWhere('product_id', $product->id);

        $this->actingAs($user)
            ->put(route('stock-opnames.update', $stockOpname), [
                'items' => [
                    [
                        'id' => $item->id,
                        'counted_quantity' => 7,
                        'notes' => 'Selisih fisik',
                    ],
                ],
            ])
            ->assertRedirect(route('stock-opnames.show', $stockOpname));

        $this->actingAs($user)
            ->post(route('stock-opnames.complete', $stockOpname))
            ->assertRedirect(route('stock-opnames.show', $stockOpname));

        $this->assertSame(StockOpname::STATUS_COMPLETED, $stockOpname->fresh()->status);
        $this->assertSame(7, $product->stock()->first()->quantity);
        $this->assertDatabaseHas('stock_opname_items', [
            'stock_opname_id' => $stockOpname->id,
            'product_id' => $product->id,
            'system_quantity' => 10,
            'counted_quantity' => 7,
            'difference_quantity' => -3,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'source_type' => StockMovement::SOURCE_ADJUSTMENT,
            'source_id' => $stockOpname->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => 3,
            'before_quantity' => 10,
            'after_quantity' => 7,
        ]);
    }

    public function test_stock_opname_requires_all_items_counted_before_complete(): void
    {
        $user = $this->superAdmin();
        $product = Product::create([
            'name' => 'Produk Opname Belum Hitung',
            'category' => 'Sayur',
            'price' => 10000,
            'base_price' => 7000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->actingAs($user)->post('/stock-opnames', [
            'opname_date' => now()->toDateString(),
        ]);

        $stockOpname = StockOpname::firstOrFail();

        $this->actingAs($user)
            ->from(route('stock-opnames.show', $stockOpname))
            ->post(route('stock-opnames.complete', $stockOpname))
            ->assertRedirect(route('stock-opnames.show', $stockOpname))
            ->assertSessionHas('error');

        $this->assertSame(StockOpname::STATUS_DRAFT, $stockOpname->fresh()->status);
        $this->assertSame(10, $product->stock()->first()->quantity);
    }

    private function superAdmin(string $email = 'admin@example.test'): User
    {
        return $this->userWithRole('super-admin', $email);
    }

    private function userWithRole(string $role, string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole($role);

        return $user;
    }

    private function createOrder(string $status, string $orderNumber = 'KMGTEST0001'): Order
    {
        $customer = User::factory()->create();

        return Order::create([
            'user_id' => $customer->id,
            'order_number' => $orderNumber,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '06:00-09:00',
            'address' => 'Jl. Test No. 1',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'subtotal' => 0,
            'total' => 0,
            'status' => $status,
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'discount_amount' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'include_ppn' => false,
            'ppn_rate' => 11,
            'ppn_amount' => 0,
            'grand_total' => 0,
        ]);
    }

    private function supplier(string $name): Supplier
    {
        return Supplier::create([
            'name' => $name,
            'phone' => '0812' . random_int(10000000, 99999999),
            'category' => 'sayur',
            'is_active' => true,
        ]);
    }

    private function product(string $name): Product
    {
        return Product::create([
            'name' => $name,
            'category' => 'Sayur',
            'price' => 5000,
            'base_price' => 3000,
            'is_active' => true,
        ]);
    }

    private function purchaseOrder(string $number, Supplier $supplier, User $user): PurchaseOrder
    {
        return PurchaseOrder::create([
            'po_number' => $number,
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_PENDING,
            'subtotal' => 5000,
            'total' => 5000,
            'created_by' => $user->id,
        ]);
    }

    private function purchaseOrderItem(PurchaseOrder $purchaseOrder, Product $product): PurchaseOrderItem
    {
        return PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'received_quantity' => 0,
            'price' => 2500,
            'subtotal' => 5000,
        ]);
    }

    private function consignment(string $number, Supplier $supplier, User $user): Consignment
    {
        return Consignment::create([
            'cn_number' => $number,
            'supplier_id' => $supplier->id,
            'consignment_date' => now()->toDateString(),
            'status' => Consignment::STATUS_ACTIVE,
            'total_items' => 2,
            'total_value' => 5000,
            'created_by' => $user->id,
        ]);
    }

    private function consignmentItem(Consignment $consignment, Product $product): ConsignmentItem
    {
        return ConsignmentItem::create([
            'consignment_id' => $consignment->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'sold_quantity' => 0,
            'returned_quantity' => 0,
            'price' => 2500,
            'subtotal' => 5000,
        ]);
    }
}
