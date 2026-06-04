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
        ]);

        $this->assertTrue($order->canTransitionTo(Order::STATUS_PAID));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_REPACKING));

        $order->status = Order::STATUS_PAID;

        $this->assertTrue($order->canTransitionTo(Order::STATUS_CHECKING_STOCK));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_PROCURING));
    }

    public function test_jit_order_moves_from_paid_to_procuring_not_stock_checking(): void
    {
        $order = new Order([
            'status' => Order::STATUS_PAID,
            'fulfillment_type' => Order::FULFILLMENT_JIT,
        ]);

        $this->assertTrue($order->canTransitionTo(Order::STATUS_PROCURING));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_CHECKING_STOCK));
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
