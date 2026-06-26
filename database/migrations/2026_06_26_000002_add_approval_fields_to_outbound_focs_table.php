<?php

use App\Models\OutboundFoc;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_focs', function (Blueprint $table) {
            $table->foreignId('approval_request_id')->nullable()->after('created_by')->constrained('approval_requests')->nullOnDelete();
            $table->string('approval_status')->default(OutboundFoc::APPROVAL_APPROVED)->after('approval_request_id');
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_note')->nullable()->after('rejected_at');

            $table->index(['approval_status', 'company_branch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('outbound_focs', function (Blueprint $table) {
            $table->dropIndex(['approval_status', 'company_branch_id']);
            $table->dropConstrainedForeignId('approval_request_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn('rejected_at');
            $table->dropColumn('rejection_note');
            $table->dropColumn('approval_status');
        });
    }
};
