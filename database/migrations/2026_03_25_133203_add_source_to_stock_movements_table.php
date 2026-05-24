<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Sumber stock masuk
            $table->string('source_type')->nullable()->after('order_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            
            // Index untuk query
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
