<?php

use App\Models\AccountingPeriodLock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_period_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_branch_id')->nullable()->constrained('company_branches')->nullOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('status', 20)->default(AccountingPeriodLock::STATUS_LOCKED);
            $table->text('reason')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->text('unlock_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'date_from', 'date_to']);
            $table->index(['company_branch_id', 'status', 'date_from', 'date_to'], 'period_locks_branch_status_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_period_locks');
    }
};
