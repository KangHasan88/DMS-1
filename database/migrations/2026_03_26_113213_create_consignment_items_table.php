<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->integer('sold_quantity')->default(0);
            $table->integer('returned_quantity')->default(0);
            $table->integer('price'); // harga jual dari supplier
            $table->integer('subtotal');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['consignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_items');
    }
};