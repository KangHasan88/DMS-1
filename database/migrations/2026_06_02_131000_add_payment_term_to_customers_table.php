<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('payment_term', ['cash', 'credit'])->default('cash')->after('customer_type');
            $table->index(['payment_term', 'credit_status']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['payment_term', 'credit_status']);
            $table->dropColumn('payment_term');
        });
    }
};
