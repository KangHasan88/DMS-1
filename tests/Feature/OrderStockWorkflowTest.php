<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
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

    /**
     * @return array{0: Order, 1: Product}
     */
    private function createStockOrder(
        int $stockQuantity = 10,
        int $itemQuantity = 3,
        string $fulfillmentType = Order::FULFILLMENT_STOCK
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
            'status' => Order::STATUS_PAID,
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
