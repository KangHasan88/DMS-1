<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_bonus_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trigger_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('bonus_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_type')->nullable();
            $table->foreignId('company_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('min_quantity')->default(1);
            $table->unsignedInteger('bonus_quantity')->default(1);
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['trigger_product_id', 'is_active', 'starts_at', 'ends_at'], 'pbr_trigger_active_period_idx');
            $table->index(['customer_id', 'company_branch_id'], 'pbr_customer_branch_idx');
            $table->index(['customer_type', 'company_branch_id'], 'pbr_segment_branch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bonus_rules');
    }
};
