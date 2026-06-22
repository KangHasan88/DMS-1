<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('customer_sales_assignments');
        Schema::dropIfExists('sales_territories');

        Schema::create('sales_territories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_branch_id')
                ->constrained('company_branches')
                ->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['company_branch_id', 'code']);
            $table->index(['company_branch_id', 'is_active'], 'sales_territory_branch_active_idx');
        });

        Schema::create('customer_sales_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();
            $table->foreignId('salesperson_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('sales_territory_id')
                ->nullable()
                ->constrained('sales_territories')
                ->nullOnDelete();
            $table->foreignId('company_branch_id')
                ->nullable()
                ->constrained('company_branches')
                ->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('assignment_type', 20)->default('permanent');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'is_active', 'start_date'], 'cust_sales_customer_active_start_idx');
            $table->index(['salesperson_id', 'is_active'], 'cust_sales_person_active_idx');
            $table->index(['company_branch_id', 'is_active'], 'cust_sales_branch_active_idx');
            $table->index(['sales_territory_id', 'is_active'], 'cust_sales_territory_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_sales_assignments');
        Schema::dropIfExists('sales_territories');
    }
};
