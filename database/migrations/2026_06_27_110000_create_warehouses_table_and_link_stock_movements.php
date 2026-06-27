<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('type', 30)->default('main');
            $table->string('address', 500)->nullable();
            $table->string('notes', 500)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });

        $defaultId = DB::table('warehouses')->insertGetId([
            'code' => 'MAIN',
            'name' => 'Gudang Utama',
            'type' => 'main',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('order_id')
                ->constrained('warehouses')
                ->nullOnDelete();
            $table->index(['warehouse_id', 'created_at']);
        });

        DB::table('stock_movements')->whereNull('warehouse_id')->update([
            'warehouse_id' => $defaultId,
        ]);
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['warehouse_id', 'created_at']);
            $table->dropColumn('warehouse_id');
        });

        Schema::dropIfExists('warehouses');
    }
};
