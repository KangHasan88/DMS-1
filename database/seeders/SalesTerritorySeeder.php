<?php

namespace Database\Seeders;

use App\Models\CompanyBranch;
use App\Models\Customer;
use App\Models\CustomerSalesAssignment;
use App\Models\SalesTerritory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SalesTerritorySeeder extends Seeder
{
    public function run(): void
    {
        $salesRole = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $branches = CompanyBranch::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        foreach ($branches as $branchIndex => $branch) {
            $branchCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $branch->code ?: $branch->name), 0, 3)) ?: 'CBG';
            $salespeople = $this->seedSalespeople($branch, $branchCode, $branchIndex, $salesRole);
            $territories = $this->seedTerritories($branch, $branchCode);
            $customers = $this->seedCustomersIfNeeded($branch, $branchCode, $branchIndex, $customerRole);

            foreach ($customers->values() as $index => $customer) {
                $territory = $territories[$index % $territories->count()];
                $salesperson = $salespeople[$index % $salespeople->count()];

                CustomerSalesAssignment::query()
                    ->where('customer_id', $customer->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'end_date' => now()->subDay()->toDateString(),
                    ]);

                CustomerSalesAssignment::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'salesperson_id' => $salesperson->id,
                        'start_date' => now()->toDateString(),
                    ],
                    [
                        'sales_territory_id' => $territory->id,
                        'company_branch_id' => $branch->id,
                        'end_date' => null,
                        'assignment_type' => CustomerSalesAssignment::TYPE_PERMANENT,
                        'is_active' => true,
                        'notes' => 'Contoh assignment sales area untuk simulasi coverage.',
                    ]
                );
            }
        }

        $this->command?->info('Sales territories, salespeople, and customer assignments seeded.');
    }

    private function seedSalespeople(CompanyBranch $branch, string $branchCode, int $branchIndex, Role $salesRole)
    {
        $names = [
            ['Andi Pratama', 'andi'],
            ['Rina Lestari', 'rina'],
        ];

        return collect($names)->map(function (array $sales, int $index) use ($branch, $branchCode, $branchIndex, $salesRole) {
            [$name, $slug] = $sales;
            $email = "{$slug}.{$branchCode}@kurmigo.test";

            $user = User::firstOrCreate(
                ['email' => strtolower($email)],
                [
                    'name' => "{$name} {$branchCode}",
                    'username' => "{$slug}_{$branchCode}",
                    'phone' => '0822' . str_pad((string) (($branchIndex + 1) * 1000 + $index + 1), 8, '0', STR_PAD_LEFT),
                    'password' => 'password123',
                    'company_branch_id' => $branch->id,
                    'position' => 'Sales Representative',
                    'department' => 'Sales',
                    'is_active' => true,
                ]
            );

            $user->company_branch_id = $branch->id;
            $user->is_active = true;
            $user->save();
            $user->assignRole($salesRole);

            return $user;
        });
    }

    private function seedTerritories(CompanyBranch $branch, string $branchCode)
    {
        $territories = [
            ['A01', 'Area Barat'],
            ['A02', 'Area Timur'],
        ];

        return collect($territories)->map(function (array $territory, int $index) use ($branch, $branchCode) {
            [$suffix, $name] = $territory;

            return SalesTerritory::updateOrCreate(
                [
                    'company_branch_id' => $branch->id,
                    'code' => "{$branchCode}-{$suffix}",
                ],
                [
                    'name' => "{$name} {$branch->name}",
                    'description' => "Coverage {$name} untuk {$branch->name}.",
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        });
    }

    private function seedCustomersIfNeeded(CompanyBranch $branch, string $branchCode, int $branchIndex, Role $customerRole)
    {
        $customers = Customer::query()
            ->where('company_branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(6)
            ->get();

        if ($customers->count() >= 4) {
            return $customers;
        }

        $samples = [
            ['Toko Sumber Makmur', 'Jl. Mawar No. 10'],
            ['Toko Segar Jaya', 'Jl. Melati No. 22'],
            ['Warung Berkah Abadi', 'Jl. Kenanga No. 7'],
            ['Toko Maju Bersama', 'Jl. Anggrek No. 15'],
        ];

        foreach ($samples as $index => [$name, $address]) {
            $email = 'customer.' . strtolower($branchCode) . '.' . ($index + 1) . '@example.test';
            $phone = '0833' . str_pad((string) (($branchIndex + 1) * 1000 + $index + 1), 8, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "{$name} {$branchCode}",
                    'phone' => $phone,
                    'password' => 'password123',
                    'company_branch_id' => $branch->id,
                    'is_active' => true,
                ]
            );
            $user->assignRole($customerRole);

            Customer::updateOrCreate(
                ['email' => $email],
                [
                    'user_id' => $user->id,
                    'company_branch_id' => $branch->id,
                    'name' => "{$name} {$branchCode}",
                    'phone' => $phone,
                    'address' => "{$address}, {$branch->name}",
                    'customer_type' => 'regular',
                    'payment_term' => Customer::PAYMENT_CASH,
                    'credit_status' => Customer::CREDIT_NORMAL,
                    'is_active' => true,
                ]
            );
        }

        return Customer::query()
            ->where('company_branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(6)
            ->get();
    }
}
