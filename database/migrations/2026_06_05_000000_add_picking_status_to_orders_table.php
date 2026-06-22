<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment','paid','checking_stock','picking','procuring','repacking','ready','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending_payment'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment','paid','checking_stock','procuring','repacking','ready','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending_payment'");
    }
};
