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
            $table->foreignId('approval_request_id')->nullable()->after('approved_at')->constrained()->nullOnDelete();
            $table->string('approval_status')->default('not_requested')->after('approval_request_id');
            $table->foreignId('rejected_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_note')->nullable()->after('rejected_at');
            $table->index(['approval_status', 'status']);
        });

        DB::table('purchase_orders')
            ->whereIn('status', ['pending', 'partially_received', 'received'])
            ->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['approval_status', 'status']);
            $table->dropConstrainedForeignId('approval_request_id');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['approval_status', 'rejected_at', 'rejection_note']);
        });
    }
};
