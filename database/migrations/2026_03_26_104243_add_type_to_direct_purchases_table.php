<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('direct_purchases', 'purchase_type')) {
                $table->enum('purchase_type', ['cash', 'foc'])->default('cash')->after('total');
            }

            if (!Schema::hasColumn('direct_purchases', 'reference_po')) {
                $table->string('reference_po')->nullable()->after('purchase_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('direct_purchases', function (Blueprint $table) {
            $table->dropColumn(['purchase_type', 'reference_po']);
        });
    }
};
