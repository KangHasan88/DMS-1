<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delivery_vehicles')) {
            Schema::create('delivery_vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->constrained('company_branches')
                    ->nullOnDelete();
                $table->string('code', 30);
                $table->string('name');
                $table->string('vehicle_type', 30)->default('motorcycle');
                $table->string('plate_number', 30)->nullable();
                $table->string('capacity', 100)->nullable();
                $table->string('status', 30)->default('available');
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['company_branch_id', 'code']);
                $table->index(['company_branch_id', 'status', 'is_active']);
            });
        }

        if (Schema::hasTable('deliveries') && !Schema::hasColumn('deliveries', 'delivery_vehicle_id')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->foreignId('delivery_vehicle_id')
                    ->nullable()
                    ->after('kurir_id')
                    ->constrained('delivery_vehicles')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deliveries') && Schema::hasColumn('deliveries', 'delivery_vehicle_id')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('delivery_vehicle_id');
            });
        }

        Schema::dropIfExists('delivery_vehicles');
    }
};
