<?php

use App\Models\ReturnablePackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returnable_package_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('returnable_packages', function (Blueprint $table) {
            $table->foreignId('returnable_package_category_id')
                ->nullable()
                ->after('category')
                ->constrained('returnable_package_categories')
                ->nullOnDelete();
        });

        $defaults = [
            'gallon' => 'Galon',
            'bottle' => 'Botol',
            'crate' => 'Krat',
            'gas_cylinder' => 'Tabung Gas',
            'pallet' => 'Pallet',
            'container' => 'Container',
            'other' => 'Lainnya',
        ];

        foreach ($defaults as $index => $name) {
            DB::table('returnable_package_categories')->insert([
                'code' => $index,
                'name' => $name,
                'is_active' => true,
                'sort_order' => array_search($index, array_keys($defaults), true) + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $categories = DB::table('returnable_package_categories')->pluck('id', 'code');

        DB::table('returnable_packages')
            ->orderBy('id')
            ->get(['id', 'category'])
            ->each(function ($package) use ($categories) {
                $categoryCode = $package->category ?: ReturnablePackage::CATEGORY_OTHER;
                $categoryId = $categories[$categoryCode] ?? $categories[ReturnablePackage::CATEGORY_OTHER] ?? null;

                DB::table('returnable_packages')
                    ->where('id', $package->id)
                    ->update(['returnable_package_category_id' => $categoryId]);
            });
    }

    public function down(): void
    {
        Schema::table('returnable_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('returnable_package_category_id');
        });

        Schema::dropIfExists('returnable_package_categories');
    }
};
