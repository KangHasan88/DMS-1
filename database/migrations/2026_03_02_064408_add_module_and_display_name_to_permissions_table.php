<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'module')) {
                $table->string('module')->nullable()->after('guard_name');
            }
            if (!Schema::hasColumn('permissions', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['module', 'display_name']);
        });
    }
};