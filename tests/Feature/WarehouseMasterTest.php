<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_warehouse_master(): void
    {
        $user = $this->actingAdmin();

        $this->actingAs($user)
            ->get(route('warehouses.index'))
            ->assertOk()
            ->assertSee('Master Gudang')
            ->assertSee('Gudang Utama');

        $this->actingAs($user)
            ->post(route('warehouses.store'), [
                'code' => 'RTR',
                'name' => 'Gudang Retur',
                'type' => Warehouse::TYPE_RETURN,
                'address' => 'Area retur barang customer',
                'sort_order' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('warehouses', [
            'code' => 'RTR',
            'name' => 'Gudang Retur',
            'type' => Warehouse::TYPE_RETURN,
            'is_active' => true,
        ]);
    }

    public function test_stock_movement_uses_default_warehouse_when_not_provided(): void
    {
        $user = $this->actingAdmin();
        $warehouse = Warehouse::where('is_default', true)->firstOrFail();
        $product = Product::create([
            'name' => 'Produk Test Warehouse',
            'price' => 1000,
            'base_price' => 800,
            'is_active' => true,
        ]);

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_ADJUSTMENT,
            'quantity' => 0,
            'before_quantity' => 0,
            'after_quantity' => 0,
            'reason' => 'Test default warehouse',
            'created_by' => $user->id,
        ]);

        $this->assertSame($warehouse->id, $movement->warehouse_id);
    }

    public function test_default_warehouse_cannot_be_deactivated(): void
    {
        $user = $this->actingAdmin();
        $warehouse = Warehouse::where('is_default', true)->firstOrFail();

        $this->actingAs($user)
            ->post(route('warehouses.toggle-status', $warehouse))
            ->assertSessionHasErrors('warehouse');

        $this->assertTrue($warehouse->fresh()->is_active);
    }

    private function actingAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'warehouse-admin@example.test',
        ]);
        $user->assignRole('admin');

        return $user;
    }
}
