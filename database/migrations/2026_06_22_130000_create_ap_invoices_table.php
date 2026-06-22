<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('purchase_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('issued');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('paid_amount')->default(0);
            $table->bigInteger('outstanding_amount')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('ap_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ap_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(0);
            $table->bigInteger('unit_price')->default(0);
            $table->bigInteger('line_total')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_invoice_items');
        Schema::dropIfExists('ap_invoices');
    }
};
