<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;

class ResetLastLoginSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Resetting last login data...');
        
        // Set semua last_login_at menjadi null dulu
        User::query()->update([
            'last_login_at' => null,
            'last_login_ip' => null
        ]);
        
        $this->command->info('All last login data has been reset!');
        $this->command->warn('Please login again to record real last login data.');
    }
}