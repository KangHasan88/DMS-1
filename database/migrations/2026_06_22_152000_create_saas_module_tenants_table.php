<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('saas_module_tenants')) {
            return;
        }

        Schema::create('saas_module_tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('tenant_module_id');
            $table->string('module_key', 80);
            $table->uuid('operation_id')->nullable();
            $table->string('status', 30)->default('provisioned');
            $table->json('metadata')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('last_launch_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_module_id', 'module_key']);
            $table->index(['tenant_id', 'module_key']);
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_module_tenants');
    }
};
