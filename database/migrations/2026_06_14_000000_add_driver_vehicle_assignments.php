<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('driver_vehicle_assignments')) {
            Schema::create('driver_vehicle_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('delivery_vehicle_id')->constrained('delivery_vehicles')->cascadeOnDelete();
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['driver_id', 'ended_at']);
                $table->index(['delivery_vehicle_id', 'ended_at']);
            });
        }

        if (Schema::hasTable('deliveries') && !Schema::hasColumn('deliveries', 'vehicle_override_reason')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->string('vehicle_override_reason')->nullable()->after('delivery_vehicle_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deliveries') && Schema::hasColumn('deliveries', 'vehicle_override_reason')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->dropColumn('vehicle_override_reason');
            });
        }

        Schema::dropIfExists('driver_vehicle_assignments');
    }
};
