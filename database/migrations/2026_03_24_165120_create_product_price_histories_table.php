<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // user yang melakukan perubahan
            $table->integer('old_price')->nullable(); // harga lama
            $table->integer('new_price'); // harga baru
            $table->integer('old_base_price')->nullable(); // harga beli lama
            $table->integer('new_base_price')->nullable(); // harga beli baru
            $table->text('reason')->nullable(); // alasan perubahan harga
            $table->timestamps();
            
            // Index untuk query cepat
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};