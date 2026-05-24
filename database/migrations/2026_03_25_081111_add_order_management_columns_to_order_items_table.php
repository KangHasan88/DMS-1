<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Status pemenuhan per item
            $table->enum('fulfillment_status', ['pending', 'procured', 'fulfilled', 'unavailable'])
                  ->default('pending')->after('notes');
            
            // Harga beli dari pedagang (untuk JIT)
            $table->integer('purchase_price')->nullable()->after('fulfillment_status');
            
            // Nama pedagang/supplier (untuk JIT)
            $table->string('supplier_name')->nullable()->after('purchase_price');
            
            // Lokasi pasar (untuk JIT)
            $table->string('market_location')->nullable()->after('supplier_name');
            
            // Referensi ke stock movement (jika dari stock)
            $table->foreignId('stock_movement_id')->nullable()->after('market_location');
            
            $table->index(['fulfillment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'fulfillment_status',
                'purchase_price',
                'supplier_name',
                'market_location',
                'stock_movement_id'
            ]);
        });
    }
};