<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 40)->unique();
            $table->string('type', 20);
            $table->string('status', 20)->default('draft');
            $table->date('document_date');
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_branch_id')->nullable()->constrained('company_branches')->nullOnDelete();
            $table->string('reference_number', 120)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['type', 'status', 'document_date']);
            $table->index(['warehouse_id', 'document_date']);
        });

        Schema::create('inventory_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_document_id')->constrained('inventory_documents')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_cost')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_document_items');
        Schema::dropIfExists('inventory_documents');
    }
};
