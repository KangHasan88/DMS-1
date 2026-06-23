<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnablePackage;
use App\Models\ReturnablePackageMovement;
use App\Models\Unit;
use App\Models\User;
use App\Services\OrderReturnablePackagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReturnablePackagingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_order_posts_returnable_packaging_balance_once(): void
    {
        [$order, $package, $customer] = $this->orderWithReturnableProduct(Product::PACKAGING_FLOW_RETURNABLE, 3);

        $service = app(OrderReturnablePackagingService::class);

        $this->assertSame(1, $service->postDeliveredOrder($order, 1));
        $this->assertSame(0, $service->postDeliveredOrder($order, 1));

        $this->assertDatabaseHas('returnable_package_balances', [
            'returnable_package_id' => $package->id,
            'customer_id' => $customer->id,
            'outstanding_quantity' => 3,
        ]);

        $this->assertSame(1, ReturnablePackageMovement::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->count());
    }

    public function test_sold_packaging_flow_does_not_create_returnable_outstanding(): void
    {
        [$order, $package, $customer] = $this->orderWithReturnableProduct(Product::PACKAGING_FLOW_SOLD, 2);

        $posted = app(OrderReturnablePackagingService::class)->postDeliveredOrder($order, 1);

        $this->assertSame(0, $posted);
        $this->assertDatabaseMissing('returnable_package_balances', [
            'returnable_package_id' => $package->id,
            'customer_id' => $customer->id,
        ]);
    }

    private function orderWithReturnableProduct(string $flow, int $quantity): array
    {
        $user = User::factory()->create([
            'name' => 'Customer Galon',
            'email' => 'customer-galon@example.test',
            'is_active' => true,
        ]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Toko Galon Makmur',
            'phone' => '08135550000' . random_int(10, 99),
            'customer_type' => 'regular',
            'is_active' => true,
        ]);
        $unit = Unit::create([
            'code' => 'GAL',
            'name' => 'Galon',
            'symbol' => 'galon',
            'category' => 'volume',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $package = ReturnablePackage::create([
            'code' => 'GAL19',
            'name' => 'Galon 19L',
            'category' => ReturnablePackage::CATEGORY_GALLON,
            'unit' => 'pcs',
            'replacement_value' => 50000,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Air Mineral Galon',
            'unit_id' => $unit->id,
            'price' => 22000,
            'base_price' => 18000,
            'returnable_package_id' => $package->id,
            'returnable_package_quantity_per_unit' => 1,
            'returnable_package_default_flow' => $flow,
            'is_active' => true,
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'KMGTEST' . random_int(1000, 9999),
            'delivery_date' => now()->toDateString(),
            'delivery_time_slot' => '08:00 - 10:00',
            'address' => 'Jl. Customer No. 1',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'requires_packing' => false,
            'subtotal' => $product->price * $quantity,
            'total' => $product->price * $quantity,
            'grand_total' => $product->price * $quantity,
            'status' => Order::STATUS_READY,
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
            'payment_method' => Order::PAYMENT_MANUAL,
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'discount_amount' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'include_ppn' => false,
            'ppn_rate' => 0,
            'ppn_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'discount' => 0,
            'quantity' => $quantity,
            'subtotal' => $product->price * $quantity,
            'fulfillment_status' => OrderItem::FULFILLMENT_FULFILLED,
        ]);

        return [$order, $package, $customer];
    }
}
