<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('reference_order')->nullable();
            $table->enum('return_type', ['defect', 'wrong_item', 'expired', 'customer_return', 'other']);
            $table->text('reason_detail')->nullable();
            $table->enum('action', ['replace', 'refund', 'store_credit']);
            $table->string('replacement_order')->nullable();
            $table->date('return_date');
            $table->integer('subtotal')->default(0);
            $table->integer('total')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['return_number', 'return_date']);
            $table->index(['customer_name']);
            $table->index(['reference_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_returns');
    }
};