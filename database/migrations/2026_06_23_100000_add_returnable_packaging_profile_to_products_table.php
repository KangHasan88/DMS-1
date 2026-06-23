<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('returnable_package_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('returnable_packages')
                ->nullOnDelete();
            $table->unsignedInteger('returnable_package_quantity_per_unit')
                ->default(0)
                ->after('returnable_package_id');
            $table->string('returnable_package_default_flow', 30)
                ->nullable()
                ->after('returnable_package_quantity_per_unit');

            $table->index(['returnable_package_id', 'returnable_package_default_flow'], 'products_returnable_profile_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_returnable_profile_idx');
            $table->dropConstrainedForeignId('returnable_package_id');
            $table->dropColumn([
                'returnable_package_quantity_per_unit',
                'returnable_package_default_flow',
            ]);
        });
    }
};
