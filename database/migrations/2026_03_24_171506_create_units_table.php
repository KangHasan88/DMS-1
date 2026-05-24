<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ikat, Kilogram, Butir, dll
            $table->string('code', 20)->unique(); // ikat, kg, butir, ekor
            $table->string('symbol', 10)->nullable(); // ik, kg, btr, ek
            $table->string('category')->nullable(); // Berat, Jumlah, Panjang, dll
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};