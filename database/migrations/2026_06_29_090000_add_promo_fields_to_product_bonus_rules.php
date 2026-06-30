<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_bonus_rules', function (Blueprint $table) {
            $table->string('promo_code')->nullable()->after('id');
            $table->string('promo_name')->nullable()->after('promo_code');
            $table->index(['promo_code'], 'pbr_promo_code_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_bonus_rules', function (Blueprint $table) {
            $table->dropIndex('pbr_promo_code_idx');
            $table->dropColumn(['promo_code', 'promo_name']);
        });
    }
};
