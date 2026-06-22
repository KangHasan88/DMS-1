<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_stocks', 'consignment_quantity')) {
            return;
        }

        Schema::table('product_stocks', function (Blueprint $table) {
            $table->integer('consignment_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropColumn('consignment_quantity');
        });
    }
};
