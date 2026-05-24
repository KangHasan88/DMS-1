<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('direct_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->integer('price');
            $table->integer('subtotal');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['direct_purchase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_purchase_items');
    }
};