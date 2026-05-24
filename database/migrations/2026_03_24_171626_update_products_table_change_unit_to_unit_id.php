<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Unit;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom unit_id dulu
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('category');
        });
        
        // 2. Migrasi data dari unit (string) ke unit_id (foreign)
        // Ambil semua unit yang sudah ada
        $units = [
            'ikat' => ['name' => 'Ikat', 'code' => 'ikat', 'symbol' => 'ik', 'category' => 'Jumlah'],
            'kg' => ['name' => 'Kilogram', 'code' => 'kg', 'symbol' => 'kg', 'category' => 'Berat'],
            'gram' => ['name' => 'Gram', 'code' => 'gram', 'symbol' => 'gr', 'category' => 'Berat'],
            'porsi' => ['name' => 'Porsi', 'code' => 'porsi', 'symbol' => 'porsi', 'category' => 'Jumlah'],
            'butir' => ['name' => 'Butir', 'code' => 'butir', 'symbol' => 'btr', 'category' => 'Jumlah'],
            'ekor' => ['name' => 'Ekor', 'code' => 'ekor', 'symbol' => 'ek', 'category' => 'Jumlah'],
            'bungkus' => ['name' => 'Bungkus', 'code' => 'bungkus', 'symbol' => 'bks', 'category' => 'Jumlah'],
            'liter' => ['name' => 'Liter', 'code' => 'liter', 'symbol' => 'L', 'category' => 'Volume'],
            'ml' => ['name' => 'Mililiter', 'code' => 'ml', 'symbol' => 'ml', 'category' => 'Volume'],
            'ons' => ['name' => 'Ons', 'code' => 'ons', 'symbol' => 'ons', 'category' => 'Berat'],
            'buah' => ['name' => 'Buah', 'code' => 'buah', 'symbol' => 'bh', 'category' => 'Jumlah'],
            'pack' => ['name' => 'Pack', 'code' => 'pack', 'symbol' => 'pack', 'category' => 'Jumlah'],
        ];
        
        foreach ($units as $code => $unitData) {
            $unit = Unit::firstOrCreate(
                ['code' => $code],
                $unitData
            );
            
            // Update products yang menggunakan unit ini
            \DB::table('products')
                ->where('unit', $code)
                ->orWhere('unit', $unitData['name'])
                ->update(['unit_id' => $unit->id]);
        }
        
        // 3. Hapus kolom unit lama setelah migrasi
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }

    public function down(): void
    {
        // Tambah kembali kolom unit
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('category');
        });
        
        // Restore data dari unit_id ke unit
        $products = \DB::table('products')->get();
        foreach ($products as $product) {
            if ($product->unit_id) {
                $unit = Unit::find($product->unit_id);
                if ($unit) {
                    \DB::table('products')
                        ->where('id', $product->id)
                        ->update(['unit' => $unit->code]);
                }
            }
        }
        
        // Hapus kolom unit_id
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};