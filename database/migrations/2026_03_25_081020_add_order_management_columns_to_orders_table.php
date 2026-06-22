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
            
            if (!Schema::hasColumn('orders', 'order_source')) {
                $table->enum('order_source', ['app', 'admin'])->default('app')->after('status');
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->enum('payment_method', ['gateway', 'manual', 'wallet'])->nullable()->after('order_source');
            }
            if (!Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'fulfillment_type')) {
                $table->enum('fulfillment_type', ['stock', 'jit'])->default('jit')->after('payment_reference');
            }
            if (!Schema::hasColumn('orders', 'shopping_notes')) {
                $table->text('shopping_notes')->nullable()->after('admin_notes');
            }
            if (!Schema::hasColumn('orders', 'procurement_started_at')) {
                $table->timestamp('procurement_started_at')->nullable()->after('shopping_notes');
            }
            if (!Schema::hasColumn('orders', 'procurement_completed_at')) {
                $table->timestamp('procurement_completed_at')->nullable()->after('procurement_started_at');
            }
            if (!Schema::hasColumn('orders', 'tracking_code')) {
                $table->string('tracking_code')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('tracking_code');
            }
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
