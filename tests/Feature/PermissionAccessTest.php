<?php

namespace Tests\Feature;

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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
