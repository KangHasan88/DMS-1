<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->integer('price');
            $table->integer('subtotal');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['outbound_return_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_return_items');
    }
};