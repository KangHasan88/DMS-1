<?php
// database/migrations/2024_01_01_000006_create_login_histories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('platform')->nullable(); // windows, macos, android, ios
            $table->string('browser')->nullable(); // chrome, firefox, etc
            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'login_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('login_histories');
    }
};