<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductStock;

class ProductStockSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        
        foreach ($products as $product) {
            ProductStock::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'quantity' => rand(10, 100),
                    'min_stock' => 10,
                    'max_stock' => 200,
                    'last_updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('? Product stocks seeded!');
    }
}