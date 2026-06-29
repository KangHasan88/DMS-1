<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStockWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_mode_order_uses_sequential_status_flow(): void
    {
        $order = new Order([
            'status' => Order::STATUS_PENDING_PAYMENT,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_PRE_PAID,
        ]);

        $this->assertTrue($order->canTransitionTo(Order::STATUS_PAID));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_REPACKING));

        $order->status = Order::STATUS_PAID;

        $this->assertTrue($order->canTransitionTo(Order::STATUS_CHECKING_STOCK));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_PROCURING));

        $order->status = Order::STATUS_CHECKING_STOCK;

        $this->assertTrue($order->canTransitionTo(Order::STATUS_PICKING));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_READY));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_REPACKING));
    }

    public function test_jit_order_moves_from_paid_to_procuring_not_stock_checking(): void
    {
        $order = new Order([
            'status' => Order::STATUS_PAID,
            'fulfillment_type' => Order::FULFILLMENT_JIT,
            'payment_timing' => Order::PAYMENT_TIMING_PRE_PAID,
        ]);

        $this->assertTrue($order->canTransitionTo(Order::STATUS_PROCURING));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_CHECKING_STOCK));
    }

    public function test_prepaid_and_postpaid_have_different_payment_positions(): void
    {
        $prePaid = new Order([
            'status' => Order::STATUS_PENDING_PAYMENT,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_PRE_PAID,
        ]);

        $this->assertTrue($prePaid->canTransitionTo(Order::STATUS_PAID));
        $this->assertFalse($prePaid->canTransitionTo(Order::STATUS_CHECKING_STOCK));

        $prePaid->status = Order::STATUS_SHIPPED;

        $this->assertTrue($prePaid->canTransitionTo(Order::STATUS_DELIVERED));
        $this->assertFalse($prePaid->canTransitionTo(Order::STATUS_PAID));

        $postPaid = new Order([
            'status' => Order::STATUS_CHECKING_STOCK,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
        ]);

        $this->assertTrue($postPaid->canTransitionTo(Order::STATUS_PICKING));
        $this->assertFalse($postPaid->canTransitionTo(Order::STATUS_PAID));

        $postPaid->status = Order::STATUS_SHIPPED;

        $this->assertTrue($postPaid->canTransitionTo(Order::STATUS_PAID));
        $this->assertFalse($postPaid->canTransitionTo(Order::STATUS_DELIVERED));

        $postPaid->status = Order::STATUS_PAID;

        $this->assertTrue($postPaid->canTransitionTo(Order::STATUS_DELIVERED));
    }

    public function test_edit_and_delete_rules_depend_on_payment_timing(): void
    {
        $prePaid = new Order([
            'status' => Order::STATUS_PENDING_PAYMENT,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_PRE_PAID,
        ]);

        $this->assertTrue($prePaid->canEditOrder());
        $this->assertTrue($prePaid->canDeleteOrder());

        $prePaid->status = Order::STATUS_PAID;

        $this->assertFalse($prePaid->canEditOrder());
        $this->assertFalse($prePaid->canDeleteOrder());

        $postPaid = new Order([
            'status' => Order::STATUS_CHECKING_STOCK,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
        ]);

        $this->assertTrue($postPaid->canEditOrder());
        $this->assertTrue($postPaid->canDeleteOrder());

        $postPaid->status = Order::STATUS_PICKING;

        $this->assertFalse($postPaid->canEditOrder());
        $this->assertFalse($postPaid->canDeleteOrder());
    }

    public function test_delivery_order_document_availability_follows_payment_and_delivery_stage(): void
    {
        $prePaid = new Order([
            'status' => Order::STATUS_PAID,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_PRE_PAID,
        ]);

        $this->assertFalse($prePaid->canViewDeliveryOrderDocument());

        $prePaid->status = Order::STATUS_READY;

        $this->assertTrue($prePaid->canViewDeliveryOrderDocument());

        $postPaid = new Order([
            'status' => Order::STATUS_PAID,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
        ]);

        $this->assertTrue($postPaid->canViewDeliveryOrderDocument());

        $notReady = new Order([
            'status' => Order::STATUS_CHECKING_STOCK,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
        ]);

        $this->assertFalse($notReady->canViewDeliveryOrderDocument());
    }

    public function test_packing_step_is_conditional_after_picking(): void
    {
        $withoutPacking = new Order([
            'status' => Order::STATUS_PICKING,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
            'requires_packing' => false,
        ]);

        $this->assertTrue($withoutPacking->canTransitionTo(Order::STATUS_READY));
        $this->assertFalse($withoutPacking->canTransitionTo(Order::STATUS_REPACKING));

        $withPacking = new Order([
            'status' => Order::STATUS_PICKING,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
            'requires_packing' => true,
        ]);

        $this->assertTrue($withPacking->canTransitionTo(Order::STATUS_REPACKING));
        $this->assertFalse($withPacking->canTransitionTo(Order::STATUS_READY));
    }

    public function test_unknown_status_is_rejected_before_database_write(): void
    {
        [$order] = $this->createStockOrder(status: Order::STATUS_PICKING);

        $this->assertFalse($order->updateStatus('ready_for_delivery', 'Legacy status should not be saved'));
        $this->assertSame(Order::STATUS_PICKING, $order->refresh()->status);
    }

    public function test_stock_reduction_only_runs_once_for_fulfilled_items(): void
    {
        [$order, $product] = $this->createStockOrder(stockQuantity: 10, itemQuantity: 3);

        $this->assertTrue($order->processStockReduction());

        $this->assertSame(7, $product->stock()->first()->quantity);
        $this->assertSame(OrderItem::FULFILLMENT_FULFILLED, $order->items()->first()->fulfillment_status);
        $this->assertSame(1, StockMovement::where('order_id', $order->id)->where('type', StockMovement::TYPE_OUT)->count());
        $this->assertSame(1, ActivityLog::where('log_name', 'stock')->where('event', 'movement_created')->where('properties->order_id', $order->id)->count());

        $order->refresh()->load('items.product.stock');

        $this->assertTrue($order->processStockReduction());
        $this->assertSame(7, $product->stock()->first()->quantity);
        $this->assertSame(1, StockMovement::where('order_id', $order->id)->where('type', StockMovement::TYPE_OUT)->count());
    }

    public function test_cancelled_stock_order_restores_allocated_stock(): void
    {
        [$order, $product] = $this->createStockOrder(stockQuantity: 10, itemQuantity: 3);

        $order->processStockReduction();
        $order->refresh()->load('items.product.stock');
        $order->restoreAllocatedStock('Order cancelled in test');

        $this->assertSame(10, $product->stock()->first()->quantity);
        $this->assertSame(OrderItem::FULFILLMENT_PENDING, $order->items()->first()->fulfillment_status);
        $this->assertSame(1, StockMovement::where('order_id', $order->id)->where('type', StockMovement::TYPE_IN)->count());
    }

    public function test_shipped_and_delivered_orders_cannot_be_cancelled(): void
    {
        $order = new Order(['fulfillment_type' => Order::FULFILLMENT_STOCK]);

        $order->status = Order::STATUS_SHIPPED;
        $this->assertFalse($order->canTransitionTo(Order::STATUS_CANCELLED));

        $order->status = Order::STATUS_DELIVERED;
        $this->assertFalse($order->canTransitionTo(Order::STATUS_CANCELLED));
    }

    public function test_nominal_discount_is_capped_at_subtotal(): void
    {
        $totals = Order::calculateTotals(
            subtotal: 10000,
            discountType: Order::DISCOUNT_NOMINAL,
            discountValue: 50000,
            shippingType: Order::SHIPPING_FLAT,
            shippingWeight: null,
            shippingDistance: null,
            shippingRate: 5000,
            packingFee: 1000,
            includePpn: true,
            ppnRate: 11
        );

        $this->assertSame(10000, $totals['discount_amount']);
        $this->assertSame(0, $totals['after_discount']);
        $this->assertSame(660, $totals['ppn_amount']);
        $this->assertSame(6660, $totals['grand_total']);
    }

    public function test_shipping_none_ignores_stale_shipping_rate(): void
    {
        $totals = Order::calculateTotals(
            subtotal: 50000,
            discountType: Order::DISCOUNT_NONE,
            discountValue: 0,
            shippingType: Order::SHIPPING_NONE,
            shippingWeight: null,
            shippingDistance: null,
            shippingRate: 15000,
            packingFee: 0,
            includePpn: false,
            ppnRate: 11
        );

        $this->assertSame(0, $totals['shipping_cost']);
        $this->assertSame(50000, $totals['grand_total']);
    }

    public function test_order_recalculate_total_never_goes_negative(): void
    {
        [$order] = $this->createStockOrder(stockQuantity: 10, itemQuantity: 2);
        $order->update([
            'discount_type' => Order::DISCOUNT_NOMINAL,
            'discount_value' => 999999,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'packing_fee' => 0,
            'include_ppn' => false,
        ]);

        $order->refresh()->load('items');
        $order->recalculateTotal();

        $this->assertSame($order->subtotal, $order->discount_amount);
        $this->assertSame(0, $order->grand_total);
        $this->assertSame(0, $order->total);
    }

    public function test_wallet_cannot_be_deducted_below_zero(): void
    {
        $wallet = Wallet::create([
            'user_id' => User::factory()->create()->id,
            'balance' => 10000,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $wallet->deductBalance(10001, null, 'Test overdraw');
    }

    public function test_order_status_change_is_logged(): void
    {
        [$order] = $this->createStockOrder(status: Order::STATUS_PENDING_PAYMENT);

        $this->assertTrue($order->updateStatus(Order::STATUS_PAID, 'Paid in test'));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'orders',
            'event' => 'status_changed',
            'subject_type' => Order::class,
            'subject_id' => $order->id,
        ]);

        $log = ActivityLog::where('log_name', 'orders')
            ->where('event', 'status_changed')
            ->where('subject_id', $order->id)
            ->first();
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $log->properties['old_status']);
        $this->assertSame(Order::STATUS_PAID, $log->properties['new_status']);
    }

    public function test_wallet_balance_changes_are_logged(): void
    {
        $wallet = Wallet::create([
            'user_id' => User::factory()->create()->id,
            'balance' => 10000,
        ]);

        $wallet->addBalance(5000, null, 'Topup test');
        $wallet->deductBalance(3000, null, 'Payment test');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'wallet',
            'event' => 'balance_added',
            'subject_type' => Wallet::class,
            'subject_id' => $wallet->id,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'wallet',
            'event' => 'balance_deducted',
            'subject_type' => Wallet::class,
            'subject_id' => $wallet->id,
        ]);
    }

    public function test_order_document_number_uses_company_and_branch_codes(): void
    {
        $order = new Order([
            'order_number' => 'KMG202606050001',
        ]);
        $order->id = 99;
        $order->created_at = now()->setDate(2026, 6, 5)->setTime(15, 21);

        $this->assertSame('PI-KMGTNG202606050001', $order->documentNumber('PI', 'KMG', 'TNG'));
        $this->assertSame('INV-KMGTNG202606050001', $order->documentNumber('INV', 'KMG', 'TNG'));
        $this->assertSame('DO-KMGTNG202606050001', $order->documentNumber('DO', 'KMG', 'TNG'));
        $this->assertSame('PI-KURMAI202606050001', $order->documentNumber('PI', 'Kurmigo', 'MAIN'));
    }

    public function test_edit_order_keeps_inactive_existing_product_visible_without_offering_it_for_new_rows(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        [$order, $inactiveProduct] = $this->createStockOrder(status: Order::STATUS_PENDING_PAYMENT);
        $order->update(['payment_timing' => Order::PAYMENT_TIMING_PRE_PAID]);
        $inactiveProduct->update(['is_active' => false]);

        $activeProduct = Product::create([
            'name' => 'Produk Aktif Baru',
            'category' => 'Sayur',
            'price' => 7000,
            'base_price' => 4000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $activeProduct->id,
            'quantity' => 20,
        ]);

        $response = $this->actingAs($admin)->get(route('orders.edit', $order));

        $response->assertOk()
            ->assertSee('Bayam')
            ->assertSee('nonaktif')
            ->assertSee('Produk ini sedang nonaktif');

        $this->assertTrue($response->viewData('products')->contains('id', $inactiveProduct->id));
        $this->assertFalse($response->viewData('activeProducts')->contains('id', $inactiveProduct->id));
        $this->assertTrue($response->viewData('activeProducts')->contains('id', $activeProduct->id));
    }

    public function test_procurement_cannot_update_item_from_another_order(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        [$order] = $this->createStockOrder(
            fulfillmentType: Order::FULFILLMENT_JIT,
            status: Order::STATUS_PROCURING
        );
        [$otherOrder] = $this->createStockOrder(
            fulfillmentType: Order::FULFILLMENT_JIT,
            status: Order::STATUS_PROCURING
        );

        $otherItem = $otherOrder->items()->firstOrFail();

        $this->actingAs($admin)
            ->from(route('orders.show', $order))
            ->post(route('orders.process-procurement', $order), [
                'items' => [
                    [
                        'id' => $otherItem->id,
                        'purchase_price' => 12345,
                        'supplier_name' => 'Wrong Supplier',
                        'market_location' => 'Wrong Market',
                    ],
                ],
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');

        $otherItem->refresh();

        $this->assertNull($otherItem->purchase_price);
        $this->assertNull($otherItem->supplier_name);
        $this->assertSame(OrderItem::FULFILLMENT_PENDING, $otherItem->fulfillment_status);
    }

    /**
     * @return array{0: Order, 1: Product}
     */
    private function createStockOrder(
        int $stockQuantity = 10,
        int $itemQuantity = 3,
        string $fulfillmentType = Order::FULFILLMENT_STOCK,
        string $status = Order::STATUS_PAID
    ): array {
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Bayam',
            'category' => 'Sayur',
            'price' => 5000,
            'base_price' => 3000,
            'is_active' => true,
        ]);

        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => $stockQuantity,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMGTEST' . str_pad((string) Order::count(), 4, '0', STR_PAD_LEFT),
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '06:00-09:00',
            'address' => 'Jl. Test No. 1',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'subtotal' => $itemQuantity * 5000,
            'total' => $itemQuantity * 5000,
            'status' => $status,
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'fulfillment_type' => $fulfillmentType,
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'discount_amount' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'include_ppn' => false,
            'ppn_rate' => 11,
            'ppn_amount' => 0,
            'grand_total' => $itemQuantity * 5000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 5000,
            'discount' => 0,
            'quantity' => $itemQuantity,
            'subtotal' => $itemQuantity * 5000,
            'is_available' => true,
            'fulfillment_status' => OrderItem::FULFILLMENT_PENDING,
        ]);

        return [$order->load('items.product.stock'), $product->refresh()];
    }
}
