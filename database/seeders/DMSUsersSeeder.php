<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DMSUsersSeeder extends Seeder
{
    public function run()
    {
        // Pastikan roles sudah ada
        $roles = ['super-admin', 'admin', 'manager', 'sales', 'warehouse', 'finance', 'customer'];
        
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Data users sesuai request
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@dms.com',
                'password' => 'password',
                'username' => 'superadmin',
                'is_active' => true,
                'role' => 'super-admin'
            ],
            [
                'name' => 'Admin Distributor',
                'email' => 'admin@dms.com',
                'password' => 'password',
                'username' => 'admin',
                'is_active' => true,
                'role' => 'admin'
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@dms.com',
                'password' => 'password',
                'username' => 'manager',
                'is_active' => true,
                'role' => 'manager'
            ],
            [
                'name' => 'Ahmad Sales',
                'email' => 'ahmad@dms.com',
                'password' => 'password',
                'username' => 'ahmad.sales',
                'is_active' => true,
                'role' => 'sales'
            ],
            [
                'name' => 'Charlie Warehouse',
                'email' => 'charlie@dms.com',
                'password' => 'password',
                'username' => 'charlie.wh',
                'is_active' => true,
                'role' => 'warehouse'
            ],
            [
                'name' => 'Dina Finance',
                'email' => 'dina@dms.com',
                'password' => 'password',
                'username' => 'dina.finance',
                'is_active' => true,
                'role' => 'finance'
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);
            
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            
            $user->assignRole($role);
        }

        $this->command->info('6 DMS users created successfully!');
    }
}
