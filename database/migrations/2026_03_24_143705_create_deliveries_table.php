<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kurir_id')->nullable()->constrained('users');
            $table->enum('status', ['assigned', 'picked_up', 'in_transit', 'completed'])->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('proof_image')->nullable();
            $table->text('notes')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index(['kurir_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};