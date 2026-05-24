<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_order_index_only_shows_own_orders(): void
    {
        $customer = $this->customer('customer-a@example.test');
        $otherCustomer = $this->customer('customer-b@example.test');
        $ownOrder = $this->createOrderFor($customer, 'KMGOWNBOUND1');
        $otherOrder = $this->createOrderFor($otherCustomer, 'KMGOTHERBOUND1');

        $this->actingAs($customer)
            ->get('/orders')
            ->assertOk()
            ->assertSee($ownOrder->order_number)
            ->assertDontSee($otherOrder->order_number);
    }

    public function test_customer_cannot_view_another_customer_order(): void
    {
        $customer = $this->customer('customer-view-a@example.test');
        $otherCustomer = $this->customer('customer-view-b@example.test');
        $otherOrder = $this->createOrderFor($otherCustomer, 'KMGOTHERBOUND2');

        $this->actingAs($customer)
            ->get(route('orders.show', $otherOrder))
            ->assertForbidden();
    }

    public function test_customer_store_forces_order_ownership_and_removes_discounts(): void
    {
        $customer = $this->customer('customer-store-a@example.test');
        $otherCustomer = $this->customer('customer-store-b@example.test');
        $product = Product::create([
            'name' => 'Bayam Customer',
            'category' => 'Sayur',
            'price' => 10000,
            'base_price' => 5000,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->post('/orders', [
                'user_id' => $otherCustomer->id,
                'delivery_date' => now()->addDay()->toDateString(),
                'delivery_time_slot' => '06:00-09:00',
                'address' => 'Jl. Customer Test',
                'order_source' => Order::ORDER_SOURCE_ADMIN,
                'fulfillment_type' => Order::FULFILLMENT_JIT,
                'payment_method' => Order::PAYMENT_MANUAL,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'discount_type' => Order::DISCOUNT_NOMINAL,
                'discount_value' => 999999,
                'shipping_type' => Order::SHIPPING_FLAT,
                'shipping_rate' => 0,
                'packing_fee' => 0,
                'include_ppn' => true,
                'ppn_rate' => 100,
            ])
            ->assertRedirect();

        $order = Order::latest('id')->first();

        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame(Order::ORDER_SOURCE_APP, $order->order_source);
        $this->assertSame(Order::DISCOUNT_NONE, $order->discount_type);
        $this->assertSame(0, $order->discount_amount);
        $this->assertSame(1000, $order->packing_fee);
        $this->assertFalse($order->include_ppn);
        $this->assertSame(11000, $order->grand_total);
    }

    private function customer(string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('customer');

        return $user;
    }

    private function createOrderFor(User $user, string $orderNumber): Order
    {
        $product = Product::create([
            'name' => 'Boundary Product ' . $orderNumber,
            'category' => 'Sayur',
            'price' => 10000,
            'base_price' => 5000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '06:00-09:00',
            'address' => 'Jl. Boundary Test',
            'delivery_fee' => 0,
            'packing_fee' => 1000,
            'subtotal' => 10000,
            'total' => 11000,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'order_source' => Order::ORDER_SOURCE_APP,
            'fulfillment_type' => Order::FULFILLMENT_JIT,
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'discount_amount' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'include_ppn' => false,
            'ppn_rate' => 0,
            'ppn_amount' => 0,
            'grand_total' => 11000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Boundary Product',
            'price' => 10000,
            'discount' => 0,
            'quantity' => 1,
            'subtotal' => 10000,
            'is_available' => true,
            'fulfillment_status' => OrderItem::FULFILLMENT_PENDING,
        ]);

        return $order;
    }
}
