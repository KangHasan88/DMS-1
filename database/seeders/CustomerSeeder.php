<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567801',
                'email' => 'budi@example.com',
                'address' => 'Jl. Merdeka No. 10, Jakarta',
                'customer_type' => 'regular',
            ],
            [
                'name' => 'Siti Aminah',
                'phone' => '081234567802',
                'email' => 'siti@example.com',
                'address' => 'Jl. Sudirman No. 25, Jakarta',
                'customer_type' => 'premium',
            ],
            [
                'name' => 'Ahmad Fauzi',
                'phone' => '081234567803',
                'email' => 'ahmad@example.com',
                'address' => 'Jl. Gatot Subroto No. 5, Jakarta',
                'customer_type' => 'regular',
            ],
            [
                'name' => 'Dewi Kartika',
                'phone' => '081234567804',
                'email' => 'dewi@example.com',
                'address' => 'Jl. Thamrin No. 45, Jakarta',
                'customer_type' => 'wholesale',
            ],
        ];

        foreach ($customers as $data) {
            // Create user account first
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]);
            $user->assignRole('customer');
            
            // Then create customer profile
            $data['user_id'] = $user->id;
            Customer::create($data);
        }
        
        $this->command->info('? ' . count($customers) . ' customers seeded successfully!');
    }
}