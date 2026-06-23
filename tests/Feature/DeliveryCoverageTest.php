<?php

namespace Tests\Feature;

use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Delivery;
use App\Models\DeliveryVehicle;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_create_multi_depot_delivery_zone(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('super-admin');
        $driver = $this->userWithRole('kurir', ['company_branch_id' => $branchA->id]);
        $vehicle = $this->vehicle($branchA);
        $address = $this->shippingAddress($branchA, 'Customer Coverage A');

        $this->actingAs($admin)
            ->get(route('delivery-coverage.create'))
            ->assertOk()
            ->assertSee('Tambah Zona Pengiriman')
            ->assertSee($branchA->name)
            ->assertSee($branchB->name);

        $response = $this->actingAs($admin)->post(route('delivery-coverage.store'), [
            'code' => 'TNG-S01',
            'name' => 'Tangerang Selatan',
            'description' => 'Coverage dengan depo utama dan backup',
            'sort_order' => 1,
            'depot_ids' => [$branchA->id, $branchB->id],
            'depot_priority' => [
                $branchA->id => 1,
                $branchB->id => 2,
            ],
            'depot_capacity' => [
                $branchA->id => 80,
                $branchB->id => 40,
            ],
            'customer_address_ids' => [$address->id],
            'driver_ids' => [$driver->id],
            'vehicle_ids' => [$vehicle->id],
        ]);

        $zone = DeliveryZone::where('code', 'TNG-S01')->firstOrFail();

        $response->assertRedirect(route('delivery-coverage.edit', $zone));
        $this->actingAs($admin)
            ->get(route('delivery-coverage.edit', $zone))
            ->assertOk()
            ->assertSee('Edit Zona Pengiriman')
            ->assertSee('TNG-S01')
            ->assertSee($address->address);
        $this->assertDatabaseHas('delivery_zone_depots', [
            'delivery_zone_id' => $zone->id,
            'company_branch_id' => $branchA->id,
            'priority' => 1,
            'max_daily_orders' => 80,
        ]);
        $this->assertDatabaseHas('delivery_zone_depots', [
            'delivery_zone_id' => $zone->id,
            'company_branch_id' => $branchB->id,
            'priority' => 2,
            'max_daily_orders' => 40,
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'id' => $address->id,
            'delivery_zone_id' => $zone->id,
            'coverage_verified_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('delivery_zone_drivers', [
            'delivery_zone_id' => $zone->id,
            'driver_id' => $driver->id,
        ]);
        $this->assertDatabaseHas('delivery_zone_vehicles', [
            'delivery_zone_id' => $zone->id,
            'delivery_vehicle_id' => $vehicle->id,
        ]);
    }

    public function test_delivery_module_navigation_separates_operations_and_settings(): void
    {
        $admin = $this->userWithRole('super-admin');

        $this->actingAs($admin)
            ->get(route('deliveries.index'))
            ->assertOk()
            ->assertSee('Operasional')
            ->assertSee('Pengaturan Pengiriman')
            ->assertDontSee('Coverage</a>', false);

        $this->actingAs($admin)
            ->get(route('delivery-coverage.index'))
            ->assertOk()
            ->assertSee('Pengaturan Pengiriman')
            ->assertSee('Coverage')
            ->assertSee('Armada')
            ->assertSee('Driver')
            ->assertSee('Ekspedisi')
            ->assertSee('Slot Waktu')
            ->assertSee(route('delivery-drivers.index'), false);
    }

    public function test_driver_setting_page_is_branch_aware(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $ownDriver = $this->userWithRole('kurir', [
            'company_branch_id' => $branchA->id,
            'name' => 'Driver Cabang Sendiri',
        ]);
        $otherDriver = $this->userWithRole('kurir', [
            'company_branch_id' => $branchB->id,
            'name' => 'Driver Cabang Lain',
        ]);

        $this->actingAs($admin)
            ->get(route('delivery-drivers.index'))
            ->assertOk()
            ->assertSee('Data Driver')
            ->assertSee($ownDriver->name)
            ->assertDontSee($otherDriver->name)
            ->assertSee('Belum ditetapkan');
    }

    public function test_branch_admin_only_sees_zones_served_by_own_branch(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $ownZone = $this->zone('OWN-ZONE', $branchA);
        $otherZone = $this->zone('OTHER-ZONE', $branchB);

        $this->actingAs($admin)
            ->get(route('delivery-coverage.index'))
            ->assertOk()
            ->assertSee($ownZone->code)
            ->assertDontSee($otherZone->code);

        $this->actingAs($admin)
            ->get(route('delivery-coverage.edit', $otherZone))
            ->assertForbidden();
    }

    public function test_branch_admin_edit_preserves_other_depot_configuration(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $zone = DeliveryZone::create([
            'company_profile_id' => CompanyProfile::defaultProfile()->id,
            'code' => 'SHARED',
            'name' => 'Shared Coverage',
            'is_active' => true,
        ]);
        $zone->depots()->attach([
            $branchA->id => ['priority' => 1, 'max_daily_orders' => 50, 'is_active' => true],
            $branchB->id => ['priority' => 2, 'max_daily_orders' => 30, 'is_active' => true],
        ]);

        $this->actingAs($admin)
            ->put(route('delivery-coverage.update', $zone), [
                'code' => 'SHARED',
                'name' => 'Shared Coverage Updated',
                'sort_order' => 2,
                'is_active' => 1,
                'depot_ids' => [$branchA->id],
                'depot_priority' => [$branchA->id => 1],
                'depot_capacity' => [$branchA->id => 60],
            ])
            ->assertRedirect(route('delivery-coverage.edit', $zone));

        $this->assertDatabaseHas('delivery_zone_depots', [
            'delivery_zone_id' => $zone->id,
            'company_branch_id' => $branchA->id,
            'max_daily_orders' => 60,
        ]);
        $this->assertDatabaseHas('delivery_zone_depots', [
            'delivery_zone_id' => $zone->id,
            'company_branch_id' => $branchB->id,
            'priority' => 2,
            'max_daily_orders' => 30,
        ]);
    }

    public function test_edit_delivery_keeps_inactive_driver_and_unavailable_vehicle_visible(): void
    {
        [$branchA] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $driver = $this->userWithRole('kurir', [
            'company_branch_id' => $branchA->id,
            'name' => 'Driver Nonaktif Lama',
            'is_active' => false,
        ]);
        $vehicle = $this->vehicle($branchA);
        $vehicle->update([
            'status' => DeliveryVehicle::STATUS_MAINTENANCE,
            'is_active' => false,
        ]);
        $customer = $this->userWithRole('customer', ['company_branch_id' => $branchA->id]);
        $order = Order::create([
            'user_id' => $customer->id,
            'company_branch_id' => $branchA->id,
            'order_number' => 'KMG-DELIVERY-LEGACY',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '09:00-12:00',
            'address' => 'Jl. Delivery Legacy',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'subtotal' => 0,
            'total' => 0,
            'grand_total' => 0,
            'status' => Order::STATUS_READY,
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
            'discount_type' => Order::DISCOUNT_NONE,
            'discount_value' => 0,
            'discount_amount' => 0,
            'shipping_type' => Order::SHIPPING_FLAT,
            'shipping_rate' => 0,
            'include_ppn' => false,
            'ppn_rate' => 0,
            'ppn_amount' => 0,
        ]);
        $delivery = Delivery::create([
            'order_id' => $order->id,
            'delivery_method' => Delivery::METHOD_INTERNAL,
            'kurir_id' => $driver->id,
            'delivery_vehicle_id' => $vehicle->id,
            'status' => Delivery::STATUS_ASSIGNED,
            'assigned_at' => now(),
            'actual_shipping_cost' => 0,
            'shipping_cost_status' => Delivery::COST_NOT_APPLICABLE,
        ]);

        $this->actingAs($admin)
            ->get(route('deliveries.edit', $delivery))
            ->assertOk()
            ->assertSee('Driver Nonaktif Lama')
            ->assertSee('Driver ini sedang nonaktif')
            ->assertSee('nonaktif/tidak tersedia')
            ->assertSee('Armada ini sedang nonaktif atau tidak tersedia');
    }

    private function shippingAddress(CompanyBranch $branch, string $name): CustomerAddress
    {
        $user = $this->userWithRole('customer', ['company_branch_id' => $branch->id]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'company_branch_id' => $branch->id,
            'name' => $name,
            'phone' => '081234567890',
            'email' => $user->email,
            'customer_type' => 'regular',
            'payment_term' => Customer::PAYMENT_CASH,
            'credit_limit' => 0,
            'max_outstanding_orders' => 0,
            'credit_status' => Customer::CREDIT_NORMAL,
            'is_active' => true,
        ]);

        return CustomerAddress::create([
            'customer_id' => $customer->id,
            'label' => 'Alamat Pengiriman',
            'type' => CustomerAddress::TYPE_SHIPPING,
            'address' => 'Jl. Coverage Test No. 1',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'is_default_shipping' => true,
            'is_active' => true,
        ]);
    }

    private function vehicle(CompanyBranch $branch): DeliveryVehicle
    {
        return DeliveryVehicle::create([
            'company_branch_id' => $branch->id,
            'code' => 'TEST-01',
            'name' => 'Armada Coverage Test',
            'vehicle_type' => DeliveryVehicle::TYPE_BOX_CAR,
            'status' => DeliveryVehicle::STATUS_AVAILABLE,
            'is_active' => true,
        ]);
    }

    private function zone(string $code, CompanyBranch $branch): DeliveryZone
    {
        $zone = DeliveryZone::create([
            'company_profile_id' => CompanyProfile::defaultProfile()->id,
            'code' => $code,
            'name' => $code,
            'is_active' => true,
        ]);
        $zone->depots()->attach($branch->id, [
            'priority' => 1,
            'is_active' => true,
        ]);

        return $zone;
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));
        $user->assignRole($role);

        return $user;
    }

    private function twoCompanyBranches(): array
    {
        $company = CompanyProfile::defaultProfile();
        $branchA = $company->defaultInvoiceBranch();
        $branchB = CompanyBranch::create([
            'company_profile_id' => $company->id,
            'name' => 'Cabang Coverage B',
            'code' => 'CVB',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        return [$branchA, $branchB];
    }
}
