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

        DB::statement("ALTER TABLE customers MODIFY customer_type VARCHAR(100) NOT NULL DEFAULT 'regular'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE customers MODIFY customer_type ENUM('regular', 'premium', 'wholesale') NOT NULL DEFAULT 'regular'");
    }
};
