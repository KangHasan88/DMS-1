<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returnable_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->string('category', 40)->default('container');
            $table->string('unit', 30)->default('pcs');
            $table->unsignedBigInteger('replacement_value')->default(0);
            $table->boolean('requires_serial_tracking')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('returnable_package_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('returnable_package_id')->constrained('returnable_packages')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_branch_id')->nullable()->constrained('company_branches')->nullOnDelete();
            $table->integer('outstanding_quantity')->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(['returnable_package_id', 'customer_id', 'company_branch_id'], 'returnable_package_balance_unique');
            $table->index(['customer_id', 'outstanding_quantity'], 'rpb_customer_outstanding_idx');
            $table->index(['company_branch_id', 'outstanding_quantity'], 'rpb_branch_outstanding_idx');
        });

        Schema::create('returnable_package_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_number', 40)->unique();
            $table->foreignId('returnable_package_id')->constrained('returnable_packages')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_branch_id')->nullable()->constrained('company_branches')->nullOnDelete();
            $table->string('movement_type', 30);
            $table->date('movement_date');
            $table->unsignedInteger('quantity');
            $table->integer('balance_before')->default(0);
            $table->integer('balance_after')->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('unit_value')->default(0);
            $table->unsignedBigInteger('total_value')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['movement_date', 'movement_type'], 'rpm_date_type_idx');
            $table->index(['returnable_package_id', 'customer_id'], 'rpm_package_customer_idx');
            $table->index(['company_branch_id', 'movement_date'], 'rpm_branch_date_idx');
            $table->index(['reference_type', 'reference_id'], 'rpm_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returnable_package_movements');
        Schema::dropIfExists('returnable_package_balances');
        Schema::dropIfExists('returnable_packages');
    }
};
