<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('display_name')->default('Kurmigo DMS');
            $table->string('legal_name')->default('PT Kurmigo Distribusi Indonesia');
            $table->string('npwp')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_profile_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_invoice_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_profile_id', 'is_active']);
            $table->index(['company_profile_id', 'is_invoice_default']);
        });

        $companyId = DB::table('company_profiles')->insertGetId([
            'display_name' => config('invoice.company.display_name', config('app.name', 'Kurmigo DMS')),
            'legal_name' => config('invoice.company.legal_name', 'PT Kurmigo Distribusi Indonesia'),
            'npwp' => config('invoice.company.npwp'),
            'phone' => config('invoice.company.phone'),
            'email' => config('invoice.company.email'),
            'address' => config('invoice.branch.address'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('company_branches')->insert([
            'company_profile_id' => $companyId,
            'name' => config('invoice.branch.name', 'Cabang Utama'),
            'code' => config('invoice.branch.code', 'MAIN'),
            'phone' => config('invoice.branch.phone'),
            'email' => config('invoice.company.email'),
            'address' => config('invoice.branch.address'),
            'is_invoice_default' => true,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('company_branches');
        Schema::dropIfExists('company_profiles');
    }
};
