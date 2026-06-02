<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE suppliers MODIFY category VARCHAR(100) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE suppliers MODIFY category ENUM('sayur','buah','lauk','bumbu','sembako','all') NOT NULL DEFAULT 'sayur'");
    }
};
