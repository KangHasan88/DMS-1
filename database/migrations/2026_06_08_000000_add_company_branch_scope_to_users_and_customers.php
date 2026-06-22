<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'company_branch_id')) {
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->after('locale')
                    ->constrained('company_branches')
                    ->nullOnDelete();
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'company_branch_id')) {
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('company_branches')
                    ->nullOnDelete();
            }
        });

        $defaultBranchId = DB::table('company_branches')
            ->where('is_invoice_default', true)
            ->value('id')
            ?: DB::table('company_branches')->orderBy('id')->value('id');

        if ($defaultBranchId) {
            DB::table('customers')
                ->whereNull('company_branch_id')
                ->update(['company_branch_id' => $defaultBranchId]);

            if (Schema::hasColumn('orders', 'company_branch_id')) {
                DB::table('orders')
                    ->whereNull('company_branch_id')
                    ->update(['company_branch_id' => $defaultBranchId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'company_branch_id')) {
                $table->dropConstrainedForeignId('company_branch_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'company_branch_id')) {
                $table->dropConstrainedForeignId('company_branch_id');
            }
        });
    }
};
