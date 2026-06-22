<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('company_profiles', 'code')) {
            DB::table('company_profiles')
                ->whereNotNull('code')
                ->orderBy('id')
                ->get(['id', 'code'])
                ->each(function ($company) {
                    DB::table('company_profiles')
                        ->where('id', $company->id)
                        ->update(['code' => substr(strtoupper((string) $company->code), 0, 3)]);
                });
        }

        if (Schema::hasColumn('company_branches', 'code')) {
            DB::table('company_branches')
                ->whereNotNull('code')
                ->orderBy('id')
                ->get(['id', 'code'])
                ->each(function ($branch) {
                    DB::table('company_branches')
                        ->where('id', $branch->id)
                        ->update(['code' => substr(strtoupper((string) $branch->code), 0, 3)]);
                });
        }
    }

    public function down(): void
    {
        //
    }
};
