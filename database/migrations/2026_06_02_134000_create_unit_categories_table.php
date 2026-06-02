<?php

use App\Models\Unit;
use App\Models\UnitCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        collect(['Berat', 'Jumlah', 'Volume', 'Panjang', 'Lainnya'])
            ->merge(Unit::query()->whereNotNull('category')->pluck('category'))
            ->filter()
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => strtolower($name))
            ->values()
            ->each(function (string $name, int $index) {
                UnitCategory::firstOrCreate(
                    ['name' => $name],
                    [
                        'slug' => UnitCategory::makeUniqueSlug($name),
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_categories');
    }
};
