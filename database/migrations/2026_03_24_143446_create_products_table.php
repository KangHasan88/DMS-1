<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama produk: Kangkung, Bayam, Daging Ayam, dll
            $table->string('category')->nullable(); // Sayur, Buah, Lauk, Bumbu
            $table->string('unit')->default('ikat'); // kg, ikat, porsi, biji, ekor
            $table->integer('price'); // Harga jual (dalam rupiah)
            $table->integer('base_price')->nullable(); // Harga beli dari pedagang (buat ngitung margin)
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};