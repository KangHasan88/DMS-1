<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'quantity']);
        });

        Schema::table('inventory_documents', function (Blueprint $table) {
            $table->foreignId('transfer_to_warehouse_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('warehouses')
                ->nullOnDelete();
            $table->index(['transfer_to_warehouse_id', 'document_date']);
        });

        $defaultWarehouseId = DB::table('warehouses')
            ->where('is_default', true)
            ->value('id')
            ?? DB::table('warehouses')->orderBy('sort_order')->orderBy('id')->value('id');

        if ($defaultWarehouseId) {
            DB::table('product_stocks')
                ->orderBy('id')
                ->chunkById(200, function ($stocks) use ($defaultWarehouseId) {
                    foreach ($stocks as $stock) {
                        DB::table('product_warehouse_stocks')->updateOrInsert(
                            [
                                'product_id' => $stock->product_id,
                                'warehouse_id' => $defaultWarehouseId,
                            ],
                            [
                                'quantity' => (int) $stock->quantity,
                                'min_stock' => (int) ($stock->min_stock ?? 0),
                                'max_stock' => property_exists($stock, 'max_stock') ? $stock->max_stock : null,
                                'last_updated_at' => $stock->last_updated_at,
                                'updated_by' => $stock->updated_by,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_documents', function (Blueprint $table) {
            $table->dropForeign(['transfer_to_warehouse_id']);
            $table->dropIndex(['transfer_to_warehouse_id', 'document_date']);
            $table->dropColumn('transfer_to_warehouse_id');
        });

        Schema::dropIfExists('product_warehouse_stocks');
    }
};
