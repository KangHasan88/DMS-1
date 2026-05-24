<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Cek dan tambah kolom jika belum ada
            if (!Schema::hasColumn('orders', 'checking_stock_at')) {
                $table->timestamp('checking_stock_at')->nullable()->after('paid_at');
            }
            
            if (!Schema::hasColumn('orders', 'procuring_at')) {
                $table->timestamp('procuring_at')->nullable()->after('checking_stock_at');
            }
            
            if (!Schema::hasColumn('orders', 'repacked_at')) {
                $table->timestamp('repacked_at')->nullable()->after('procuring_at');
            }
            
            if (!Schema::hasColumn('orders', 'ready_at')) {
                $table->timestamp('ready_at')->nullable()->after('repacked_at');
            }
            
            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('ready_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'checking_stock_at',
                'procuring_at',
                'repacked_at',
                'ready_at',
                'shipped_at'
            ]);
        });
    }
};