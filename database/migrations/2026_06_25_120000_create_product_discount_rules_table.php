<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_discount_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_type')->nullable();
            $table->foreignId('company_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('discount_type', ['percent', 'nominal']);
            $table->decimal('discount_value', 10, 2);
            $table->unsignedInteger('min_quantity')->default(1);
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'starts_at', 'ends_at'], 'pdr_product_active_period_idx');
            $table->index(['customer_id', 'company_branch_id'], 'pdr_customer_branch_idx');
            $table->index(['customer_type', 'company_branch_id'], 'pdr_segment_branch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_discount_rules');
    }
};
