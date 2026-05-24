<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;

class FixLastLoginSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting fix last login for users...');
        
        // Cari user yang pernah login tapi last_login_at null
        $users = User::whereNull('last_login_at')->get();
        
        $count = 0;
        foreach ($users as $user) {
            // Set last_login_at ke waktu random dalam 7 hari terakhir
            $randomDays = rand(1, 7);
            $randomHours = rand(0, 23);
            $randomMinutes = rand(0, 59);
            
            $randomTime = Carbon::now()
                ->subDays($randomDays)
                ->addHours($randomHours)
                ->addMinutes($randomMinutes);
            
            // Random IP
            $randomIp = rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
            
            $user->update([
                'last_login_at' => $randomTime,
                'last_login_ip' => $randomIp
            ]);
            
            $count++;
            $this->command->info("Updated last login for: {$user->email} -> {$randomTime->format('d M Y H:i')}");
        }
        
        $this->command->info("====================================");
        $this->command->info("? Fixed {$count} users!");
        $this->command->info("====================================");
        
        // Tampilkan hasil
        $fixedUsers = User::whereNotNull('last_login_at')->get();
        $this->command->table(
            ['ID', 'Name', 'Email', 'Last Login', 'IP'],
            $fixedUsers->map(function($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : '-',
                    $user->last_login_ip ?? '-'
                ];
            })->toArray()
        );
    }
}