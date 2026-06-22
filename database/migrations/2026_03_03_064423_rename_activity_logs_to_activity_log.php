<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_log') || !Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::rename('activity_logs', 'activity_log');
    }

    public function down(): void
    {
        Schema::rename('activity_log', 'activity_logs');
    }
};
