<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ReturnablePackage;
use App\Models\ReturnablePackageBalance;
use App\Models\ReturnablePackageCategory;
use App\Models\ReturnablePackageMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnablePackageFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_package_and_record_customer_movements(): void
    {
        $user = $this->actingAdmin();
        $customer = $this->customer('Toko Aqua Sejahtera', '081300000001');
        $category = ReturnablePackageCategory::where('code', ReturnablePackage::CATEGORY_GALLON)->firstOrFail();

        $this->actingAs($user)
            ->post(route('returnable-packages.store'), [
                'code' => 'GAL19',
                'name' => 'Galon 19L',
                'returnable_package_category_id' => $category->id,
                'unit' => 'pcs',
                'replacement_value' => 50000,
            ])
            ->assertRedirect(route('returnable-packages.index'))
            ->assertSessionHasNoErrors();

        $package = ReturnablePackage::where('code', 'GAL19')->firstOrFail();

        $this->actingAs($user)
            ->post(route('returnable-packages.movements.store'), [
                'returnable_package_id' => $package->id,
                'customer_id' => $customer->id,
                'movement_type' => ReturnablePackageMovement::TYPE_ISSUED,
                'movement_date' => now()->toDateString(),
                'quantity' => 10,
                'reference_number' => 'DO-001',
            ])
            ->assertRedirect(route('returnable-packages.index'))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('returnable-packages.movements.store'), [
                'returnable_package_id' => $package->id,
                'customer_id' => $customer->id,
                'movement_type' => ReturnablePackageMovement::TYPE_RETURNED,
                'movement_date' => now()->toDateString(),
                'quantity' => 4,
                'reference_number' => 'RET-001',
            ])
            ->assertRedirect(route('returnable-packages.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('returnable_package_balances', [
            'returnable_package_id' => $package->id,
            'customer_id' => $customer->id,
            'outstanding_quantity' => 6,
        ]);

        $this->actingAs($user)
            ->get(route('returnable-packages.index'))
            ->assertOk()
            ->assertSee('Kemasan Kembali')
            ->assertSee('Galon 19L')
            ->assertSee('Toko Aqua Sejahtera');
    }

    public function test_admin_can_create_returnable_package_category(): void
    {
        $user = $this->actingAdmin();

        $this->actingAs($user)
            ->post(route('returnable-packages.categories.store'), [
                'category_code' => 'jerigen',
                'category_name' => 'Jerigen',
            ])
            ->assertRedirect(route('returnable-packages.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('returnable_package_categories', [
            'code' => 'jerigen',
            'name' => 'Jerigen',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('returnable-packages.index'))
            ->assertOk()
            ->assertSee('Kategori Kemasan')
            ->assertSee('Jerigen');
    }

    public function test_admin_can_toggle_returnable_package_category_status(): void
    {
        $user = $this->actingAdmin();
        $category = ReturnablePackageCategory::where('code', ReturnablePackage::CATEGORY_GALLON)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('returnable-packages.categories.toggle', $category))
            ->assertRedirect(route('returnable-packages.index'))
            ->assertSessionHasNoErrors();

        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($user)
            ->get(route('returnable-packages.index'))
            ->assertOk()
            ->assertDontSee('value="' . $category->id . '"');

        $this->actingAs($user)
            ->patch(route('returnable-packages.categories.toggle', $category))
            ->assertRedirect(route('returnable-packages.index'))
            ->assertSessionHasNoErrors();

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_returnable_package_balance_cannot_go_negative(): void
    {
        $user = $this->actingAdmin();
        $customer = $this->customer('Toko Lemin Mandiri', '081300000002');
        $package = ReturnablePackage::create([
            'code' => 'BTL01',
            'name' => 'Botol Kaca',
            'category' => ReturnablePackage::CATEGORY_BOTTLE,
            'unit' => 'pcs',
            'replacement_value' => 2500,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('returnable-packages.movements.store'), [
                'returnable_package_id' => $package->id,
                'customer_id' => $customer->id,
                'movement_type' => ReturnablePackageMovement::TYPE_RETURNED,
                'movement_date' => now()->toDateString(),
                'quantity' => 1,
            ])
            ->assertSessionHas('error', 'Saldo kemasan customer tidak boleh minus.');

        $this->assertDatabaseMissing('returnable_package_movements', [
            'returnable_package_id' => $package->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertDatabaseMissing('returnable_package_balances', [
            'returnable_package_id' => $package->id,
            'customer_id' => $customer->id,
        ]);
    }

    private function actingAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'returnable-admin@example.test',
        ]);
        $user->assignRole('admin');

        return $user;
    }

    private function customer(string $name, string $phone): Customer
    {
        return Customer::create([
            'name' => $name,
            'phone' => $phone,
            'email' => str($name)->slug() . '@example.test',
            'address' => 'Jl. Distribusi No. 1',
            'customer_type' => 'regular',
            'is_active' => true,
        ]);
    }
}
