<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('market_name')->nullable(); // Nama pasar (Pasar Baru, Pasar Lama)
            $table->string('stall_number')->nullable(); // Nomor kios/lapak
            $table->text('address')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('photo')->nullable();
            $table->enum('category', ['sayur', 'buah', 'lauk', 'bumbu', 'sembako', 'all'])->default('all');
            $table->string('specialty')->nullable(); // Spesialisasi (contoh: sayur organik, ayam potong)
            $table->integer('min_order')->default(0); // Minimal order dalam rupiah
            $table->boolean('is_active')->default(true);
            $table->integer('total_transactions')->default(0);
            $table->bigInteger('total_purchase')->default(0);
            $table->text('notes')->nullable();
            $table->text('payment_notes')->nullable(); // Catatan pembayaran (transfer tunai, hutang)
            $table->timestamp('last_purchase_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['phone', 'is_active']);
            $table->index(['market_name', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};