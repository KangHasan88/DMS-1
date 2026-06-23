<?php

namespace Tests\Feature;

use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\CustomerSalesAssignment;
use App\Models\SalesTerritory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCoverageLegacyTerritoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_with_inactive_existing_territory_stays_visible_but_not_available_for_new_assignment(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->userWithRole('super-admin');
        $branch = CompanyProfile::defaultProfile()->defaultInvoiceBranch();
        $sales = $this->userWithRole('sales', ['company_branch_id' => $branch->id, 'name' => 'Sales Aktif']);
        $customer = $this->customer($branch, 'Toko Area Lama');
        $newCustomer = $this->customer($branch, 'Toko Baru');
        $inactiveTerritory = SalesTerritory::create([
            'company_branch_id' => $branch->id,
            'code' => 'OLD-A',
            'name' => 'Area Lama',
            'is_active' => false,
            'sort_order' => 9,
        ]);
        $activeTerritory = SalesTerritory::create([
            'company_branch_id' => $branch->id,
            'code' => 'NEW-A',
            'name' => 'Area Baru',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $assignment = CustomerSalesAssignment::create([
            'customer_id' => $customer->id,
            'salesperson_id' => $sales->id,
            'sales_territory_id' => $inactiveTerritory->id,
            'company_branch_id' => $branch->id,
            'start_date' => now()->subDay()->toDateString(),
            'assignment_type' => CustomerSalesAssignment::TYPE_PERMANENT,
            'is_active' => true,
            'assigned_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('sales-coverage.index'));

        $response->assertOk()
            ->assertSee('OLD-A - Area Lama - nonaktif')
            ->assertSee('Area sales ini sedang nonaktif')
            ->assertSee('NEW-A - Area Baru');

        $this->assertTrue($response->viewData('territories')->contains('id', $inactiveTerritory->id));
        $this->assertFalse($response->viewData('activeTerritories')->contains('id', $inactiveTerritory->id));
        $this->assertTrue($response->viewData('activeTerritories')->contains('id', $activeTerritory->id));

        $this->actingAs($admin)
            ->post(route('sales-coverage.assignments.store'), [
                'customer_id' => $newCustomer->id,
                'salesperson_id' => $sales->id,
                'sales_territory_id' => $inactiveTerritory->id,
                'start_date' => now()->toDateString(),
                'assignment_type' => CustomerSalesAssignment::TYPE_PERMANENT,
            ])
            ->assertSessionHasErrors('sales_territory_id');

        $this->actingAs($admin)
            ->put(route('sales-coverage.assignments.update', $assignment), [
                'sales_territory_id' => $inactiveTerritory->id,
                'assignment_type' => CustomerSalesAssignment::TYPE_PERMANENT,
                'notes' => 'Tetap pakai area lama untuk histori',
            ])
            ->assertRedirect(route('sales-coverage.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_sales_assignments', [
            'id' => $assignment->id,
            'sales_territory_id' => $inactiveTerritory->id,
            'notes' => 'Tetap pakai area lama untuk histori',
        ]);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));
        $user->assignRole($role);

        return $user;
    }

    private function customer(CompanyBranch $branch, string $name): Customer
    {
        $user = $this->userWithRole('customer', ['company_branch_id' => $branch->id]);

        return Customer::create([
            'user_id' => $user->id,
            'company_branch_id' => $branch->id,
            'name' => $name,
            'phone' => '0812' . str_pad((string) Customer::count(), 8, '0', STR_PAD_LEFT),
            'email' => $user->email,
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);
    }
}
