<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_purchases', function (Blueprint $table) {
            $table->enum('purchase_type', ['cash', 'foc'])->default('cash')->after('total');
            $table->string('reference_po')->nullable()->after('purchase_type'); // referensi PO jika FOC dari PO tertentu
        });
    }

    public function down(): void
    {
        Schema::table('direct_purchases', function (Blueprint $table) {
            $table->dropColumn(['purchase_type', 'reference_po']);
        });
    }
};