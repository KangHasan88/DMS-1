<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Customer;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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

    public function test_admin_order_quick_search_matches_order_customer_email_and_phone(): void
    {
        $admin = $this->superAdmin('order-search-admin@example.test');
        $customer = $this->customer('siti-search@example.test');
        $customer->update([
            'name' => 'Siti Search Customer',
            'phone' => '081234567890',
        ]);
        $matchedOrder = $this->createOrderFor($customer, 'KMGSEARCH001');

        $otherCustomer = $this->customer('other-search@example.test');
        $otherCustomer->update([
            'name' => 'Budi Other Customer',
            'phone' => '089999999999',
        ]);
        $otherOrder = $this->createOrderFor($otherCustomer, 'KMGSEARCH002');

        $this->actingAs($admin)
            ->get(route('orders.index', ['search' => '081234567890']))
            ->assertOk()
            ->assertSee($matchedOrder->order_number)
            ->assertDontSee($otherOrder->order_number);

        $this->actingAs($admin)
            ->get(route('orders.index', ['search' => 'siti-search@example.test']))
            ->assertOk()
            ->assertSee($matchedOrder->order_number)
            ->assertDontSee($otherOrder->order_number);
    }

    public function test_customer_maps_reverse_geocode_returns_address_from_coordinates(): void
    {
        config(['services.google_maps.key' => 'google-test-key']);

        Http::fake([
            'maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'OK',
                'results' => [
                    [
                        'formatted_address' => 'Jalan Jenderal Sudirman Kav. 59 5, RT.5/RW.3, Senayan, Jakarta Selatan 12110',
                    ],
                ],
            ], 200),
        ]);

        $admin = $this->superAdmin('maps-geocode-admin@example.test');

        $this->actingAs($admin)
            ->getJson(route('customers.maps.reverse-geocode', [
                'latitude' => '-6.2255839',
                'longitude' => '106.7955787',
            ]))
            ->assertOk()
            ->assertJson([
                'address' => 'Jalan Jenderal Sudirman Kav. 59 5, RT.5/RW.3, Senayan, Jakarta Selatan 12110',
            ]);
    }

    public function test_customer_maps_reverse_geocode_does_not_fallback_to_non_google_address(): void
    {
        config(['services.google_maps.key' => null]);

        Http::fake();

        $admin = $this->superAdmin('maps-no-key-admin@example.test');

        $this->actingAs($admin)
            ->getJson(route('customers.maps.reverse-geocode', [
                'latitude' => '-6.2255839',
                'longitude' => '106.7955787',
            ]))
            ->assertNotFound()
            ->assertJson([
                'message' => 'Alamat detail Google Maps belum tersedia. Aktifkan GOOGLE_MAPS_API_KEY atau isi alamat manual.',
            ]);
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
        $this->assertSame(0, $order->packing_fee);
        $this->assertFalse($order->include_ppn);
        $this->assertSame(10000, $order->grand_total);
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

    public function test_duplicate_order_submit_with_same_request_token_returns_existing_order(): void
    {
        $admin = $this->superAdmin('idempotent-admin@example.test');
        $customer = $this->customer('idempotent-customer@example.test');
        $this->customerProfile($customer);
        $product = $this->product('Idempotent Product');
        $payload = $this->orderPayload($customer, $product, 'order-token-001');

        $firstResponse = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/orders', $payload);

        $firstResponse->assertRedirect();
        $firstOrder = Order::where('request_token', 'order-token-001')->firstOrFail();

        $secondResponse = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/orders', $payload);

        $secondResponse->assertRedirect(route('orders.show', $firstOrder));
        $this->assertSame(1, Order::where('request_token', 'order-token-001')->count());
        $this->assertSame(1, Order::where('user_id', $customer->id)->count());
    }

    public function test_order_store_defaults_optional_save_fields_when_inputs_are_missing(): void
    {
        $admin = $this->superAdmin('save-default-admin@example.test');
        $customer = $this->customer('save-default-customer@example.test');
        $this->customerProfile($customer);
        $product = $this->product('Save Default Product');
        $payload = $this->orderPayload($customer, $product, 'save-default-token-001');

        unset($payload['payment_timing'], $payload['shipping_type'], $payload['shipping_rate']);
        $payload['requires_packing'] = 0;
        $payload['include_ppn'] = false;

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/orders', $payload)
            ->assertRedirect();

        $order = Order::where('request_token', 'save-default-token-001')->firstOrFail();

        $this->assertSame(Order::PAYMENT_TIMING_POST_PAID, $order->payment_timing);
        $this->assertSame(Order::SHIPPING_FLAT, $order->shipping_type);
        $this->assertSame(0, (int) $order->delivery_fee);
        $this->assertFalse($order->requires_packing);
        $this->assertSame(0, (int) $order->packing_fee);
        $this->assertSame(10000, (int) $order->grand_total);
    }

    public function test_branch_admin_only_sees_customers_from_assigned_branch_on_order_create(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $customerA = $this->customer('branch-a-customer@example.test');
        $customerB = $this->customer('branch-b-customer@example.test');
        $customerA->update(['name' => 'Customer Cabang A']);
        $customerB->update(['name' => 'Customer Cabang B']);
        $this->customerProfile($customerA, ['company_branch_id' => $branchA->id, 'name' => 'Customer Cabang A']);
        $this->customerProfile($customerB, ['company_branch_id' => $branchB->id, 'name' => 'Customer Cabang B']);
        $admin = User::factory()->create([
            'email' => 'branch-admin@example.test',
            'company_branch_id' => $branchA->id,
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('orders.create'))
            ->assertOk()
            ->assertSee('Customer Cabang A')
            ->assertDontSee('Customer Cabang B');
    }

    public function test_order_store_rejects_customer_from_different_branch(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->superAdmin('branch-mismatch-admin@example.test');
        $customer = $this->customer('branch-mismatch-customer@example.test');
        $this->customerProfile($customer, ['company_branch_id' => $branchB->id]);
        $product = $this->product('Branch Mismatch Product');
        $payload = $this->orderPayload($customer, $product, 'branch-mismatch-token-001');
        $payload['company_branch_id'] = $branchA->id;

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->from(route('orders.create'))
            ->post('/orders', $payload)
            ->assertRedirect(route('orders.create'))
            ->assertSessionHas('error', 'Gagal membuat order: Pelanggan tidak terdaftar pada cabang pengirim yang dipilih.');

        $this->assertDatabaseMissing('orders', [
            'request_token' => 'branch-mismatch-token-001',
        ]);
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

    private function twoCompanyBranches(): array
    {
        $company = CompanyProfile::defaultProfile();
        $branchA = $company->defaultInvoiceBranch();
        $branchB = CompanyBranch::create([
            'company_profile_id' => $company->id,
            'name' => 'Cabang Dua',
            'code' => 'DUB',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        return [$branchA, $branchB];
    }

    private function orderPayload(User $customer, Product $product, string $requestToken = 'test-order-token'): array
    {
        return [
            '_token' => 'test-token',
            'order_request_token' => $requestToken,
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
