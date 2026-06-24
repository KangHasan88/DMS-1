<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_payments', 'chart_account_id')) {
                $table->foreignId('chart_account_id')
                    ->nullable()
                    ->after('payment_method')
                    ->constrained('chart_accounts')
                    ->nullOnDelete();
            }
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_payments', 'chart_account_id')) {
                $table->foreignId('chart_account_id')
                    ->nullable()
                    ->after('payment_method')
                    ->constrained('chart_accounts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_payments', 'chart_account_id')) {
                $table->dropConstrainedForeignId('chart_account_id');
            }
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('customer_payments', 'chart_account_id')) {
                $table->dropConstrainedForeignId('chart_account_id');
            }
        });
    }
};
