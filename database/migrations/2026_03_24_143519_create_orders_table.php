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
                'pending_payment', // menunggu pembayaran
                'paid',            // sudah dibayar
                'checking_stock',  // cek stok untuk fulfillment stock
                'procuring',       // belanja untuk fulfillment JIT
                'repacking',       // sedang di-repack
                'ready',           // siap kirim
                'shipped',         // dalam pengiriman
                'delivered',       // selesai
                'cancelled',       // dibatalkan
            ])->default('pending_payment');
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
