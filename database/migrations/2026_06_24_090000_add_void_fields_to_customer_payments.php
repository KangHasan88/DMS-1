<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->string('status', 20)->default('received')->after('unallocated_amount');
            $table->foreignId('voided_by')->nullable()->after('received_by')->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->text('void_reason')->nullable()->after('voided_at');
            $table->index(['status', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'payment_date']);
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['status', 'voided_at', 'void_reason']);
        });
    }
};
