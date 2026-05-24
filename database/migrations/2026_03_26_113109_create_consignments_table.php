<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->string('cn_number')->unique();
            $table->foreignId('supplier_id')->constrained();
            $table->date('consignment_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['active', 'partial', 'returned', 'completed'])->default('active');
            $table->integer('total_items')->default(0);
            $table->integer('total_sold')->default(0);
            $table->integer('total_returned')->default(0);
            $table->bigInteger('total_value')->default(0);
            $table->bigInteger('total_paid')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['supplier_id', 'status']);
            $table->index(['cn_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignments');
    }
};