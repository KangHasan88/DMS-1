<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'route_session_id')) {
                $table->foreignId('route_session_id')
                    ->nullable()
                    ->after('salesperson_id')
                    ->constrained('delivery_route_sessions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'route_session_id')) {
                $table->dropConstrainedForeignId('route_session_id');
            }
        });
    }
};
