<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('company_profiles', 'code')) {
            Schema::table('company_profiles', function (Blueprint $table) {
                $table->string('code', 20)->default('KMG')->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('company_profiles', 'code')) {
            Schema::table('company_profiles', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
