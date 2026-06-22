<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('period_label', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_branch_id', 'is_active', 'sort_order']);
        });

        DB::table('delivery_time_slots')->insert([
            [
                'name' => 'Pagi',
                'start_time' => '06:00:00',
                'end_time' => '09:00:00',
                'period_label' => 'Pagi',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Siang',
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'period_label' => 'Siang',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sore',
                'start_time' => '12:00:00',
                'end_time' => '15:00:00',
                'period_label' => 'Sore',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_time_slots');
    }
};
