<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'company_branch_id')) {
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('company_branches')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'company_branch_id')) {
                $table->dropConstrainedForeignId('company_branch_id');
            }
        });
    }
};
