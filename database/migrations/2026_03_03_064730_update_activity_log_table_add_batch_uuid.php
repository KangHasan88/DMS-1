<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Cek apakah kolom batch_uuid sudah ada
            if (!Schema::hasColumn('activity_log', 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            }
            
            // Cek apakah kolom event sudah ada
            if (!Schema::hasColumn('activity_log', 'event')) {
                $table->string('event')->nullable()->after('batch_uuid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn(['batch_uuid', 'event']);
        });
    }
};