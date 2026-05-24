<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name'); // snapshot nama produk (kalau produk diupdate, order tetap lihat nama lama)
            $table->integer('price'); // snapshot harga per unit
            $table->integer('quantity');
            $table->integer('subtotal');
            $table->boolean('is_available')->default(true); // barang tersedia di pasar? (kalau false, refund)
            $table->text('notes')->nullable(); // catatan dari customer: "jangan yang tua", dll
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};