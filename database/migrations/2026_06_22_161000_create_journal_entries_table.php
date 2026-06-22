<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->string('journal_number', 40)->unique();
                $table->date('journal_date');
                $table->text('description');
                $table->foreignId('company_branch_id')->nullable()->constrained('company_branches')->nullOnDelete();
                $table->string('status', 20)->default('posted');
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('debit_total')->default(0);
                $table->unsignedBigInteger('credit_total')->default(0);
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('voided_at')->nullable();
                $table->text('void_reason')->nullable();
                $table->timestamps();

                $table->index(['journal_date', 'status']);
                $table->index(['company_branch_id', 'journal_date']);
                $table->index(['source_type', 'source_id']);
            });
        }

        if (!Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
                $table->foreignId('chart_account_id')->constrained('chart_accounts')->restrictOnDelete();
                $table->string('description')->nullable();
                $table->unsignedBigInteger('debit_amount')->default(0);
                $table->unsignedBigInteger('credit_amount')->default(0);
                $table->timestamps();

                $table->index(['chart_account_id', 'journal_entry_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
    }
};
