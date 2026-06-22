<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['company_profile_id', 'code']);
            $table->index(['company_profile_id', 'is_active']);
        });

        Schema::create('delivery_zone_depots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained('delivery_zones')->cascadeOnDelete();
            $table->foreignId('company_branch_id')->constrained('company_branches')->cascadeOnDelete();
            $table->unsignedInteger('priority')->default(1);
            $table->unsignedInteger('max_daily_orders')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['delivery_zone_id', 'company_branch_id']);
            $table->index(['company_branch_id', 'is_active']);
        });

        Schema::create('delivery_zone_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained('delivery_zones')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['delivery_zone_id', 'driver_id']);
        });

        Schema::create('delivery_zone_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained('delivery_zones')->cascadeOnDelete();
            $table->foreignId('delivery_vehicle_id')->constrained('delivery_vehicles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['delivery_zone_id', 'delivery_vehicle_id'], 'delivery_zone_vehicle_unique');
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->foreignId('delivery_zone_id')
                ->nullable()
                ->after('longitude')
                ->constrained('delivery_zones')
                ->nullOnDelete();
            $table->timestamp('coverage_verified_at')->nullable()->after('delivery_zone_id');
            $table->foreignId('coverage_verified_by')
                ->nullable()
                ->after('coverage_verified_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['delivery_zone_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropIndex(['delivery_zone_id', 'is_active']);
            $table->dropConstrainedForeignId('coverage_verified_by');
            $table->dropColumn('coverage_verified_at');
            $table->dropConstrainedForeignId('delivery_zone_id');
        });

        Schema::dropIfExists('delivery_zone_vehicles');
        Schema::dropIfExists('delivery_zone_drivers');
        Schema::dropIfExists('delivery_zone_depots');
        Schema::dropIfExists('delivery_zones');
    }
};
