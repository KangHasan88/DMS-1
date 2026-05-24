<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // customer yang order
            $table->string('order_number')->unique(); // format: KMG202403240001
            $table->date('delivery_date'); // tanggal pengiriman
            $table->string('delivery_time_slot')->default('06:00-09:00');
            $table->text('address'); // alamat pengiriman
            $table->string('latitude')->nullable(); // koordinat buat kurir
            $table->string('longitude')->nullable();
            $table->integer('delivery_fee'); // biaya kirim
            $table->integer('packing_fee')->default(1000); // biaya repack
            $table->integer('subtotal'); // total harga produk
            $table->integer('total'); // subtotal + delivery_fee + packing_fee
            $table->enum('status', [
                'pending',      // menunggu pembayaran
                'paid',         // sudah dibayar
                'shopping',     // sedang dibeli tim (jam 12 malam)
                'repacking',    // sedang di-repack
                'ready_for_delivery', // siap kirim
                'delivered',    // sudah dikirim
                'cancelled'     // dibatalkan
            ])->default('pending');
            $table->text('notes')->nullable(); // catatan dari customer
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shopping_at')->nullable();
            $table->timestamp('repacked_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};