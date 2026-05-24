<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_access_protected_modules(): void
    {
        $user = $this->userWithRole('super-admin');

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/stock')->assertOk();
        $this->actingAs($user)->get('/reports/financial')->assertOk();
    }

    public function test_sales_role_can_view_orders_but_cannot_create_products_or_view_stock(): void
    {
        $user = $this->userWithRole('sales');

        $this->actingAs($user)->get('/orders')->assertOk();
        $this->actingAs($user)->get('/products/create')->assertForbidden();
        $this->actingAs($user)->get('/stock')->assertForbidden();
    }

    public function test_warehouse_role_can_view_stock_but_cannot_view_financial_report(): void
    {
        $user = $this->userWithRole('warehouse');

        $this->actingAs($user)->get('/stock')->assertOk();
        $this->actingAs($user)->get('/reports/financial')->assertForbidden();
    }

    public function test_finance_role_can_view_financial_report_but_cannot_manage_stock(): void
    {
        $user = $this->userWithRole('finance');

        $this->actingAs($user)->get('/reports/financial')->assertOk();
        $this->actingAs($user)->get('/stock')->assertForbidden();
    }

    public function test_product_actions_are_hidden_when_role_only_has_view_permission(): void
    {
        $product = Product::create([
            'name' => 'Bayam',
            'category' => 'Sayur',
            'price' => 5000,
            'base_price' => 3000,
            'is_active' => true,
        ]);

        $user = $this->userWithRole('sales');

        $this->actingAs($user)
            ->get('/products')
            ->assertOk()
            ->assertSee(route('products.show', $product), false)
            ->assertDontSee(route('products.create'), false)
            ->assertDontSee(route('products.edit', $product), false)
            ->assertDontSee("toggleStatus({$product->id})", false)
            ->assertDontSee("deleteProduct({$product->id}", false);
    }

    public function test_stock_mutation_actions_follow_warehouse_permissions(): void
    {
        $product = Product::create([
            'name' => 'Kangkung',
            'category' => 'Sayur',
            'price' => 4000,
            'base_price' => 2500,
            'is_active' => true,
        ]);

        ProductStock::create([
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $warehouse = $this->userWithRole('warehouse');

        $this->actingAs($warehouse)
            ->get('/stock')
            ->assertOk()
            ->assertSee(route('stock.add-form', $product), false)
            ->assertSee(route('stock.reduce-form', $product), false)
            ->assertSee(route('stock.adjustment-form', $product), false);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
