<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLegacyTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_customer_keeps_inactive_existing_type_visible_and_updatable(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $inactiveType = CustomerType::create([
            'code' => 'legacy-store',
            'name' => 'Legacy Store',
            'is_active' => false,
            'sort_order' => 9,
        ]);
        CustomerType::create([
            'code' => 'retail',
            'name' => 'Retail',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $customer = Customer::create([
            'name' => 'Toko Legacy',
            'phone' => '081200000001',
            'email' => 'legacy@example.test',
            'customer_type' => $inactiveType->code,
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('customers.edit', $customer));

        $response->assertOk()
            ->assertSee('Legacy Store - nonaktif')
            ->assertSee('Tipe pelanggan tersimpan belum ada atau sedang nonaktif');

        $this->assertTrue($response->viewData('customerTypes')->contains('code', $inactiveType->code));

        $this->actingAs($admin)
            ->put(route('customers.update', $customer), [
                'name' => 'Toko Legacy Update',
                'phone' => '081200000001',
                'email' => 'legacy@example.test',
                'customer_type' => $inactiveType->code,
                'payment_term' => Customer::PAYMENT_CASH,
                'credit_limit' => 0,
                'max_outstanding_orders' => 0,
                'credit_status' => Customer::CREDIT_NORMAL,
                'is_active' => 1,
            ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Toko Legacy Update',
            'customer_type' => $inactiveType->code,
        ]);
    }
}
