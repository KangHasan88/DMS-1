<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ar_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('ar_invoices', 'exchange_status')) {
                $table->string('exchange_status')->default('ready')->after('tax_error_message');
            }
            if (!Schema::hasColumn('ar_invoices', 'exchange_scheduled_date')) {
                $table->date('exchange_scheduled_date')->nullable()->after('exchange_status');
            }
            if (!Schema::hasColumn('ar_invoices', 'exchange_submitted_at')) {
                $table->timestamp('exchange_submitted_at')->nullable()->after('exchange_scheduled_date');
            }
            if (!Schema::hasColumn('ar_invoices', 'exchange_accepted_at')) {
                $table->timestamp('exchange_accepted_at')->nullable()->after('exchange_submitted_at');
            }
            if (!Schema::hasColumn('ar_invoices', 'exchange_rejected_at')) {
                $table->timestamp('exchange_rejected_at')->nullable()->after('exchange_accepted_at');
            }
            if (!Schema::hasColumn('ar_invoices', 'exchange_rejection_reason')) {
                $table->string('exchange_rejection_reason', 500)->nullable()->after('exchange_rejected_at');
            }
            if (!Schema::hasColumn('ar_invoices', 'exchange_next_action_date')) {
                $table->date('exchange_next_action_date')->nullable()->after('exchange_rejection_reason');
            }
            if (!Schema::hasColumn('ar_invoices', 'exchange_collector_id')) {
                $table->foreignId('exchange_collector_id')->nullable()->after('exchange_next_action_date')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('ar_invoices', 'exchange_receipt_number')) {
                $table->string('exchange_receipt_number')->nullable()->after('exchange_collector_id');
            }
            if (!Schema::hasColumn('ar_invoices', 'exchange_notes')) {
                $table->text('exchange_notes')->nullable()->after('exchange_receipt_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ar_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('ar_invoices', 'exchange_collector_id')) {
                $table->dropConstrainedForeignId('exchange_collector_id');
            }

            foreach ([
                'exchange_notes',
                'exchange_receipt_number',
                'exchange_next_action_date',
                'exchange_rejection_reason',
                'exchange_rejected_at',
                'exchange_accepted_at',
                'exchange_submitted_at',
                'exchange_scheduled_date',
                'exchange_status',
            ] as $column) {
                if (Schema::hasColumn('ar_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};