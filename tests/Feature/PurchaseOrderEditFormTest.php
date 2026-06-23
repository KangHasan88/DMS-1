<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderEditFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_purchase_order_keeps_inactive_supplier_and_product_visible(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $inactiveSupplier = Supplier::create([
            'name' => 'Supplier Lama',
            'phone' => '081234567890',
            'category' => Supplier::CATEGORY_ALL,
            'is_active' => false,
        ]);
        $inactiveProduct = Product::create([
            'name' => 'Produk PO Lama',
            'category' => 'Legacy',
            'price' => 15000,
            'base_price' => 12000,
            'is_active' => false,
        ]);
        $activeProduct = Product::create([
            'name' => 'Produk Aktif Baru',
            'category' => 'Aktif',
            'price' => 18000,
            'base_price' => 14000,
            'is_active' => true,
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'supplier_id' => $inactiveSupplier->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDay()->toDateString(),
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => 24000,
            'total' => 24000,
            'created_by' => $admin->id,
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $inactiveProduct->id,
            'quantity' => 2,
            'price' => 12000,
            'subtotal' => 24000,
        ]);

        $response = $this->actingAs($admin)->get(route('purchase-orders.edit', $purchaseOrder));

        $response->assertOk()
            ->assertSee('Supplier Lama')
            ->assertSee('Pemasok ini sedang nonaktif')
            ->assertSee('Produk PO Lama')
            ->assertSee('Produk ini sedang nonaktif')
            ->assertSee('Produk Aktif Baru');

        $this->assertTrue($response->viewData('suppliers')->contains('id', $inactiveSupplier->id));
        $this->assertTrue($response->viewData('products')->contains('id', $inactiveProduct->id));
        $this->assertTrue($response->viewData('activeProducts')->contains('id', $activeProduct->id));
        $this->assertFalse($response->viewData('activeProducts')->contains('id', $inactiveProduct->id));
    }
}
