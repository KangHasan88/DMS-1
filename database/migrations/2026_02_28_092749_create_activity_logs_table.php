<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('activity_logs') || Schema::hasTable('activity_log')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            
            // Ini otomatis bikin index: subject_type, subject_id
            $table->nullableMorphs('subject');
            
            // Ini otomatis bikin index: causer_type, causer_id
            $table->nullableMorphs('causer');
            
            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            // HANYA index tambahan yang belum ada
            $table->index('log_name');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
