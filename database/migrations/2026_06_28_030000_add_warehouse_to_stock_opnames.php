<?php

use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('opname_date')
                ->constrained('warehouses')
                ->nullOnDelete();
            $table->index(['warehouse_id', 'status', 'opname_date']);
        });

        if ($defaultWarehouseId = Warehouse::defaultId()) {
            DB::table('stock_opnames')->whereNull('warehouse_id')->update([
                'warehouse_id' => $defaultWarehouseId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['warehouse_id', 'status', 'opname_date']);
            $table->dropColumn('warehouse_id');
        });
    }
};
