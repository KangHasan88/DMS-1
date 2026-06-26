<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============= DEFINE PERMISSIONS =============
        $permissions = [
            // DASHBOARD
            'view dashboard',
            
            // USER MANAGEMENT
            'view users',
            'create users',
            'edit users',
            'delete users',
            'activate users',

            // APPROVAL WORKFLOW
            'view approvals',
            'manage approvals',
            
            // ROLE & PERMISSION
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'assign permissions',
            
            // MASTER DATA
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'view units',
            'create units',
            'edit units',
            'delete units',
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            'view suppliers',
            'create suppliers',
            'edit suppliers',
            'delete suppliers',
            
            // SALES
            'view sales team',
            'manage sales team',
            'view visit plan',
            'create visit plan',
            'edit visit plan',
            'delete visit plan',
            'view target commission',
            'manage target commission',
            
            // ORDER
            'view sales order',
            'create sales order',
            'edit sales order',
            'delete sales order',
            'process orders',
            'view order history',
            'view deliveries',
            'create deliveries',
            'edit deliveries',
            'delete deliveries',
            'process deliveries',
            
            // INVENTORY
            'view warehouse',
            'manage warehouse',
            'view stock movement',
            'create stock movement',
            'view purchase order',
            'create purchase order',
            'edit purchase order',
            'delete purchase order',
            'view direct purchase',
            'create direct purchase',
            'edit direct purchase',
            'delete direct purchase',
            'view consignments',
            'create consignments',
            'edit consignments',
            'delete consignments',
            'view outbound foc',
            'create outbound foc',
            'edit outbound foc',
            'delete outbound foc',
            'view outbound return',
            'create outbound return',
            'edit outbound return',
            'delete outbound return',
            'view stock opname',
            'do stock opname',
            'view returnable packages',
            'manage returnable packages',
            
            // FINANCE
            'view invoice',
            'create invoice',
            'edit invoice',
            'delete invoice',
            'view payment',
            'process payment',
            'view piutang',
            'manage piutang',

            // ACCOUNTING
            'view chart of accounts',
            'manage chart of accounts',
            'view journal entries',
            'manage journal entries',
            'view general ledger',
            
            // REPORTS
            'view sales report',
            'view inventory report',
            'view delivery report',
            'view financial report',
            'export reports',
            
            // MOBILE
            'download apk',
            'view sync status',
            
            // SYSTEM
            'view company profile',
            'edit company profile',
            'view settings',
            'edit settings',
            'view backup',
            'manage backup',
            'view logs',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ============= CREATE ROLES =============
        
        // ROLE: SUPER ADMIN (ALL ACCESS)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());
        
        // ROLE: ADMIN (ALL ACCESS KECUALI BEBERAPA)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            // Hampir semua kecuali system critical
            'view dashboard',
            'view users', 'create users', 'edit users', 'activate users',
            'view roles',
            'view products', 'create products', 'edit products', 'delete products',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view units', 'create units', 'edit units', 'delete units',
            'view customers', 'create customers', 'edit customers', 'delete customers',
            'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers',
            'view sales team', 'manage sales team',
            'view visit plan', 'create visit plan', 'edit visit plan',
            'view target commission', 'manage target commission',
            'view sales order', 'create sales order', 'edit sales order', 'process orders',
            'view order history',
            'view deliveries', 'create deliveries', 'edit deliveries', 'delete deliveries', 'process deliveries',
            'view warehouse', 'manage warehouse',
            'view stock movement', 'create stock movement',
            'view purchase order', 'create purchase order', 'edit purchase order',
            'view direct purchase', 'create direct purchase', 'edit direct purchase', 'delete direct purchase',
            'view consignments', 'create consignments', 'edit consignments', 'delete consignments',
            'view outbound foc', 'create outbound foc', 'edit outbound foc', 'delete outbound foc',
            'view outbound return', 'create outbound return', 'edit outbound return', 'delete outbound return',
            'view stock opname', 'do stock opname',
            'view returnable packages', 'manage returnable packages',
            'view invoice', 'create invoice', 'edit invoice',
            'view payment', 'process payment',
            'view piutang', 'manage piutang',
            'view chart of accounts', 'manage chart of accounts',
            'view journal entries', 'manage journal entries',
            'view general ledger',
            'view sales report', 'view inventory report', 'view delivery report', 'view financial report', 'export reports',
            'download apk', 'view sync status',
            'view company profile', 'edit company profile',
            'view settings', 'view logs',
            'view approvals', 'manage approvals',
        ]);
        
        // ROLE: SALES
        $sales = Role::firstOrCreate(['name' => 'sales']);
        $sales->syncPermissions([
            'view dashboard',
            'view products',
            'view customers', 'create customers', 'edit customers',
            'view visit plan', 'create visit plan', 'edit visit plan',
            'view sales order', 'create sales order', 'edit sales order',
            'view order history',
            'view deliveries',
            'view sales report',
            'download apk',
            'view sync status',
        ]);
        
        // ROLE: MANAGER
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view dashboard',
            'view users',
            'view products', 'create products', 'edit products',
            'view categories', 'create categories', 'edit categories',
            'view units', 'create units', 'edit units',
            'view customers', 'create customers', 'edit customers',
            'view suppliers',
            'view sales team',
            'view visit plan', 'create visit plan', 'edit visit plan', 'delete visit plan',
            'view target commission', 'manage target commission',
            'view sales order', 'process orders',
            'view order history',
            'view deliveries', 'create deliveries', 'edit deliveries', 'process deliveries',
            'view warehouse',
            'view stock movement',
            'view purchase order',
            'view direct purchase',
            'view consignments',
            'view outbound foc',
            'view outbound return',
            'view stock opname',
            'view returnable packages',
            'view invoice',
            'view payment',
            'view piutang',
            'view chart of accounts',
            'view journal entries',
            'view general ledger',
            'view sales report', 'view inventory report', 'view delivery report', 'view financial report', 'export reports',
            'download apk', 'view sync status',
            'view approvals', 'manage approvals',
        ]);

        // ROLE: SUPERVISOR
        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $supervisor->syncPermissions([
            'view dashboard',
            'view products',
            'view categories',
            'view units',
            'view customers', 'create customers', 'edit customers',
            'view suppliers',
            'view sales order', 'create sales order', 'edit sales order', 'process orders',
            'view order history',
            'view deliveries', 'create deliveries', 'edit deliveries', 'process deliveries',
            'view warehouse',
            'view stock movement',
            'view invoice',
            'view payment',
            'view chart of accounts',
            'view sales report', 'view inventory report', 'view delivery report',
            'download apk',
            'view sync status',
            'view approvals',
        ]);
        
        // ROLE: WAREHOUSE
        $warehouse = Role::firstOrCreate(['name' => 'warehouse']);
        $warehouse->syncPermissions([
            'view dashboard',
            'view products',
            'view categories',
            'view units',
            'view sales order', 'process orders',
            'view deliveries',
            'view warehouse', 'manage warehouse',
            'view stock movement', 'create stock movement',
            'view purchase order', 'create purchase order', 'edit purchase order',
            'view direct purchase', 'create direct purchase', 'edit direct purchase',
            'view consignments', 'create consignments', 'edit consignments',
            'view outbound foc', 'create outbound foc', 'edit outbound foc',
            'view outbound return', 'create outbound return', 'edit outbound return',
            'view stock opname', 'do stock opname',
            'view returnable packages', 'manage returnable packages',
            'view inventory report',
            'download apk',
            'view sync status',
        ]);

        // ROLE: OPERATOR / HELPER GUDANG
        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->syncPermissions([
            'view dashboard',
            'view products',
            'view sales order', 'process orders',
            'view deliveries',
            'view warehouse',
            'view stock movement',
            'view returnable packages',
            'download apk',
            'view sync status',
        ]);
        
        // ROLE: FINANCE
        $finance = Role::firstOrCreate(['name' => 'finance']);
        $finance->syncPermissions([
            'view dashboard',
            'view customers',
            'view suppliers',
            'view sales order',
            'view deliveries',
            'view purchase order',
            'view direct purchase',
            'view consignments',
            'view invoice', 'create invoice', 'edit invoice',
            'view payment', 'process payment',
            'view piutang', 'manage piutang',
            'view chart of accounts', 'manage chart of accounts',
            'view journal entries', 'manage journal entries',
            'view general ledger',
            'view delivery report', 'view financial report', 'export reports',
            'download apk',
            'view sync status',
            'view approvals', 'manage approvals',
        ]);

        // ROLE: KURIR
        $kurir = Role::firstOrCreate(['name' => 'kurir']);
        $kurir->syncPermissions([
            'view dashboard',
            'view deliveries',
            'process deliveries',
            'download apk',
            'view sync status',
        ]);

        // ROLE: CUSTOMER
        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->syncPermissions([
            'view dashboard',
            'view products',
            'view sales order',
            'create sales order',
            'view order history',
        ]);

        // ============= ASSIGN ROLE TO EXISTING USERS =============
        
        // Assign super-admin ke user pertama (biasanya superadmin@dms.com)
        $superAdminUser = User::where('email', 'superadmin@dms.com')->first();
        if ($superAdminUser && !$superAdminUser->hasRole('super-admin')) {
            $superAdminUser->assignRole('super-admin');
        }
        
        // Assign admin ke user dengan email admin@dms.com (jika ada)
        $adminUser = User::where('email', 'admin@dms.com')->first();
        if ($adminUser && !$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }
        
        // Assign sales ke test user
        $salesUser = User::where('email', 'test@dms.com')->first();
        if ($salesUser && !$salesUser->hasRole('sales')) {
            $salesUser->assignRole('sales');
        }
        
        $this->command->info('Roles and permissions seeded successfully!');
    }
}
