<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Cek apakah kolom admin_notes sudah ada, jika belum tambahkan
            if (!Schema::hasColumn('orders', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('notes');
            }
            
            // Tambah kolom baru
            $table->enum('order_source', ['app', 'admin'])->default('app')->after('status');
            $table->enum('payment_method', ['gateway', 'manual', 'wallet'])->nullable()->after('order_source');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->enum('fulfillment_type', ['stock', 'jit'])->default('jit')->after('payment_reference');
            $table->text('shopping_notes')->nullable()->after('admin_notes');
            $table->timestamp('procurement_started_at')->nullable()->after('shopping_notes');
            $table->timestamp('procurement_completed_at')->nullable()->after('procurement_started_at');
            $table->string('tracking_code')->nullable()->after('delivered_at');
            $table->timestamp('shipped_at')->nullable()->after('tracking_code');
            
            // Indeks
            $table->index(['order_source', 'fulfillment_type']);
            $table->index(['payment_method', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_source',
                'payment_method',
                'payment_reference',
                'fulfillment_type',
                'shopping_notes',
                'procurement_started_at',
                'procurement_completed_at',
                'tracking_code',
                'shipped_at'
            ]);
            $table->dropIndex(['order_source', 'fulfillment_type']);
            $table->dropIndex(['payment_method', 'status']);
        });
    }
};