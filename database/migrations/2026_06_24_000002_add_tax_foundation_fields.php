<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('company_profiles', 'nitku')) {
                $table->string('nitku', 40)->nullable()->after('npwp');
            }
            if (!Schema::hasColumn('company_profiles', 'is_pkp')) {
                $table->boolean('is_pkp')->default(false)->after('nitku');
            }
            if (!Schema::hasColumn('company_profiles', 'tax_address')) {
                $table->text('tax_address')->nullable()->after('is_pkp');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'tax_npwp')) {
                $table->string('tax_npwp', 40)->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'tax_nitku')) {
                $table->string('tax_nitku', 40)->nullable()->after('tax_npwp');
            }
            if (!Schema::hasColumn('customers', 'tax_name')) {
                $table->string('tax_name')->nullable()->after('tax_nitku');
            }
            if (!Schema::hasColumn('customers', 'tax_address')) {
                $table->text('tax_address')->nullable()->after('tax_name');
            }
            if (!Schema::hasColumn('customers', 'is_pkp')) {
                $table->boolean('is_pkp')->default(false)->after('tax_address');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'tax_npwp')) {
                $table->string('tax_npwp', 40)->nullable()->after('address');
            }
            if (!Schema::hasColumn('suppliers', 'tax_nitku')) {
                $table->string('tax_nitku', 40)->nullable()->after('tax_npwp');
            }
            if (!Schema::hasColumn('suppliers', 'tax_name')) {
                $table->string('tax_name')->nullable()->after('tax_nitku');
            }
            if (!Schema::hasColumn('suppliers', 'tax_address')) {
                $table->text('tax_address')->nullable()->after('tax_name');
            }
            if (!Schema::hasColumn('suppliers', 'is_pkp')) {
                $table->boolean('is_pkp')->default(false)->after('tax_address');
            }
        });

        Schema::table('ar_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('ar_invoices', 'tax_base_amount')) {
                $table->integer('tax_base_amount')->default(0)->after('ppn_amount');
            }
            if (!Schema::hasColumn('ar_invoices', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_base_amount');
            }
            if (!Schema::hasColumn('ar_invoices', 'tax_transaction_code')) {
                $table->string('tax_transaction_code', 10)->nullable()->after('tax_rate');
            }
            if (!Schema::hasColumn('ar_invoices', 'tax_status')) {
                $table->string('tax_status', 30)->default('not_required')->after('tax_transaction_code');
            }
            if (!Schema::hasColumn('ar_invoices', 'tax_invoice_number')) {
                $table->string('tax_invoice_number', 80)->nullable()->after('tax_status');
            }
            if (!Schema::hasColumn('ar_invoices', 'tax_invoice_date')) {
                $table->date('tax_invoice_date')->nullable()->after('tax_invoice_number');
            }
            if (!Schema::hasColumn('ar_invoices', 'tax_exported_at')) {
                $table->timestamp('tax_exported_at')->nullable()->after('tax_invoice_date');
            }
            if (!Schema::hasColumn('ar_invoices', 'tax_approved_at')) {
                $table->timestamp('tax_approved_at')->nullable()->after('tax_exported_at');
            }
            if (!Schema::hasColumn('ar_invoices', 'tax_error_message')) {
                $table->text('tax_error_message')->nullable()->after('tax_approved_at');
            }
        });

        Schema::table('ap_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('ap_invoices', 'ppn_amount')) {
                $table->integer('ppn_amount')->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('ap_invoices', 'tax_base_amount')) {
                $table->integer('tax_base_amount')->default(0)->after('ppn_amount');
            }
            if (!Schema::hasColumn('ap_invoices', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_base_amount');
            }
            if (!Schema::hasColumn('ap_invoices', 'tax_status')) {
                $table->string('tax_status', 30)->default('not_received')->after('tax_rate');
            }
            if (!Schema::hasColumn('ap_invoices', 'supplier_tax_invoice_number')) {
                $table->string('supplier_tax_invoice_number', 80)->nullable()->after('tax_status');
            }
            if (!Schema::hasColumn('ap_invoices', 'supplier_tax_invoice_date')) {
                $table->date('supplier_tax_invoice_date')->nullable()->after('supplier_tax_invoice_number');
            }
            if (!Schema::hasColumn('ap_invoices', 'tax_exported_at')) {
                $table->timestamp('tax_exported_at')->nullable()->after('supplier_tax_invoice_date');
            }
            if (!Schema::hasColumn('ap_invoices', 'tax_approved_at')) {
                $table->timestamp('tax_approved_at')->nullable()->after('tax_exported_at');
            }
            if (!Schema::hasColumn('ap_invoices', 'tax_error_message')) {
                $table->text('tax_error_message')->nullable()->after('tax_approved_at');
            }
        });

        \DB::table('ar_invoices')
            ->select(['id', 'total_amount', 'ppn_amount', 'tax_transaction_code'])
            ->orderBy('id')
            ->chunkById(200, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $ppnAmount = (int) $invoice->ppn_amount;

                    \DB::table('ar_invoices')->where('id', $invoice->id)->update([
                        'tax_base_amount' => max(0, (int) $invoice->total_amount - $ppnAmount),
                        'tax_status' => $ppnAmount > 0 ? 'draft' : 'not_required',
                        'tax_transaction_code' => $ppnAmount > 0 ? ($invoice->tax_transaction_code ?: '01') : $invoice->tax_transaction_code,
                    ]);
                }
            });

        \DB::table('ap_invoices')
            ->select(['id', 'total_amount', 'ppn_amount'])
            ->orderBy('id')
            ->chunkById(200, function ($invoices) {
                foreach ($invoices as $invoice) {
                    \DB::table('ap_invoices')->where('id', $invoice->id)->update([
                        'tax_base_amount' => max(0, (int) $invoice->total_amount - (int) $invoice->ppn_amount),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Production-safe migration: keep tax audit fields once introduced.
    }
};
