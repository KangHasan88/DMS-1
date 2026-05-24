<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->integer('amount');
            $table->enum('payment_method', ['cash', 'transfer', 'cheque']);
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['consignment_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_payments');
    }
};