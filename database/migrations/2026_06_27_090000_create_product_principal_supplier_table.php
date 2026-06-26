<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_principal_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_principal_id')->constrained('product_principals')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_principal_id', 'supplier_id'], 'principal_supplier_unique');
            $table->index(['supplier_id', 'product_principal_id'], 'supplier_principal_index');
        });

        $principalIds = DB::table('product_principals')->pluck('id', 'code');
        $supplierIds = DB::table('suppliers')->pluck('id', 'name');
        $now = now();

        $pairs = [
            ['principal_code' => 'UNILEVER', 'supplier_name' => 'DEMO PT Sumber Air Nusantara'],
            ['principal_code' => 'INDOFOOD', 'supplier_name' => 'DEMO PT Sumber Air Nusantara'],
            ['principal_code' => 'DANONE', 'supplier_name' => 'DEMO PT Sumber Air Nusantara'],
        ];

        foreach ($pairs as $pair) {
            $principalId = $principalIds[$pair['principal_code']] ?? null;
            $supplierId = $supplierIds[$pair['supplier_name']] ?? null;

            if (! $principalId || ! $supplierId) {
                continue;
            }

            DB::table('product_principal_supplier')->updateOrInsert(
                [
                    'product_principal_id' => $principalId,
                    'supplier_id' => $supplierId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_principal_supplier');
    }
};
