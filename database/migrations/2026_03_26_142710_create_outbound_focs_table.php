<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_focs', function (Blueprint $table) {
            $table->id();
            $table->string('foc_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('address')->nullable();
            $table->date('foc_date');
            $table->enum('reason', ['promotion', 'sample', 'support', 'compensation', 'other']);
            $table->text('reason_detail')->nullable();
            $table->string('reference_order')->nullable();
            $table->integer('subtotal')->default(0);
            $table->integer('total')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['foc_number', 'foc_date']);
            $table->index(['customer_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_focs');
    }
};