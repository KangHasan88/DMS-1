<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Pedagang Sayur Makmur',
                'phone' => '081234567901',
                'alternate_phone' => '081234567902',
                'market_name' => 'Pasar Baru',
                'stall_number' => 'A-01',
                'address' => 'Pasar Baru, Blok A No. 1, Jakarta',
                'category' => 'sayur',
                'specialty' => 'Sayur Organik',
                'min_order' => 50000,
                'notes' => 'Supplier sayur langganan, selalu fresh',
            ],
            [
                'name' => 'Bapak Daging',
                'phone' => '081234567903',
                'alternate_phone' => '081234567904',
                'market_name' => 'Pasar Lama',
                'stall_number' => 'B-12',
                'address' => 'Pasar Lama, Blok B No. 12, Jakarta',
                'category' => 'lauk',
                'specialty' => 'Ayam Potong Segar',
                'min_order' => 100000,
                'notes' => 'Ayam fresh, potong sesuai request',
            ],
            [
                'name' => 'Buah Segar Jaya',
                'phone' => '081234567905',
                'market_name' => 'Pasar Baru',
                'stall_number' => 'C-05',
                'address' => 'Pasar Baru, Blok C No. 5, Jakarta',
                'category' => 'buah',
                'specialty' => 'Buah Import',
                'min_order' => 75000,
                'notes' => 'Buah dari import langsung',
            ],
            [
                'name' => 'Bumbu Dapur Sari',
                'phone' => '081234567906',
                'market_name' => 'Pasar Lama',
                'stall_number' => 'D-08',
                'address' => 'Pasar Lama, Blok D No. 8, Jakarta',
                'category' => 'bumbu',
                'specialty' => 'Bumbu Dapur Lengkap',
                'min_order' => 25000,
                'notes' => 'Bawang, cabai, rempah lengkap',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
        
        $this->command->info('? ' . count($suppliers) . ' suppliers seeded successfully!');
    }
}