<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique('product_id');
            $table->index(['product_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};