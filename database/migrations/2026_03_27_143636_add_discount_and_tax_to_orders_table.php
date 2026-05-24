<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Diskon
            $table->enum('discount_type', ['none', 'percent', 'nominal'])->default('none')->after('subtotal');
            $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
            $table->integer('discount_amount')->default(0)->after('discount_value');
            
            // Ongkos Kirim
            $table->enum('shipping_type', ['flat', 'weight', 'distance'])->default('flat')->after('discount_amount');
            $table->integer('shipping_weight')->nullable()->after('shipping_type');
            $table->integer('shipping_distance')->nullable()->after('shipping_weight');
            $table->integer('shipping_rate')->default(0)->after('shipping_distance');
            
            // PPN
            $table->boolean('include_ppn')->default(false)->after('shipping_rate');
            $table->decimal('ppn_rate', 5, 2)->default(11)->after('include_ppn');
            $table->integer('ppn_amount')->default(0)->after('ppn_rate');
            
            // Grand Total setelah diskon, ongkir, PPN
            $table->integer('grand_total')->default(0)->after('ppn_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type', 'discount_value', 'discount_amount',
                'shipping_type', 'shipping_weight', 'shipping_distance', 'shipping_rate',
                'include_ppn', 'ppn_rate', 'ppn_amount', 'grand_total'
            ]);
        });
    }
};