<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Kangkung', 'category' => 'Sayur', 'unit' => 'ikat', 'price' => 5000, 'base_price' => 3000],
            ['name' => 'Bayam', 'category' => 'Sayur', 'unit' => 'ikat', 'price' => 4000, 'base_price' => 2500],
            ['name' => 'Wortel', 'category' => 'Sayur', 'unit' => 'kg', 'price' => 12000, 'base_price' => 8000],
            ['name' => 'Kentang', 'category' => 'Sayur', 'unit' => 'kg', 'price' => 15000, 'base_price' => 10000],
            ['name' => 'Cabai Merah', 'category' => 'Bumbu', 'unit' => 'kg', 'price' => 50000, 'base_price' => 35000],
            ['name' => 'Bawang Merah', 'category' => 'Bumbu', 'unit' => 'kg', 'price' => 30000, 'base_price' => 20000],
            ['name' => 'Daging Ayam', 'category' => 'Lauk', 'unit' => 'kg', 'price' => 35000, 'base_price' => 28000],
            ['name' => 'Telur', 'category' => 'Lauk', 'unit' => 'butir', 'price' => 3000, 'base_price' => 2000],
            ['name' => 'Apel', 'category' => 'Buah', 'unit' => 'kg', 'price' => 25000, 'base_price' => 18000],
            ['name' => 'Jeruk', 'category' => 'Buah', 'unit' => 'kg', 'price' => 20000, 'base_price' => 15000],
        ];
        
        foreach ($products as $product) {
            Product::create($product);
        }
        
        $this->command->info('10 products seeded successfully!');
    }
}