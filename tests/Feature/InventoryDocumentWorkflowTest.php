<?php

namespace Tests\Feature;

use App\Models\InventoryDocument;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductWarehouseStock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_btb_post_adds_stock_and_records_movement(): void
    {
        $user = $this->actingAdmin();
        $warehouse = Warehouse::where('is_default', true)->firstOrFail();
        $product = Product::create(['name' => 'Produk BTB', 'price' => 10000, 'base_price' => 7000, 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('inventory-documents.store'), [
            'type' => InventoryDocument::TYPE_BTB,
            'document_date' => now()->format('Y-m-d'),
            'warehouse_id' => $warehouse->id,
            'reference_number' => 'TEST-BTB',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 12, 'unit_cost' => 7000],
            ],
        ]);

        $document = InventoryDocument::where('reference_number', 'TEST-BTB')->firstOrFail();
        $response->assertRedirect(route('inventory-documents.show', $document));

        $this->actingAs($user)->post(route('inventory-documents.post', $document))
            ->assertRedirect(route('inventory-documents.show', $document));

        $this->assertSame(12, ProductStock::where('product_id', $product->id)->value('quantity'));
        $this->assertSame(12, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'source_type' => StockMovement::SOURCE_BTB,
            'source_id' => $document->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 12,
            'before_quantity' => 0,
            'after_quantity' => 12,
        ]);
    }

    public function test_bkb_post_reduces_stock_and_void_reverses_it(): void
    {
        $user = $this->actingAdmin();
        $warehouse = Warehouse::where('is_default', true)->firstOrFail();
        $product = Product::create(['name' => 'Produk BKB', 'price' => 10000, 'base_price' => 7000, 'is_active' => true]);
        ProductStock::create(['product_id' => $product->id, 'quantity' => 20]);

        $this->actingAs($user)->post(route('inventory-documents.store'), [
            'type' => InventoryDocument::TYPE_BKB,
            'document_date' => now()->format('Y-m-d'),
            'warehouse_id' => $warehouse->id,
            'reference_number' => 'TEST-BKB',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ])->assertSessionHasNoErrors();

        $document = InventoryDocument::where('reference_number', 'TEST-BKB')->firstOrFail();

        $this->actingAs($user)->post(route('inventory-documents.post', $document))
            ->assertRedirect(route('inventory-documents.show', $document));

        $this->assertSame(15, ProductStock::where('product_id', $product->id)->value('quantity'));
        $this->assertSame(15, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'));

        $this->actingAs($user)->post(route('inventory-documents.void', $document), [
            'void_reason' => 'Salah input',
        ])->assertRedirect(route('inventory-documents.show', $document));

        $this->assertSame(20, ProductStock::where('product_id', $product->id)->value('quantity'));
        $this->assertSame(20, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'));
        $this->assertSame(InventoryDocument::STATUS_VOID, $document->fresh()->status);
    }

    public function test_transfer_moves_stock_between_warehouses_without_changing_global_stock(): void
    {
        $user = $this->actingAdmin();
        $fromWarehouse = Warehouse::where('is_default', true)->firstOrFail();
        $toWarehouse = Warehouse::create([
            'code' => 'TRANSIT',
            'name' => 'Gudang Transit',
            'type' => Warehouse::TYPE_TRANSIT,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $product = Product::create(['name' => 'Produk Transfer', 'price' => 10000, 'base_price' => 7000, 'is_active' => true]);
        ProductStock::create(['product_id' => $product->id, 'quantity' => 20]);
        ProductWarehouseStock::create(['product_id' => $product->id, 'warehouse_id' => $fromWarehouse->id, 'quantity' => 20]);

        $this->actingAs($user)->post(route('inventory-documents.store'), [
            'type' => InventoryDocument::TYPE_TRANSFER,
            'document_date' => now()->format('Y-m-d'),
            'warehouse_id' => $fromWarehouse->id,
            'transfer_to_warehouse_id' => $toWarehouse->id,
            'reference_number' => 'TEST-TRANSFER',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 6],
            ],
        ])->assertSessionHasNoErrors();

        $document = InventoryDocument::where('reference_number', 'TEST-TRANSFER')->firstOrFail();

        $this->actingAs($user)->post(route('inventory-documents.post', $document))
            ->assertRedirect(route('inventory-documents.show', $document));

        $this->assertSame(20, ProductStock::where('product_id', $product->id)->value('quantity'));
        $this->assertSame(14, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $fromWarehouse->id)->value('quantity'));
        $this->assertSame(6, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $toWarehouse->id)->value('quantity'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $fromWarehouse->id,
            'source_type' => StockMovement::SOURCE_TRANSFER_OUT,
            'source_id' => $document->id,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => 6,
            'before_quantity' => 20,
            'after_quantity' => 14,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $toWarehouse->id,
            'source_type' => StockMovement::SOURCE_TRANSFER_IN,
            'source_id' => $document->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 6,
            'before_quantity' => 0,
            'after_quantity' => 6,
        ]);

        $this->actingAs($user)->post(route('inventory-documents.void', $document), [
            'void_reason' => 'Batal transfer',
        ])->assertRedirect(route('inventory-documents.show', $document));

        $this->assertSame(20, ProductStock::where('product_id', $product->id)->value('quantity'));
        $this->assertSame(20, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $fromWarehouse->id)->value('quantity'));
        $this->assertSame(0, ProductWarehouseStock::where('product_id', $product->id)->where('warehouse_id', $toWarehouse->id)->value('quantity'));
    }

    public function test_bkb_cannot_post_when_stock_is_not_enough(): void
    {
        $user = $this->actingAdmin();
        $warehouse = Warehouse::where('is_default', true)->firstOrFail();
        $product = Product::create(['name' => 'Produk Kosong', 'price' => 10000, 'base_price' => 7000, 'is_active' => true]);

        $document = InventoryDocument::create([
            'document_number' => InventoryDocument::nextDocumentNumber(InventoryDocument::TYPE_BKB),
            'type' => InventoryDocument::TYPE_BKB,
            'status' => InventoryDocument::STATUS_DRAFT,
            'document_date' => now(),
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
        ]);
        $document->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)->post(route('inventory-documents.post', $document))
            ->assertSessionHas('error');

        $this->assertSame(InventoryDocument::STATUS_DRAFT, $document->fresh()->status);
    }

    private function actingAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'inventory-document-admin@example.test',
        ]);
        $user->assignRole('admin');

        return $user;
    }
}
