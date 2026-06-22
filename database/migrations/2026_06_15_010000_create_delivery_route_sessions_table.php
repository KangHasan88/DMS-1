<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_route_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
            $table->foreignId('company_branch_id')->constrained('company_branches')->cascadeOnDelete();
            $table->foreignId('sales_territory_id')->nullable()->constrained('sales_territories')->nullOnDelete();
            $table->foreignId('salesperson_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delivery_vehicle_id')->constrained('delivery_vehicles')->cascadeOnDelete();
            $table->string('route_code', 40)->unique();
            $table->date('route_date');
            $table->string('selling_mode', 20);
            $table->string('status', 20)->default('planned');
            $table->unsignedInteger('opening_qty')->default(0);
            $table->unsignedInteger('sold_qty')->default(0);
            $table->unsignedInteger('returned_qty')->default(0);
            $table->unsignedInteger('damaged_qty')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_branch_id', 'route_date'], 'route_sessions_branch_date_idx');
            $table->index(['status', 'route_date'], 'route_sessions_status_date_idx');
            $table->index(['sales_territory_id', 'route_date'], 'route_sessions_territory_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_route_sessions');
    }
};
