<?php

namespace Tests\Feature;

use App\Models\CompanyBranch;
use App\Models\ApprovalRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBonusRule;
use App\Models\ProductDiscountRule;
use App\Models\ProductPriceHistory;
use App\Models\ProductPriceRule;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
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
        $order = Order::where('user_id', $customerUser->id)->latest('id')->firstOrFail();

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
                'customer_ids' => [$customer->id],
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

    public function test_price_rule_page_can_create_rules_for_multiple_customers(): void
    {
        $admin = $this->userWithRole('admin', 'price-multi-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $firstUser = $this->userWithRole('customer', 'price-multi-first@example.test');
        $secondUser = $this->userWithRole('customer', 'price-multi-second@example.test');
        $firstCustomer = Customer::create([
            'user_id' => $firstUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Harga Pertama',
            'phone' => '081234567896',
            'email' => 'price-multi-first@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $secondCustomer = Customer::create([
            'user_id' => $secondUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Harga Kedua',
            'phone' => '081234567897',
            'email' => 'price-multi-second@example.test',
            'customer_type' => 'wholesale',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Harga Multi Customer',
            'category' => 'Demo',
            'price' => 20000,
            'base_price' => 12000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('product-price-rules.store'), [
                'product_id' => $product->id,
                'customer_ids' => [$firstCustomer->id, $secondCustomer->id],
                'customer_type' => 'wholesale',
                'company_branch_id' => $branch->id,
                'price' => 16000,
                'starts_at' => now()->toDateString(),
                'notes' => 'Harga khusus dua customer',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_price_rules', [
            'product_id' => $product->id,
            'customer_id' => $firstCustomer->id,
            'customer_type' => null,
            'price' => 16000,
        ]);
        $this->assertDatabaseHas('product_price_rules', [
            'product_id' => $product->id,
            'customer_id' => $secondCustomer->id,
            'customer_type' => null,
            'price' => 16000,
        ]);
    }

    public function test_product_price_info_endpoint_resolves_customer_price(): void
    {
        $admin = $this->userWithRole('admin', 'pricing-endpoint-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'pricing-endpoint-customer@example.test');
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Endpoint Harga',
            'phone' => '081234567892',
            'email' => 'pricing-endpoint-customer@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Endpoint Harga',
            'category' => 'Demo',
            'price' => 15000,
            'base_price' => 8000,
            'is_active' => true,
        ]);
        ProductPriceRule::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'price' => 11000,
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('products.price-info', [
                'product' => $product,
                'user_id' => $customerUser->id,
                'company_branch_id' => $branch->id,
            ]))
            ->assertOk()
            ->assertJson([
                'price' => 11000,
                'formatted_price' => 'Rp 11.000',
            ]);
    }

    public function test_product_price_info_endpoint_returns_auto_discount_preview(): void
    {
        $admin = $this->userWithRole('admin', 'discount-preview-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'discount-preview-customer@example.test');
        Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Preview Diskon',
            'phone' => '081234567899',
            'email' => 'discount-preview-customer@example.test',
            'customer_type' => 'wholesale',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Preview Diskon',
            'category' => 'Demo',
            'price' => 10000,
            'base_price' => 6000,
            'is_active' => true,
        ]);
        ProductDiscountRule::create([
            'product_id' => $product->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'discount_type' => ProductDiscountRule::TYPE_PERCENT,
            'discount_value' => 10,
            'min_quantity' => 2,
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('products.price-info', [
                'product' => $product,
                'user_id' => $customerUser->id,
                'company_branch_id' => $branch->id,
                'quantity' => 2,
            ]))
            ->assertOk()
            ->assertJson([
                'price' => 10000,
                'auto_discount_amount' => 2000,
                'formatted_auto_discount' => 'Rp 2.000',
                'auto_discount_label' => '10%',
            ]);
    }

    public function test_bonus_rule_page_can_create_segment_rule(): void
    {
        $admin = $this->userWithRole('admin', 'bonus-page-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'bonus-page-customer@example.test');
        Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Bonus Page',
            'phone' => '081234567897',
            'email' => 'bonus-page-customer@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $triggerProduct = Product::create([
            'name' => 'Produk Trigger Bonus',
            'category' => 'Demo',
            'price' => 25000,
            'base_price' => 15000,
            'is_active' => true,
        ]);
        $bonusProduct = Product::create([
            'name' => 'Produk Bonus Rule',
            'category' => 'Demo',
            'price' => 5000,
            'base_price' => 2500,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('product-bonus-rules.index'))
            ->assertOk()
            ->assertSee('Aturan Bonus')
            ->assertSee('Pilih Customer Khusus')
            ->assertSee('Pilih Customer')
            ->assertSee('bonus-customer-picker-modal')
            ->assertSee('bonus-company-branch-id')
            ->assertSee('bonus-customer-branch-note')
            ->assertSee('bonus-customer-visible-count')
            ->assertSee('bonus-customer-empty-state')
            ->assertSee('data-customer-branch-id')
            ->assertDontSee('id="bonus-customer-ids"', false);

        $this->actingAs($admin)
            ->post(route('product-bonus-rules.store'), [
                'trigger_product_id' => $triggerProduct->id,
                'bonus_product_id' => $bonusProduct->id,
                'customer_type' => 'wholesale',
                'company_branch_id' => $branch->id,
                'min_quantity' => 3,
                'bonus_quantity' => 1,
                'starts_at' => now()->toDateString(),
                'notes' => 'Promo bonus segment',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_bonus_rules', [
            'trigger_product_id' => $triggerProduct->id,
            'bonus_product_id' => $bonusProduct->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'min_quantity' => 3,
            'bonus_quantity' => 1,
            'is_active' => true,
        ]);
    }

    public function test_bonus_rule_rejects_customer_from_different_selected_branch(): void
    {
        $admin = $this->userWithRole('admin', 'bonus-branch-admin@example.test');
        $mainBranch = CompanyBranch::where('is_active', true)->firstOrFail();
        $otherBranch = CompanyBranch::create([
            'company_profile_id' => $mainBranch->company_profile_id,
            'name' => 'Cabang Bonus Lain',
            'code' => 'CBL',
            'is_active' => true,
        ]);
        $customerUser = $this->userWithRole('customer', 'bonus-branch-customer@example.test');
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $otherBranch->id,
            'name' => 'Customer Bonus Cabang Lain',
            'phone' => '081234567898',
            'email' => 'bonus-branch-customer@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $triggerProduct = Product::create([
            'name' => 'Produk Trigger Bonus Cabang',
            'category' => 'Demo',
            'price' => 25000,
            'base_price' => 15000,
            'is_active' => true,
        ]);
        $bonusProduct = Product::create([
            'name' => 'Produk Bonus Cabang',
            'category' => 'Demo',
            'price' => 5000,
            'base_price' => 2500,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('product-bonus-rules.index'))
            ->post(route('product-bonus-rules.store'), [
                'trigger_product_id' => $triggerProduct->id,
                'bonus_product_id' => $bonusProduct->id,
                'customer_ids' => [$customer->id],
                'company_branch_id' => $mainBranch->id,
                'min_quantity' => 3,
                'bonus_quantity' => 1,
                'starts_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('product-bonus-rules.index'))
            ->assertSessionHasErrors('customer_ids');

        $this->assertDatabaseMissing('product_bonus_rules', [
            'trigger_product_id' => $triggerProduct->id,
            'bonus_product_id' => $bonusProduct->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_product_price_info_endpoint_returns_bonus_preview(): void
    {
        $admin = $this->userWithRole('admin', 'bonus-preview-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'bonus-preview-customer@example.test');
        Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Preview Bonus',
            'phone' => '081234567900',
            'email' => 'bonus-preview-customer@example.test',
            'customer_type' => 'wholesale',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $triggerProduct = Product::create([
            'name' => 'Produk Trigger Preview Bonus',
            'category' => 'Demo',
            'price' => 30000,
            'base_price' => 18000,
            'is_active' => true,
        ]);
        $bonusProduct = Product::create([
            'name' => 'Produk Bonus Preview',
            'category' => 'Demo',
            'price' => 6000,
            'base_price' => 3000,
            'is_active' => true,
        ]);
        ProductBonusRule::create([
            'trigger_product_id' => $triggerProduct->id,
            'bonus_product_id' => $bonusProduct->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'min_quantity' => 3,
            'bonus_quantity' => 1,
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('products.price-info', [
                'product' => $triggerProduct,
                'user_id' => $customerUser->id,
                'company_branch_id' => $branch->id,
                'quantity' => 3,
            ]))
            ->assertOk()
            ->assertJson([
                'price' => 30000,
                'bonus_product_id' => $bonusProduct->id,
                'bonus_quantity' => 1,
            ]);

        $this->assertStringContainsString('Produk Bonus Preview', $response->json('bonus_label'));
    }

    public function test_bonus_rule_rejects_overlapping_active_rule_for_same_scope(): void
    {
        $admin = $this->userWithRole('admin', 'bonus-overlap-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $triggerProduct = Product::create([
            'name' => 'Produk Bonus Overlap',
            'category' => 'Demo',
            'price' => 21000,
            'base_price' => 11000,
            'is_active' => true,
        ]);
        $firstBonusProduct = Product::create([
            'name' => 'Bonus Pertama',
            'category' => 'Demo',
            'price' => 5000,
            'base_price' => 2500,
            'is_active' => true,
        ]);
        $secondBonusProduct = Product::create([
            'name' => 'Bonus Kedua',
            'category' => 'Demo',
            'price' => 7000,
            'base_price' => 3500,
            'is_active' => true,
        ]);
        ProductBonusRule::create([
            'trigger_product_id' => $triggerProduct->id,
            'bonus_product_id' => $firstBonusProduct->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'min_quantity' => 2,
            'bonus_quantity' => 1,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addWeek()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('product-bonus-rules.index'))
            ->post(route('product-bonus-rules.store'), [
                'trigger_product_id' => $triggerProduct->id,
                'bonus_product_id' => $secondBonusProduct->id,
                'customer_type' => 'wholesale',
                'company_branch_id' => $branch->id,
                'min_quantity' => 2,
                'bonus_quantity' => 1,
                'starts_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('product-bonus-rules.index'))
            ->assertSessionHasErrors('starts_at');

        $this->assertSame(1, ProductBonusRule::where('trigger_product_id', $triggerProduct->id)->count());
    }

    public function test_discount_rule_replace_closes_old_rule_and_creates_new_rule(): void
    {
        $admin = $this->userWithRole('admin', 'discount-replace-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $product = Product::create([
            'name' => 'Produk Diskon Replace',
            'category' => 'Demo',
            'price' => 20000,
            'base_price' => 12000,
            'is_active' => true,
        ]);
        $oldRule = ProductDiscountRule::create([
            'product_id' => $product->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'discount_type' => ProductDiscountRule::TYPE_PERCENT,
            'discount_value' => 10,
            'min_quantity' => 2,
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ]);
        $newStart = now()->addDays(7)->toDateString();

        $this->actingAs($admin)
            ->post(route('product-discount-rules.replace', $oldRule), [
                'discount_type' => ProductDiscountRule::TYPE_PERCENT,
                'discount_value' => 12,
                'starts_at' => $newStart,
                'notes' => 'Naik diskon periode baru',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_discount_rules', [
            'id' => $oldRule->id,
            'ends_at' => now()->addDays(6)->startOfDay()->toDateTimeString(),
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('product_discount_rules', [
            'product_id' => $product->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'discount_type' => ProductDiscountRule::TYPE_PERCENT,
            'discount_value' => 12,
            'min_quantity' => 2,
            'starts_at' => now()->addDays(7)->startOfDay()->toDateTimeString(),
            'is_active' => true,
        ]);
    }

    public function test_bonus_rule_replace_closes_old_rule_and_creates_new_rule(): void
    {
        $admin = $this->userWithRole('admin', 'bonus-replace-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $triggerProduct = Product::create([
            'name' => 'Produk Trigger Replace Bonus',
            'category' => 'Demo',
            'price' => 25000,
            'base_price' => 15000,
            'is_active' => true,
        ]);
        $oldBonusProduct = Product::create([
            'name' => 'Produk Bonus Replace Lama',
            'category' => 'Demo',
            'price' => 5000,
            'base_price' => 2500,
            'is_active' => true,
        ]);
        $newBonusProduct = Product::create([
            'name' => 'Produk Bonus Replace Baru',
            'category' => 'Demo',
            'price' => 7000,
            'base_price' => 3000,
            'is_active' => true,
        ]);
        $oldRule = ProductBonusRule::create([
            'trigger_product_id' => $triggerProduct->id,
            'bonus_product_id' => $oldBonusProduct->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'min_quantity' => 3,
            'bonus_quantity' => 1,
            'starts_at' => now()->toDateString(),
            'is_active' => true,
        ]);
        $newStart = now()->addDays(7)->toDateString();

        $this->actingAs($admin)
            ->post(route('product-bonus-rules.replace', $oldRule), [
                'bonus_product_id' => $newBonusProduct->id,
                'bonus_quantity' => 2,
                'starts_at' => $newStart,
                'notes' => 'Bonus periode baru',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_bonus_rules', [
            'id' => $oldRule->id,
            'ends_at' => now()->addDays(6)->startOfDay()->toDateTimeString(),
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('product_bonus_rules', [
            'trigger_product_id' => $triggerProduct->id,
            'bonus_product_id' => $newBonusProduct->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'min_quantity' => 3,
            'bonus_quantity' => 2,
            'starts_at' => now()->addDays(7)->startOfDay()->toDateTimeString(),
            'is_active' => true,
        ]);
    }

    public function test_discount_rule_replace_rejects_past_start_date(): void
    {
        $admin = $this->userWithRole('admin', 'discount-replace-past-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $product = Product::create([
            'name' => 'Produk Diskon Replace Past',
            'category' => 'Demo',
            'price' => 20000,
            'base_price' => 12000,
            'is_active' => true,
        ]);
        $oldRule = ProductDiscountRule::create([
            'product_id' => $product->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'discount_type' => ProductDiscountRule::TYPE_PERCENT,
            'discount_value' => 10,
            'min_quantity' => 2,
            'starts_at' => now()->subDays(10)->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('product-discount-rules.index'))
            ->post(route('product-discount-rules.replace', $oldRule), [
                'discount_type' => ProductDiscountRule::TYPE_PERCENT,
                'discount_value' => 12,
                'starts_at' => now()->subDay()->toDateString(),
            ])
            ->assertRedirect(route('product-discount-rules.index'))
            ->assertSessionHasErrors('starts_at');

        $this->assertSame(1, ProductDiscountRule::where('product_id', $product->id)->count());
    }

    public function test_expired_discount_rule_cannot_be_reactivated(): void
    {
        $admin = $this->userWithRole('admin', 'discount-expired-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $product = Product::create([
            'name' => 'Produk Diskon Expired',
            'category' => 'Demo',
            'price' => 20000,
            'base_price' => 12000,
            'is_active' => true,
        ]);
        $rule = ProductDiscountRule::create([
            'product_id' => $product->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'discount_type' => ProductDiscountRule::TYPE_PERCENT,
            'discount_value' => 10,
            'min_quantity' => 2,
            'starts_at' => now()->subDays(10)->toDateString(),
            'ends_at' => now()->subDay()->toDateString(),
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('product-discount-rules.index'))
            ->assertOk()
            ->assertSee('Expired')
            ->assertSee('disabled title="Periode sudah lewat"', false);

        $this->actingAs($admin)
            ->from(route('product-discount-rules.index'))
            ->post(route('product-discount-rules.toggle-status', $rule))
            ->assertRedirect(route('product-discount-rules.index'))
            ->assertSessionHasErrors('status');

        $this->assertFalse($rule->fresh()->is_active);
    }

    public function test_bonus_rule_replace_rejects_past_start_date(): void
    {
        $admin = $this->userWithRole('admin', 'bonus-replace-past-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $triggerProduct = Product::create([
            'name' => 'Produk Trigger Replace Past',
            'category' => 'Demo',
            'price' => 25000,
            'base_price' => 15000,
            'is_active' => true,
        ]);
        $bonusProduct = Product::create([
            'name' => 'Produk Bonus Replace Past',
            'category' => 'Demo',
            'price' => 5000,
            'base_price' => 2500,
            'is_active' => true,
        ]);
        $oldRule = ProductBonusRule::create([
            'trigger_product_id' => $triggerProduct->id,
            'bonus_product_id' => $bonusProduct->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'min_quantity' => 3,
            'bonus_quantity' => 1,
            'starts_at' => now()->subDays(10)->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('product-bonus-rules.index'))
            ->post(route('product-bonus-rules.replace', $oldRule), [
                'bonus_product_id' => $bonusProduct->id,
                'bonus_quantity' => 2,
                'starts_at' => now()->subDay()->toDateString(),
            ])
            ->assertRedirect(route('product-bonus-rules.index'))
            ->assertSessionHasErrors('starts_at');

        $this->assertSame(1, ProductBonusRule::where('trigger_product_id', $triggerProduct->id)->count());
    }

    public function test_expired_bonus_rule_cannot_be_reactivated(): void
    {
        $admin = $this->userWithRole('admin', 'bonus-expired-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $triggerProduct = Product::create([
            'name' => 'Produk Trigger Bonus Expired',
            'category' => 'Demo',
            'price' => 25000,
            'base_price' => 15000,
            'is_active' => true,
        ]);
        $bonusProduct = Product::create([
            'name' => 'Produk Bonus Expired',
            'category' => 'Demo',
            'price' => 5000,
            'base_price' => 2500,
            'is_active' => true,
        ]);
        $rule = ProductBonusRule::create([
            'trigger_product_id' => $triggerProduct->id,
            'bonus_product_id' => $bonusProduct->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'min_quantity' => 3,
            'bonus_quantity' => 1,
            'starts_at' => now()->subDays(10)->toDateString(),
            'ends_at' => now()->subDay()->toDateString(),
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('product-bonus-rules.index'))
            ->assertOk()
            ->assertSee('Expired')
            ->assertSee('disabled title="Periode sudah lewat"', false);

        $this->actingAs($admin)
            ->from(route('product-bonus-rules.index'))
            ->post(route('product-bonus-rules.toggle-status', $rule))
            ->assertRedirect(route('product-bonus-rules.index'))
            ->assertSessionHasErrors('status');

        $this->assertFalse($rule->fresh()->is_active);
    }

    public function test_order_bonus_plan_can_prefill_outbound_foc_form(): void
    {
        $admin = $this->userWithRole('admin', 'bonus-foc-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'bonus-foc-customer@example.test');
        Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Bonus FOC',
            'phone' => '081234567901',
            'email' => 'bonus-foc-customer@example.test',
            'customer_type' => 'wholesale',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $triggerProduct = Product::create([
            'name' => 'Produk Pemicu FOC',
            'category' => 'Demo',
            'price' => 12000,
            'base_price' => 7000,
            'is_active' => true,
        ]);
        $bonusProduct = Product::create([
            'name' => 'Produk Bonus FOC',
            'category' => 'Demo',
            'price' => 4000,
            'base_price' => 2000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $triggerProduct->id,
            'quantity' => 20,
        ]);
        ProductBonusRule::create([
            'trigger_product_id' => $triggerProduct->id,
            'bonus_product_id' => $bonusProduct->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'min_quantity' => 2,
            'bonus_quantity' => 1,
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('orders.store'), [
                'order_request_token' => 'bonus-foc-token',
                'user_id' => $customerUser->id,
                'company_branch_id' => $branch->id,
                'delivery_date' => now()->addDay()->toDateString(),
                'delivery_time_slot' => '06:00-09:00',
                'address' => 'Jl. Bonus FOC No. 1',
                'order_source' => Order::ORDER_SOURCE_ADMIN,
                'fulfillment_type' => Order::FULFILLMENT_STOCK,
                'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
                'payment_method' => Order::PAYMENT_MANUAL,
                'items' => [
                    ['product_id' => $triggerProduct->id, 'quantity' => 2],
                ],
                'discount_type' => Order::DISCOUNT_NONE,
                'discount_value' => 0,
                'shipping_type' => Order::SHIPPING_NONE,
                'include_ppn' => false,
            ])
            ->assertRedirect();

        $order = Order::where('user_id', $customerUser->id)->latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Bonus Eligible')
            ->assertSee('Produk Bonus FOC')
            ->assertSee('Buat Barang Bonus');

        $this->actingAs($admin)
            ->get(route('outbound-focs.create', ['order_id' => $order->id]))
            ->assertOk()
            ->assertSee('Customer Bonus FOC')
            ->assertSee('Bonus promo dari order ' . $order->order_number)
            ->assertSee('Produk Bonus FOC');
    }

    public function test_price_rule_rejects_overlapping_active_rule_for_same_scope(): void
    {
        $admin = $this->userWithRole('admin', 'price-overlap-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'price-overlap-customer@example.test');
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Harga Overlap',
            'phone' => '081234567898',
            'email' => 'price-overlap-customer@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Harga Overlap',
            'category' => 'Demo',
            'price' => 20000,
            'base_price' => 12000,
            'is_active' => true,
        ]);

        ProductPriceRule::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'company_branch_id' => $branch->id,
            'price' => 16000,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addWeek()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('product-price-rules.index'))
            ->post(route('product-price-rules.store'), [
                'product_id' => $product->id,
                'customer_ids' => [$customer->id],
                'company_branch_id' => $branch->id,
                'price' => 15500,
                'starts_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('product-price-rules.index'))
            ->assertSessionHasErrors('starts_at');

        $this->assertSame(1, ProductPriceRule::where('product_id', $product->id)->count());
    }

    public function test_discount_rule_page_can_create_segment_rule(): void
    {
        $admin = $this->userWithRole('admin', 'discount-page-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'discount-page-customer@example.test');
        Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Diskon Page',
            'phone' => '081234567896',
            'email' => 'discount-page-customer@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Diskon Segment',
            'category' => 'Demo',
            'price' => 20000,
            'base_price' => 12000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('product-discount-rules.index'))
            ->assertOk()
            ->assertSee('Aturan Diskon')
            ->assertSee('Pilih Customer Khusus')
            ->assertSee('Pilih Customer')
            ->assertSee('customer-picker-modal')
            ->assertSee('discount-company-branch-id')
            ->assertSee('discount-customer-branch-note')
            ->assertSee('discount-customer-visible-count')
            ->assertSee('discount-customer-empty-state')
            ->assertSee('data-customer-branch-id')
            ->assertDontSee('id="discount-customer-ids"', false);

        $this->actingAs($admin)
            ->post(route('product-discount-rules.store'), [
                'product_id' => $product->id,
                'customer_type' => 'wholesale',
                'company_branch_id' => $branch->id,
                'discount_type' => ProductDiscountRule::TYPE_PERCENT,
                'discount_value' => 10,
                'min_quantity' => 2,
                'starts_at' => now()->toDateString(),
                'notes' => 'Promo segment grosir',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_discount_rules', [
            'product_id' => $product->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'discount_type' => ProductDiscountRule::TYPE_PERCENT,
            'discount_value' => 10,
            'min_quantity' => 2,
            'is_active' => true,
        ]);
    }

    public function test_discount_rule_page_can_create_rules_for_multiple_customers(): void
    {
        $admin = $this->userWithRole('admin', 'discount-multi-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $firstUser = $this->userWithRole('customer', 'discount-multi-first@example.test');
        $secondUser = $this->userWithRole('customer', 'discount-multi-second@example.test');
        $firstCustomer = Customer::create([
            'user_id' => $firstUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Promo Pertama',
            'phone' => '081234567894',
            'email' => 'discount-multi-first@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $secondCustomer = Customer::create([
            'user_id' => $secondUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Customer Promo Kedua',
            'phone' => '081234567895',
            'email' => 'discount-multi-second@example.test',
            'customer_type' => 'wholesale',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Diskon Multi Customer',
            'category' => 'Demo',
            'price' => 18000,
            'base_price' => 11000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('product-discount-rules.store'), [
                'product_id' => $product->id,
                'customer_ids' => [$firstCustomer->id, $secondCustomer->id],
                'customer_type' => 'wholesale',
                'company_branch_id' => $branch->id,
                'discount_type' => ProductDiscountRule::TYPE_NOMINAL,
                'discount_value' => 1000,
                'min_quantity' => 1,
                'starts_at' => now()->toDateString(),
                'notes' => 'Promo khusus dua customer',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_discount_rules', [
            'product_id' => $product->id,
            'customer_id' => $firstCustomer->id,
            'customer_type' => null,
            'discount_type' => ProductDiscountRule::TYPE_NOMINAL,
            'discount_value' => 1000,
        ]);
        $this->assertDatabaseHas('product_discount_rules', [
            'product_id' => $product->id,
            'customer_id' => $secondCustomer->id,
            'customer_type' => null,
            'discount_type' => ProductDiscountRule::TYPE_NOMINAL,
            'discount_value' => 1000,
        ]);
    }

    public function test_discount_rule_rejects_customer_from_different_selected_branch(): void
    {
        $admin = $this->userWithRole('admin', 'discount-branch-admin@example.test');
        $mainBranch = CompanyBranch::where('is_active', true)->firstOrFail();
        $otherBranch = CompanyBranch::create([
            'company_profile_id' => $mainBranch->company_profile_id,
            'name' => 'Cabang Test Beda',
            'code' => 'CTB',
            'is_active' => true,
        ]);
        $customerUser = $this->userWithRole('customer', 'discount-branch-customer@example.test');
        $otherBranchCustomer = Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $otherBranch->id,
            'name' => 'Customer Cabang Beda',
            'phone' => '081234567899',
            'email' => 'discount-branch-customer@example.test',
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Diskon Cabang',
            'category' => 'Demo',
            'price' => 18000,
            'base_price' => 11000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('product-discount-rules.index'))
            ->post(route('product-discount-rules.store'), [
                'product_id' => $product->id,
                'customer_ids' => [$otherBranchCustomer->id],
                'company_branch_id' => $mainBranch->id,
                'discount_type' => ProductDiscountRule::TYPE_NOMINAL,
                'discount_value' => 1000,
                'min_quantity' => 1,
                'starts_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('product-discount-rules.index'))
            ->assertSessionHasErrors('customer_ids');

        $this->assertSame(0, ProductDiscountRule::where('product_id', $product->id)->count());
    }

    public function test_discount_rule_rejects_overlapping_active_rule_for_same_scope(): void
    {
        $admin = $this->userWithRole('admin', 'discount-overlap-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $product = Product::create([
            'name' => 'Produk Diskon Overlap',
            'category' => 'Demo',
            'price' => 18000,
            'base_price' => 10000,
            'is_active' => true,
        ]);

        ProductDiscountRule::create([
            'product_id' => $product->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'discount_type' => ProductDiscountRule::TYPE_PERCENT,
            'discount_value' => 10,
            'min_quantity' => 2,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addWeek()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('product-discount-rules.index'))
            ->post(route('product-discount-rules.store'), [
                'product_id' => $product->id,
                'customer_type' => 'wholesale',
                'company_branch_id' => $branch->id,
                'discount_type' => ProductDiscountRule::TYPE_PERCENT,
                'discount_value' => 12,
                'min_quantity' => 2,
                'starts_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('product-discount-rules.index'))
            ->assertSessionHasErrors('starts_at');

        $this->assertSame(1, ProductDiscountRule::where('product_id', $product->id)->count());
    }

    public function test_order_applies_active_discount_rule_when_item_has_no_manual_discount(): void
    {
        $admin = $this->userWithRole('admin', 'discount-order-admin@example.test');
        $branch = CompanyBranch::where('is_active', true)->firstOrFail();
        $customerUser = $this->userWithRole('customer', 'discount-order-customer@example.test');
        Customer::create([
            'user_id' => $customerUser->id,
            'company_branch_id' => $branch->id,
            'name' => 'Toko Promo Grosir',
            'phone' => '081234567893',
            'email' => 'discount-order-customer@example.test',
            'customer_type' => 'wholesale',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Produk Promo Otomatis',
            'category' => 'Demo',
            'price' => 10000,
            'base_price' => 6000,
            'is_active' => true,
        ]);
        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 20,
        ]);
        ProductDiscountRule::create([
            'product_id' => $product->id,
            'customer_type' => 'wholesale',
            'company_branch_id' => $branch->id,
            'discount_type' => ProductDiscountRule::TYPE_PERCENT,
            'discount_value' => 10,
            'min_quantity' => 2,
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('orders.store'), [
                'order_request_token' => 'discount-rule-token',
                'user_id' => $customerUser->id,
                'company_branch_id' => $branch->id,
                'delivery_date' => now()->addDay()->toDateString(),
                'delivery_time_slot' => '06:00-09:00',
                'address' => 'Jl. Promo No. 1',
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

        $this->assertSame(2000, $item->discount);
        $this->assertSame(18000, $item->subtotal);
        $this->assertSame(18000, $order->subtotal);
    }

    public function test_price_impact_review_flags_margin_drop_from_latest_purchase_price(): void
    {
        $admin = $this->userWithRole('admin', 'price-impact-admin@example.test');
        $product = Product::create([
            'name' => 'Produk Dampak Cost',
            'category' => 'Demo',
            'price' => 22000,
            'base_price' => 18000,
            'is_active' => true,
        ]);
        $supplier = Supplier::create([
            'name' => 'PT Supplier Naik Harga',
            'phone' => '081200000001',
            'is_active' => true,
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-IMPACT-001',
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_PENDING,
            'approval_status' => PurchaseOrder::APPROVAL_APPROVED,
            'subtotal' => 200000,
            'total' => 200000,
            'created_by' => $admin->id,
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'received_quantity' => 0,
            'price' => 20000,
            'subtotal' => 200000,
        ]);

        $this->actingAs($admin)
            ->get(route('price-impact-review.index', [
                'target_margin' => 25,
                'cost_increase_threshold' => 5,
            ]))
            ->assertOk()
            ->assertSee('Produk Dampak Cost')
            ->assertSee('Perlu Review')
            ->assertSee('Rp 27.000');
    }

    public function test_price_impact_review_apply_creates_approval_before_master_price_changes(): void
    {
        $admin = $this->userWithRole('admin', 'price-impact-apply-admin@example.test');
        $product = Product::create([
            'name' => 'Produk Apply Dampak Cost',
            'category' => 'Demo',
            'price' => 22000,
            'base_price' => 18000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('price-impact-review.apply', $product), [
                'new_base_price' => 20000,
                'new_price' => 27000,
                'reason' => 'Penyesuaian kenaikan harga pemasok',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $product->refresh();

        $this->assertSame(18000, $product->base_price);
        $this->assertSame(22000, $product->price);
        $this->assertDatabaseHas('approval_requests', [
            'approval_type' => ApprovalRequest::TYPE_PRICE_CHANGE,
            'approvable_type' => Product::class,
            'approvable_id' => $product->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);
        $this->assertSame(0, ProductPriceHistory::where('product_id', $product->id)->count());

        $this->actingAs($admin)
            ->get(route('price-impact-review.index', ['mode' => 'all']))
            ->assertOk()
            ->assertSee('Menunggu Approval')
            ->assertSee('Lihat Approval')
            ->assertDontSee('Ajukan');
    }

    public function test_price_change_approval_updates_master_prices_and_records_history(): void
    {
        $admin = $this->userWithRole('admin', 'price-impact-approval-admin@example.test');
        $product = Product::create([
            'name' => 'Produk Approval Dampak Cost',
            'category' => 'Demo',
            'price' => 22000,
            'base_price' => 18000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('price-impact-review.apply', $product), [
                'new_base_price' => 20000,
                'new_price' => 27000,
                'reason' => 'Penyesuaian kenaikan harga pemasok',
            ])
            ->assertRedirect();

        $approvalRequest = ApprovalRequest::where('approval_type', ApprovalRequest::TYPE_PRICE_CHANGE)
            ->where('approvable_type', Product::class)
            ->where('approvable_id', $product->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('approval-requests.approve', $approvalRequest), [
                'decision_note' => 'Harga disetujui',
            ])
            ->assertRedirect(route('approval-requests.show', $approvalRequest));

        $product->refresh();
        $approvalRequest->refresh();

        $this->assertSame(ApprovalRequest::STATUS_APPROVED, $approvalRequest->status);
        $this->assertSame(20000, $product->base_price);
        $this->assertSame(27000, $product->price);
        $this->assertDatabaseHas('product_price_histories', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'old_price' => 22000,
            'new_price' => 27000,
            'old_base_price' => 18000,
            'new_base_price' => 20000,
            'reason' => 'Penyesuaian kenaikan harga pemasok',
        ]);
        $this->assertSame(1, ProductPriceHistory::where('product_id', $product->id)->count());
    }

    private function userWithRole(string $role, string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole($role);

        return $user;
    }
}
