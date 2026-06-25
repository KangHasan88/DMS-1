<?php

namespace Tests\Feature;

use App\Models\CompanyBranch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPriceRule;
use App\Models\ProductStock;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingRuleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_order_uses_customer_segment_price_rule(): void
    {
        $admin = $this->userWithRole('admin', 'pricing-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'pricing-customer@example.test');
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Toko Grosir Pricing',
            'phone' => '081234567890',
            'email' => 'pricing-customer@example.test',
            'customer_type' => 'wholesale',
            'payment_term' => Customer::PAYMENT_CREDIT,
            'credit_limit' => 1000000,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Segment Pricing',
            'category' => 'Demo',
            'price' => 10000,
            'base_price' => 6000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 20,
        ]);
        ProductPriceRule::create([
            'product_id' => $product->id,
            'customer_type' => 'wholesale',
            'price' => 8000,
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('orders.store'), [
                'order_request_token' => 'pricing-segment-token',
                'user_id' => $customerUser->id,
                'company_branch_id' => $branch->id,
                'delivery_date' => now()->addDay()->toDateString(),
                'delivery_time_slot' => '06:00-09:00',
                'address' => 'Jl. Pricing No. 1',
                'order_source' => Order::ORDER_SOURCE_ADMIN,
                'fulfillment_type' => Order::FULFILLMENT_STOCK,
                'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
                'payment_method' => Order::PAYMENT_MANUAL,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
                'discount_type' => Order::DISCOUNT_NONE,
                'discount_value' => 0,
                'shipping_type' => Order::SHIPPING_NONE,
                'include_ppn' => false,
            ])
            ->assertRedirect();

        $item = OrderItem::where('product_id', $product->id)->firstOrFail();
        $order = Order::firstOrFail();

        $this->assertSame($customer->user_id, $order->user_id);
        $this->assertSame(8000, $item->price);
        $this->assertSame(16000, $item->subtotal);
        $this->assertSame(16000, $order->subtotal);
    }

    public function test_price_rule_page_can_create_customer_specific_rule(): void
    {
        $admin = $this->userWithRole('admin', 'pricing-page-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'pricing-page-customer@example.test');
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Harga Khusus',
            'phone' => '081234567891',
            'email' => 'pricing-page-customer@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Harga Khusus',
            'category' => 'Demo',
            'price' => 12000,
            'base_price' => 7000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('product-price-rules.index'))
            ->assertOk()
            ->assertSee('Daftar Harga');

        $this->actingAs($admin)
            ->post(route('product-price-rules.store'), [
                'product_id' => $product->id,
                'customer_id' => $customer->id,
                'company_branch_id' => $branch->id,
                'price' => 9000,
                'starts_at' => now()->toDateString(),
                'notes' => 'Harga khusus customer',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_price_rules', [
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'company_branch_id' => $branch->id,
            'price' => 9000,
            'is_active' => true,
        ]);
    }

    private function userWithRole(string $role, string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole($role);

        return $user;
    }
}
