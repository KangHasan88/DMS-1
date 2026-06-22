<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'company_branch_id')) {
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('company_branches')
                    ->nullOnDelete();
            }
        });

        Schema::table('ap_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('ap_invoices', 'company_branch_id')) {
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('company_branches')
                    ->nullOnDelete();
            }
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_payments', 'company_branch_id')) {
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('company_branches')
                    ->nullOnDelete();
            }
        });

        $defaultBranchId = DB::table('company_branches')
            ->where('is_invoice_default', true)
            ->value('id')
            ?: DB::table('company_branches')->orderBy('id')->value('id');

        if ($defaultBranchId) {
            if (
                Schema::hasColumn('users', 'company_branch_id')
                && Schema::hasColumn('purchase_orders', 'created_by')
            ) {
                DB::table('purchase_orders')
                    ->leftJoin('users', 'users.id', '=', 'purchase_orders.created_by')
                    ->select('purchase_orders.id', 'users.company_branch_id as user_branch_id')
                    ->whereNull('purchase_orders.company_branch_id')
                    ->orderBy('purchase_orders.id')
                    ->get()
                    ->each(function ($purchaseOrder) use ($defaultBranchId) {
                        DB::table('purchase_orders')
                            ->where('id', $purchaseOrder->id)
                            ->update(['company_branch_id' => $purchaseOrder->user_branch_id ?: $defaultBranchId]);
                    });
            } else {
                DB::table('purchase_orders')
                    ->whereNull('company_branch_id')
                    ->update(['company_branch_id' => $defaultBranchId]);
            }

            if (Schema::hasColumn('users', 'company_branch_id')) {
                DB::table('ap_invoices')
                    ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'ap_invoices.purchase_order_id')
                    ->leftJoin('users', 'users.id', '=', 'ap_invoices.issued_by')
                    ->select(
                        'ap_invoices.id',
                        'purchase_orders.company_branch_id as purchase_order_branch_id',
                        'users.company_branch_id as user_branch_id'
                    )
                    ->whereNull('ap_invoices.company_branch_id')
                    ->orderBy('ap_invoices.id')
                    ->get()
                    ->each(function ($invoice) use ($defaultBranchId) {
                        DB::table('ap_invoices')
                            ->where('id', $invoice->id)
                            ->update([
                                'company_branch_id' => $invoice->purchase_order_branch_id
                                    ?: $invoice->user_branch_id
                                    ?: $defaultBranchId,
                            ]);
                    });
            } else {
                DB::table('ap_invoices')
                    ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'ap_invoices.purchase_order_id')
                    ->select('ap_invoices.id', 'purchase_orders.company_branch_id as purchase_order_branch_id')
                    ->whereNull('ap_invoices.company_branch_id')
                    ->orderBy('ap_invoices.id')
                    ->get()
                    ->each(function ($invoice) use ($defaultBranchId) {
                        DB::table('ap_invoices')
                            ->where('id', $invoice->id)
                            ->update(['company_branch_id' => $invoice->purchase_order_branch_id ?: $defaultBranchId]);
                    });
            }

            DB::table('supplier_payments')
                ->join('supplier_payment_allocations', 'supplier_payment_allocations.supplier_payment_id', '=', 'supplier_payments.id')
                ->join('ap_invoices', 'ap_invoices.id', '=', 'supplier_payment_allocations.ap_invoice_id')
                ->select('supplier_payments.id', 'ap_invoices.company_branch_id as invoice_branch_id')
                ->whereNull('supplier_payments.company_branch_id')
                ->orderBy('supplier_payments.id')
                ->get()
                ->each(function ($payment) {
                    if ($payment->invoice_branch_id) {
                        DB::table('supplier_payments')
                            ->where('id', $payment->id)
                            ->update(['company_branch_id' => $payment->invoice_branch_id]);
                    }
                });

            if (Schema::hasColumn('users', 'company_branch_id')) {
                DB::table('supplier_payments')
                    ->leftJoin('users', 'users.id', '=', 'supplier_payments.paid_by')
                    ->select('supplier_payments.id', 'users.company_branch_id as user_branch_id')
                    ->whereNull('supplier_payments.company_branch_id')
                    ->orderBy('supplier_payments.id')
                    ->get()
                    ->each(function ($payment) use ($defaultBranchId) {
                        DB::table('supplier_payments')
                            ->where('id', $payment->id)
                            ->update(['company_branch_id' => $payment->user_branch_id ?: $defaultBranchId]);
                    });
            } else {
                DB::table('supplier_payments')
                    ->whereNull('company_branch_id')
                    ->update(['company_branch_id' => $defaultBranchId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_payments', 'company_branch_id')) {
                $table->dropConstrainedForeignId('company_branch_id');
            }
        });

        Schema::table('ap_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('ap_invoices', 'company_branch_id')) {
                $table->dropConstrainedForeignId('company_branch_id');
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'company_branch_id')) {
                $table->dropConstrainedForeignId('company_branch_id');
            }
        });
    }
};
