<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Customer;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $this->customerProfile($customer);
        $otherCustomer = $this->customer('customer-store-b@example.test');
        $product = Product::create([
            'name' => 'Bayam Customer',
            'category' => 'Sayur',
            'price' => 10000,
            'base_price' => 5000,
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->withSession(['_token' => 'test-token'])
            ->post('/orders', [
                '_token' => 'test-token',
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

    public function test_admin_created_customer_does_not_use_default_password(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/customers', [
                '_token' => 'test-token',
                'name' => 'Customer Password Test',
                'phone' => '081299988877',
                'email' => 'customer-password@example.test',
                'address' => 'Jl. Password Test',
                'customer_type' => 'regular',
                'is_active' => '1',
            ])
            ->assertRedirect('/customers');

        $createdUser = User::where('email', 'customer-password@example.test')->firstOrFail();

        $this->assertFalse(Hash::check('password123', $createdUser->password));
        $this->assertTrue($createdUser->hasRole('customer'));
    }

    public function test_cash_customer_credit_fields_are_normalized_on_store(): void
    {
        $admin = $this->superAdmin('cash-normalize-admin@example.test');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/customers', [
                '_token' => 'test-token',
                'name' => 'Cash Normalize Test',
                'phone' => '081288877766',
                'email' => 'cash-normalize@example.test',
                'customer_type' => 'regular',
                'payment_term' => Customer::PAYMENT_CASH,
                'credit_limit' => 999999,
                'max_outstanding_orders' => 9,
                'credit_status' => Customer::CREDIT_BLOCKED,
                'credit_notes' => 'Should be cleared',
                'is_active' => '1',
            ])
            ->assertRedirect('/customers');

        $customer = Customer::where('email', 'cash-normalize@example.test')->firstOrFail();

        $this->assertSame(Customer::PAYMENT_CASH, $customer->payment_term);
        $this->assertSame(0, $customer->credit_limit);
        $this->assertSame(0, $customer->max_outstanding_orders);
        $this->assertSame(Customer::CREDIT_NORMAL, $customer->credit_status);
        $this->assertNull($customer->credit_notes);
    }

    public function test_blocked_credit_customer_cannot_create_order(): void
    {
        $admin = $this->superAdmin('blocked-admin@example.test');
        $customer = $this->customer('blocked-credit@example.test');
        $this->customerProfile($customer, [
            'payment_term' => Customer::PAYMENT_CREDIT,
            'credit_status' => Customer::CREDIT_BLOCKED,
        ]);
        $product = $this->product('Blocked Credit Product');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->from('/orders/create')
            ->post('/orders', $this->orderPayload($customer, $product))
            ->assertRedirect('/orders/create')
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('orders', [
            'user_id' => $customer->id,
        ]);
    }

    public function test_customer_credit_limit_blocks_order_when_outstanding_would_exceed_limit(): void
    {
        $admin = $this->superAdmin('limit-admin@example.test');
        $customer = $this->customer('limit-credit@example.test');
        $this->customerProfile($customer, [
            'payment_term' => Customer::PAYMENT_CREDIT,
            'credit_limit' => 15000,
        ]);
        $product = $this->product('Limit Credit Product');
        $this->createOrderFor($customer, 'KMGCREDITLIMIT1');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->from('/orders/create')
            ->post('/orders', $this->orderPayload($customer, $product))
            ->assertRedirect('/orders/create')
            ->assertSessionHas('error');

        $this->assertSame(1, Order::where('user_id', $customer->id)->count());
    }

    public function test_cash_customer_ignores_credit_limit_and_credit_status(): void
    {
        $admin = $this->superAdmin('cash-admin@example.test');
        $customer = $this->customer('cash-credit-ignored@example.test');
        $this->customerProfile($customer, [
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_limit' => 1,
            'credit_status' => Customer::CREDIT_BLOCKED,
        ]);
        $product = $this->product('Cash Customer Product');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/orders', $this->orderPayload($customer, $product))
            ->assertRedirect();

        $this->assertSame(1, Order::where('user_id', $customer->id)->count());
    }

    private function customer(string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('customer');

        return $user;
    }

    private function superAdmin(string $email = 'customer-admin@example.test'): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function customerProfile(User $user, array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => '08' . str_pad((string) $user->id, 10, '0', STR_PAD_LEFT),
            'email' => $user->email,
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_limit' => 0,
            'max_outstanding_orders' => 0,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ], $overrides));
    }

    private function product(string $name): Product
    {
        return Product::create([
            'name' => $name,
            'category' => 'Sayur',
            'price' => 10000,
            'base_price' => 5000,
            'is_active' => true,
        ]);
    }

    private function orderPayload(User $customer, Product $product): array
    {
        return [
            '_token' => 'test-token',
            'user_id' => $customer->id,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '06:00-09:00',
            'address' => 'Jl. Credit Test',
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'fulfillment_type' => Order::FULFILLMENT_JIT,
            'payment_method' => Order::PAYMENT_MANUAL,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'packing_fee' => 1000,
            'include_ppn' => false,
            'ppn_rate' => 0,
        ];
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
