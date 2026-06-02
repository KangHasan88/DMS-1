<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $defaults = [
        'sayur' => 'Sayur',
        'buah' => 'Buah',
        'lauk' => 'Lauk',
        'bumbu' => 'Bumbu',
        'sembako' => 'Sembako',
        'all' => 'Semua Kategori',
    ];

    public function up(): void
    {
        Schema::create('supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $existingCodes = DB::table('suppliers')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $codes = collect(array_keys($this->defaults))->merge($existingCodes)->unique()->values();

        foreach ($codes as $index => $code) {
            DB::table('supplier_categories')->insert([
                'code' => $this->uniqueCode($code),
                'name' => $this->defaults[$code] ?? Str::headline(str_replace(['-', '_'], ' ', $code)),
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_categories');
    }

    private function uniqueCode(string $code): string
    {
        $baseCode = Str::slug($code) ?: 'kategori';
        $uniqueCode = $baseCode;
        $counter = 2;

        while (DB::table('supplier_categories')->where('code', $uniqueCode)->exists()) {
            $uniqueCode = $baseCode . '-' . $counter;
            $counter++;
        }

        return $uniqueCode;
    }
};
