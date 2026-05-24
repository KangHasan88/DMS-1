<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Hapus enum constraint lama dengan mengubah ke VARCHAR
        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending_payment'");
        
        // Set ulang enum dengan nilai baru yang lengkap
        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'pending_payment',
            'paid',
            'checking_stock',
            'procuring',
            'repacking',
            'ready',
            'shipped',
            'delivered',
            'cancelled'
        ) NOT NULL DEFAULT 'pending_payment'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Kembalikan ke enum lama
        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'pending',
            'paid',
            'shopping',
            'repacking',
            'ready_for_delivery',
            'delivered',
            'cancelled'
        ) NOT NULL DEFAULT 'pending'");
    }
};
