<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_limit')->default(0)->after('customer_type');
            $table->unsignedInteger('max_outstanding_orders')->default(0)->after('credit_limit');
            $table->enum('credit_status', ['normal', 'watchlist', 'blocked'])->default('normal')->after('max_outstanding_orders');
            $table->text('credit_notes')->nullable()->after('credit_status');

            $table->index(['credit_status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['credit_status', 'is_active']);
            $table->dropColumn([
                'credit_limit',
                'max_outstanding_orders',
                'credit_status',
                'credit_notes',
            ]);
        });
    }
};
