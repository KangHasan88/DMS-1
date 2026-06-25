<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_type')->nullable();
            $table->foreignId('company_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('price');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'starts_at', 'ends_at']);
            $table->index(['customer_id', 'company_branch_id']);
            $table->index(['customer_type', 'company_branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_rules');
    }
};
