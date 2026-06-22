<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'created_by')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('orders', 'salesperson_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('salesperson_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY order_source ENUM('app','admin','sfa','telesales') NOT NULL DEFAULT 'admin'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::table('orders')
                ->whereIn('order_source', ['sfa', 'telesales'])
                ->update(['order_source' => 'admin']);

            DB::statement("ALTER TABLE orders MODIFY order_source ENUM('app','admin') NOT NULL DEFAULT 'app'");
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach (['salesperson_id', 'created_by'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });
    }
};
