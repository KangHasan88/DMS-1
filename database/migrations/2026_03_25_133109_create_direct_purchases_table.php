<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supplier_name');
            $table->string('supplier_phone')->nullable();
            $table->date('purchase_date');
            $table->integer('subtotal')->default(0);
            $table->integer('total')->default(0);
            $table->enum('purchase_type', ['cash', 'foc'])->default('cash');
            $table->string('reference_po')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['supplier_id', 'purchase_date']);
            $table->index(['invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_purchases');
    }
};