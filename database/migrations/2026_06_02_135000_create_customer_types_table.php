<?php

use App\Models\Customer;
use App\Models\CustomerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $defaults = collect([
            'regular' => 'Regular',
            'premium' => 'Premium',
            'wholesale' => 'Wholesale',
        ]);

        Customer::query()
            ->whereNotNull('customer_type')
            ->pluck('customer_type')
            ->filter()
            ->unique()
            ->each(function (string $code) use (&$defaults) {
                if (!$defaults->has($code)) {
                    $defaults->put($code, Str::headline(str_replace(['-', '_'], ' ', $code)));
                }
            });

        $defaults->values()->each(function (string $name, int $index) {
            CustomerType::firstOrCreate(
                ['code' => CustomerType::makeUniqueCode($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_types');
    }
};
