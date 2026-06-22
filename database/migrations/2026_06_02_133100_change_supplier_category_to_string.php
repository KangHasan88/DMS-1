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

        DB::statement("ALTER TABLE suppliers MODIFY category VARCHAR(100) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE suppliers MODIFY category ENUM('sayur','buah','lauk','bumbu','sembako','all') NOT NULL DEFAULT 'sayur'");
    }
};
