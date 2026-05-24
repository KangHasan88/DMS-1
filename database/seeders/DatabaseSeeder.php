<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ========================================
        // 1. CREATE PERMISSIONS FOR KURMIGO
        // ========================================
        
        $permissions = [
            // Dashboard
            ['name' => 'view-dashboard', 'guard_name' => 'web', 'module' => 'Dashboard', 'display_name' => 'View Dashboard'],
            
            // Product Management
            ['name' => 'view-products', 'guard_name' => 'web', 'module' => 'Products', 'display_name' => 'View Products'],
            ['name' => 'create-products', 'guard_name' => 'web', 'module' => 'Products', 'display_name' => 'Create Products'],
            ['name' => 'edit-products', 'guard_name' => 'web', 'module' => 'Products', 'display_name' => 'Edit Products'],
            ['name' => 'delete-products', 'guard_name' => 'web', 'module' => 'Products', 'display_name' => 'Delete Products'],
            
            // Unit Management
            ['name' => 'view-units', 'guard_name' => 'web', 'module' => 'Units', 'display_name' => 'View Units'],
            ['name' => 'create-units', 'guard_name' => 'web', 'module' => 'Units', 'display_name' => 'Create Units'],
            ['name' => 'edit-units', 'guard_name' => 'web', 'module' => 'Units', 'display_name' => 'Edit Units'],
            ['name' => 'delete-units', 'guard_name' => 'web', 'module' => 'Units', 'display_name' => 'Delete Units'],
            
            // Customer Management
            ['name' => 'view-customers', 'guard_name' => 'web', 'module' => 'Customers', 'display_name' => 'View Customers'],
            ['name' => 'create-customers', 'guard_name' => 'web', 'module' => 'Customers', 'display_name' => 'Create Customers'],
            ['name' => 'edit-customers', 'guard_name' => 'web', 'module' => 'Customers', 'display_name' => 'Edit Customers'],
            ['name' => 'delete-customers', 'guard_name' => 'web', 'module' => 'Customers', 'display_name' => 'Delete Customers'],
            
            // Supplier Management
            ['name' => 'view-suppliers', 'guard_name' => 'web', 'module' => 'Suppliers', 'display_name' => 'View Suppliers'],
            ['name' => 'create-suppliers', 'guard_name' => 'web', 'module' => 'Suppliers', 'display_name' => 'Create Suppliers'],
            ['name' => 'edit-suppliers', 'guard_name' => 'web', 'module' => 'Suppliers', 'display_name' => 'Edit Suppliers'],
            ['name' => 'delete-suppliers', 'guard_name' => 'web', 'module' => 'Suppliers', 'display_name' => 'Delete Suppliers'],
            
            // Order Management
            ['name' => 'view-orders', 'guard_name' => 'web', 'module' => 'Orders', 'display_name' => 'View Orders'],
            ['name' => 'create-orders', 'guard_name' => 'web', 'module' => 'Orders', 'display_name' => 'Create Orders'],
            ['name' => 'edit-orders', 'guard_name' => 'web', 'module' => 'Orders', 'display_name' => 'Edit Orders'],
            ['name' => 'delete-orders', 'guard_name' => 'web', 'module' => 'Orders', 'display_name' => 'Delete Orders'],
            ['name' => 'process-orders', 'guard_name' => 'web', 'module' => 'Orders', 'display_name' => 'Process Orders'],
            
            // Delivery Management
            ['name' => 'view-deliveries', 'guard_name' => 'web', 'module' => 'Deliveries', 'display_name' => 'View Deliveries'],
            ['name' => 'update-delivery-status', 'guard_name' => 'web', 'module' => 'Deliveries', 'display_name' => 'Update Delivery Status'],
            
            // Reports
            ['name' => 'view-sales-reports', 'guard_name' => 'web', 'module' => 'Reports', 'display_name' => 'View Sales Reports'],
            ['name' => 'view-delivery-reports', 'guard_name' => 'web', 'module' => 'Reports', 'display_name' => 'View Delivery Reports'],
            ['name' => 'view-financial-reports', 'guard_name' => 'web', 'module' => 'Reports', 'display_name' => 'View Financial Reports'],
            ['name' => 'export-reports', 'guard_name' => 'web', 'module' => 'Reports', 'display_name' => 'Export Reports'],
            
            // User Management
            ['name' => 'view-users', 'guard_name' => 'web', 'module' => 'Users', 'display_name' => 'View Users'],
            ['name' => 'create-users', 'guard_name' => 'web', 'module' => 'Users', 'display_name' => 'Create Users'],
            ['name' => 'edit-users', 'guard_name' => 'web', 'module' => 'Users', 'display_name' => 'Edit Users'],
            ['name' => 'delete-users', 'guard_name' => 'web', 'module' => 'Users', 'display_name' => 'Delete Users'],
            
            // Role Management
            ['name' => 'view-roles', 'guard_name' => 'web', 'module' => 'Roles', 'display_name' => 'View Roles'],
            ['name' => 'create-roles', 'guard_name' => 'web', 'module' => 'Roles', 'display_name' => 'Create Roles'],
            ['name' => 'edit-roles', 'guard_name' => 'web', 'module' => 'Roles', 'display_name' => 'Edit Roles'],
            ['name' => 'delete-roles', 'guard_name' => 'web', 'module' => 'Roles', 'display_name' => 'Delete Roles'],
            ['name' => 'assign-permissions', 'guard_name' => 'web', 'module' => 'Roles', 'display_name' => 'Assign Permissions'],
            
            // Activity Logs
            ['name' => 'view-activity-logs', 'guard_name' => 'web', 'module' => 'System', 'display_name' => 'View Activity Logs'],
            ['name' => 'clear-activity-logs', 'guard_name' => 'web', 'module' => 'System', 'display_name' => 'Clear Activity Logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // ========================================
        // 2. CREATE ROLES
        // ========================================
        
        $roles = [
            'super-admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'operator' => 'Operator (Tim Belanja & Repack)',
            'kurir' => 'Kurir',
            'customer' => 'Customer',
        ];

        $createdRoles = [];
        foreach ($roles as $roleName => $roleDesc) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);
            $createdRoles[$roleName] = $role;
            $this->command->info("? Role created: {$roleName} - {$roleDesc}");
        }

        // ========================================
        // 3. ASSIGN PERMISSIONS TO ROLES
        // ========================================
        
        // Super Admin - semua permission
        $createdRoles['super-admin']->givePermissionTo(Permission::all());
        
        // Admin - hampir semua kecuali role management
        $createdRoles['admin']->givePermissionTo([
            'view-dashboard',
            'view-products', 'create-products', 'edit-products', 'delete-products',
            'view-units', 'create-units', 'edit-units', 'delete-units',
            'view-customers', 'create-customers', 'edit-customers', 'delete-customers',
            'view-suppliers', 'create-suppliers', 'edit-suppliers', 'delete-suppliers',
            'view-orders', 'create-orders', 'edit-orders', 'delete-orders', 'process-orders',
            'view-deliveries', 'update-delivery-status',
            'view-sales-reports', 'view-delivery-reports', 'view-financial-reports', 'export-reports',
            'view-users', 'create-users', 'edit-users', 'delete-users',
            'view-activity-logs',
        ]);
        
        // Operator - proses order (belanja & repack)
        $createdRoles['operator']->givePermissionTo([
            'view-dashboard',
            'view-products',
            'view-orders', 'process-orders',
            'view-deliveries',
        ]);
        
        // Kurir - update delivery status
        $createdRoles['kurir']->givePermissionTo([
            'view-dashboard',
            'view-deliveries', 'update-delivery-status',
        ]);
        
        // Customer - tidak ada permission khusus (default)
        
        // ========================================
        // 4. CREATE USERS
        // ========================================
        
        // Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@kurmigo.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password123'),
                'phone' => '081234567890',
                'gender' => 'male',
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole('super-admin');
        $this->command->info("? User created: superadmin@kurmigo.com (Super Admin)");

        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@kurmigo.com'],
            [
                'name' => 'Admin KurmiGO',
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'phone' => '081234567891',
                'gender' => 'male',
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');
        $this->command->info("? User created: admin@kurmigo.com (Admin)");

        // Operator (Tim Belanja & Repack)
        $operator = User::firstOrCreate(
            ['email' => 'operator@kurmigo.com'],
            [
                'name' => 'Tim Belanja',
                'username' => 'operator',
                'password' => Hash::make('password123'),
                'phone' => '081234567892',
                'gender' => 'male',
                'is_active' => true,
            ]
        );
        $operator->assignRole('operator');
        $this->command->info("? User created: operator@kurmigo.com (Operator)");

        // Kurir
        $kurir = User::firstOrCreate(
            ['email' => 'kurir@kurmigo.com'],
            [
                'name' => 'Kurir 1',
                'username' => 'kurir1',
                'password' => Hash::make('password123'),
                'phone' => '081234567893',
                'gender' => 'male',
                'is_active' => true,
            ]
        );
        $kurir->assignRole('kurir');
        $this->command->info("? User created: kurir@kurmigo.com (Kurir)");

        // ========================================
        // 5. CREATE UNITS (via UnitSeeder)
        // ========================================
        $this->call(UnitSeeder::class);
        
        // ========================================
        // 6. CREATE PRODUCTS (via ProductSeeder)
        // ========================================
        $this->call(ProductSeeder::class);
        
        // ========================================
        // 7. CREATE CUSTOMERS
        // ========================================
        
        $customers = [
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567801',
                'email' => 'budi@customer.com',
                'address' => 'Jl. Merdeka No. 10, Jakarta',
                'customer_type' => 'regular',
            ],
            [
                'name' => 'Siti Aminah',
                'phone' => '081234567802',
                'email' => 'siti@customer.com',
                'address' => 'Jl. Sudirman No. 25, Jakarta',
                'customer_type' => 'premium',
            ],
            [
                'name' => 'Ahmad Fauzi',
                'phone' => '081234567803',
                'email' => 'ahmad@customer.com',
                'address' => 'Jl. Gatot Subroto No. 5, Jakarta',
                'customer_type' => 'regular',
            ],
            [
                'name' => 'Dewi Kartika',
                'phone' => '081234567804',
                'email' => 'dewi@customer.com',
                'address' => 'Jl. Thamrin No. 45, Jakarta',
                'customer_type' => 'wholesale',
            ],
            [
                'name' => 'Rudi Hermawan',
                'phone' => '081234567805',
                'email' => 'rudi@customer.com',
                'address' => 'Jl. Diponegoro No. 12, Jakarta',
                'customer_type' => 'regular',
            ],
        ];

        foreach ($customers as $data) {
            // Create user account for customer
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => strtolower(str_replace(' ', '', $data['name'])),
                    'password' => Hash::make('password123'),
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'is_active' => true,
                ]
            );
            $user->assignRole('customer');
            
            // Create customer profile
            Customer::updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'address' => $data['address'],
                    'customer_type' => $data['customer_type'],
                    'is_active' => true,
                ]
            );
        }
        $this->command->info("? " . count($customers) . " customers created!");

        // ========================================
        // 8. CREATE SUPPLIERS
        // ========================================
        
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
                'alternate_phone' => null,
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
                'alternate_phone' => '081234567907',
                'market_name' => 'Pasar Lama',
                'stall_number' => 'D-08',
                'address' => 'Pasar Lama, Blok D No. 8, Jakarta',
                'category' => 'bumbu',
                'specialty' => 'Bumbu Dapur Lengkap',
                'min_order' => 25000,
                'notes' => 'Bawang, cabai, rempah lengkap',
            ],
            [
                'name' => 'Ikan Segar Langsung',
                'phone' => '081234567908',
                'alternate_phone' => null,
                'market_name' => 'Pasar Ikan',
                'stall_number' => 'E-03',
                'address' => 'Pasar Ikan, Blok E No. 3, Jakarta',
                'category' => 'lauk',
                'specialty' => 'Ikan Laut Segar',
                'min_order' => 150000,
                'notes' => 'Ikan langsung dari nelayan',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['phone' => $supplier['phone']],
                $supplier
            );
        }
        $this->command->info("? " . count($suppliers) . " suppliers created!");

        // ========================================
        // 9. FINAL MESSAGE
        // ========================================
        
        $this->command->info('========================================');
        $this->command->info('?? DATABASE SEEDER KURMIGO BERHASIL!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('?? LOGIN CREDENTIALS:');
        $this->command->info('----------------------------------------');
        $this->command->info('?? Super Admin: superadmin@kurmigo.com / password123');
        $this->command->info('?? Admin: admin@kurmigo.com / password123');
        $this->command->info('?? Operator: operator@kurmigo.com / password123');
        $this->command->info('?? Kurir: kurir@kurmigo.com / password123');
        $this->command->info('?? Customer: budi@customer.com / password123');
        $this->command->info('----------------------------------------');
        $this->command->info('');
        $this->command->info('?? DATA SUMMARY:');
        $this->command->info('----------------------------------------');
        $this->command->info('? Roles: ' . count($roles) . ' roles');
        $this->command->info('? Permissions: ' . count($permissions) . ' permissions');
        $this->command->info('? Users: ' . User::count() . ' users');
        $this->command->info('? Customers: ' . Customer::count() . ' customers');
        $this->command->info('? Suppliers: ' . Supplier::count() . ' suppliers');
        $this->command->info('? Units: ' . Unit::count() . ' units');
        $this->command->info('? Products: ' . Product::count() . ' products');
        $this->command->info('========================================');
    }
}