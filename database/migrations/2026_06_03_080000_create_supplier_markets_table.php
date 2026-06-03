<?php

use App\Models\Supplier;
use App\Models\SupplierMarket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_markets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Supplier::query()
            ->whereNotNull('market_name')
            ->pluck('market_name')
            ->merge(['Pasar Baru', 'Pasar Lama'])
            ->filter()
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => strtolower($name))
            ->values()
            ->each(function (string $name, int $index) {
                SupplierMarket::firstOrCreate(
                    ['name' => $name],
                    [
                        'slug' => SupplierMarket::makeUniqueSlug($name),
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_markets');
    }
};
