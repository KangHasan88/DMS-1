<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('current_quantity')->default(0);
            $table->integer('new_quantity')->default(0);
            $table->integer('quantity_difference')->default(0);
            $table->text('reason');
            $table->foreignId('approval_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('approval_status')->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();

            $table->index(['approval_status', 'created_at']);
            $table->index(['product_id', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_requests');
    }
};
