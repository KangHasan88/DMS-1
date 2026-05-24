<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('photo')->nullable();
            $table->string('referral_code')->nullable();
            $table->foreignId('referred_by')->nullable()->references('id')->on('customers');
            $table->enum('customer_type', ['regular', 'premium', 'wholesale'])->default('regular');
            $table->integer('total_orders')->default(0);
            $table->bigInteger('total_spent')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['phone', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};