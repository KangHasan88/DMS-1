<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ReturnablePackage;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReturnablePackagingProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_returnable_packaging_profile_on_product(): void
    {
        $user = $this->actingAdmin();
        $unit = Unit::create([
            'code' => 'GAL',
            'name' => 'Galon',
            'symbol' => 'galon',
            'category' => 'volume',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $package = ReturnablePackage::create([
            'code' => 'GAL19',
            'name' => 'Galon 19L',
            'category' => ReturnablePackage::CATEGORY_GALLON,
            'unit' => 'pcs',
            'replacement_value' => 50000,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('products.store'), [
                'name' => 'Air Mineral Galon 19L',
                'unit_id' => $unit->id,
                'price' => 22000,
                'base_price' => 18000,
                'returnable_package_id' => $package->id,
                'returnable_package_quantity_per_unit' => 1,
                'returnable_package_default_flow' => Product::PACKAGING_FLOW_RETURNABLE,
                'is_active' => 1,
            ])
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors();

        $product = Product::where('name', 'Air Mineral Galon 19L')->firstOrFail();

        $this->assertTrue($product->hasReturnablePackaging());
        $this->assertSame(5, $product->returnablePackageQuantityFor(5));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'returnable_package_id' => $package->id,
            'returnable_package_quantity_per_unit' => 1,
            'returnable_package_default_flow' => Product::PACKAGING_FLOW_RETURNABLE,
        ]);

        $this->actingAs($user)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Profil Kemasan')
            ->assertSee('Galon 19L')
            ->assertSee('Kemasan Kembali');
    }

    public function test_empty_packaging_selection_clears_product_packaging_profile(): void
    {
        $user = $this->actingAdmin();
        $unit = Unit::create([
            'code' => 'BTL',
            'name' => 'Botol',
            'symbol' => 'btl',
            'category' => 'unit',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $package = ReturnablePackage::create([
            'code' => 'BTL01',
            'name' => 'Botol Kaca',
            'category' => ReturnablePackage::CATEGORY_BOTTLE,
            'unit' => 'pcs',
            'replacement_value' => 2500,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Minuman Botol',
            'unit_id' => $unit->id,
            'price' => 10000,
            'base_price' => 7000,
            'returnable_package_id' => $package->id,
            'returnable_package_quantity_per_unit' => 1,
            'returnable_package_default_flow' => Product::PACKAGING_FLOW_SOLD,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('products.update', $product), [
                'name' => 'Minuman Botol',
                'unit_id' => $unit->id,
                'price' => 10000,
                'base_price' => 7000,
                'returnable_package_id' => '',
                'returnable_package_quantity_per_unit' => 0,
                'returnable_package_default_flow' => Product::PACKAGING_FLOW_RETURNABLE,
                'is_active' => 1,
            ])
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertFalse($product->hasReturnablePackaging());
        $this->assertNull($product->returnable_package_id);
        $this->assertSame(0, $product->returnable_package_quantity_per_unit);
        $this->assertNull($product->returnable_package_default_flow);
    }

    private function actingAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'product-packaging-admin@example.test',
        ]);
        $user->assignRole('admin');

        return $user;
    }
}
