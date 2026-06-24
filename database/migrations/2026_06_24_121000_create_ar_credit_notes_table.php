<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ar_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('ar_invoices', 'credit_note_amount')) {
                $table->unsignedBigInteger('credit_note_amount')->default(0)->after('paid_amount');
            }
        });

        Schema::create('ar_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_number')->unique();
            $table->foreignId('ar_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_branch_id')->nullable()->constrained('company_branches')->nullOnDelete();
            $table->date('note_date');
            $table->string('reason_type', 40);
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('posted');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'note_date']);
            $table->index(['company_branch_id', 'note_date']);
            $table->index(['status', 'note_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_credit_notes');

        Schema::table('ar_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('ar_invoices', 'credit_note_amount')) {
                $table->dropColumn('credit_note_amount');
            }
        });
    }
};
