<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultBranchId = DB::table('company_branches')
            ->where('is_invoice_default', true)
            ->value('id')
            ?: DB::table('company_branches')->orderBy('id')->value('id');

        Schema::table('outbound_focs', function (Blueprint $table) {
            if (!Schema::hasColumn('outbound_focs', 'company_branch_id')) {
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->after('foc_number')
                    ->constrained('company_branches')
                    ->nullOnDelete();
            }
        });

        Schema::table('outbound_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('outbound_returns', 'company_branch_id')) {
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->after('return_number')
                    ->constrained('company_branches')
                    ->nullOnDelete();
            }
        });

        if ($defaultBranchId) {
            DB::table('outbound_focs')
                ->whereNull('company_branch_id')
                ->update(['company_branch_id' => $defaultBranchId]);

            DB::table('outbound_returns')
                ->whereNull('company_branch_id')
                ->update(['company_branch_id' => $defaultBranchId]);
        }
    }

    public function down(): void
    {
        Schema::table('outbound_returns', function (Blueprint $table) {
            if (Schema::hasColumn('outbound_returns', 'company_branch_id')) {
                $table->dropConstrainedForeignId('company_branch_id');
            }
        });

        Schema::table('outbound_focs', function (Blueprint $table) {
            if (Schema::hasColumn('outbound_focs', 'company_branch_id')) {
                $table->dropConstrainedForeignId('company_branch_id');
            }
        });
    }
};
