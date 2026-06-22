<?php

namespace Tests\Feature;

use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\DeliveryRouteSession;
use App\Models\DeliveryVehicle;
use App\Models\SalesTerritory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryRouteSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_create_route_session(): void
    {
        $branch = CompanyProfile::defaultProfile()->defaultInvoiceBranch();
        $admin = $this->userWithRole('super-admin');
        $territory = $this->territory($branch);
        $salesperson = $this->userWithRole('sales', ['company_branch_id' => $branch->id]);
        $driver = $this->userWithRole('kurir', ['company_branch_id' => $branch->id]);
        $vehicle = $this->vehicle($branch);

        $this->actingAs($admin)
            ->get(route('delivery-route-sessions.create'))
            ->assertOk()
            ->assertSee('Tambah Sesi Rute')
            ->assertSee($territory->code)
            ->assertSee($vehicle->code);

        $response = $this->actingAs($admin)->post(route('delivery-route-sessions.store'), [
            'company_branch_id' => $branch->id,
            'sales_territory_id' => $territory->id,
            'salesperson_id' => $salesperson->id,
            'driver_id' => $driver->id,
            'delivery_vehicle_id' => $vehicle->id,
            'route_date' => '2026-06-15',
            'selling_mode' => DeliveryRouteSession::MODE_FULL_CANVAS,
            'status' => DeliveryRouteSession::STATUS_PLANNED,
            'opening_qty' => 120,
            'sold_qty' => 0,
            'returned_qty' => 0,
            'damaged_qty' => 0,
            'notes' => 'Simulasi sesi rute canvas.',
        ]);

        $session = DeliveryRouteSession::firstOrFail();

        $response->assertRedirect(route('delivery-route-sessions.edit', $session));
        $this->assertStringStartsWith('RTS-', $session->route_code);
        $this->assertDatabaseHas('delivery_route_sessions', [
            'id' => $session->id,
            'company_branch_id' => $branch->id,
            'sales_territory_id' => $territory->id,
            'selling_mode' => DeliveryRouteSession::MODE_FULL_CANVAS,
            'opening_qty' => 120,
        ]);
    }

    public function test_branch_admin_only_sees_own_branch_route_sessions(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $ownSession = $this->routeSession($branchA, 'OWN-RTS');
        $otherSession = $this->routeSession($branchB, 'OTHER-RTS');

        $this->actingAs($admin)
            ->get(route('delivery-route-sessions.index'))
            ->assertOk()
            ->assertSee($ownSession->route_code)
            ->assertDontSee($otherSession->route_code);

        $this->actingAs($admin)
            ->get(route('delivery-route-sessions.edit', $otherSession))
            ->assertForbidden();
    }

    public function test_super_admin_can_open_route_session_edit_page(): void
    {
        $branch = CompanyProfile::defaultProfile()->defaultInvoiceBranch();
        $admin = $this->userWithRole('super-admin');
        $session = $this->routeSession($branch, 'EDIT-RTS');

        $this->actingAs($admin)
            ->get(route('delivery-route-sessions.edit', $session))
            ->assertOk()
            ->assertSee('Edit Sesi Rute')
            ->assertSee($session->route_code)
            ->assertSee($session->salesTerritory->code)
            ->assertSee($session->vehicle->code);
    }

    private function routeSession(CompanyBranch $branch, string $code): DeliveryRouteSession
    {
        return DeliveryRouteSession::create([
            'company_profile_id' => CompanyProfile::defaultProfile()->id,
            'company_branch_id' => $branch->id,
            'sales_territory_id' => $this->territory($branch, $code . '-AREA')->id,
            'salesperson_id' => $this->userWithRole('sales', ['company_branch_id' => $branch->id])->id,
            'driver_id' => $this->userWithRole('kurir', ['company_branch_id' => $branch->id])->id,
            'delivery_vehicle_id' => $this->vehicle($branch, $code . '-VH')->id,
            'route_code' => $code,
            'route_date' => '2026-06-15',
            'selling_mode' => DeliveryRouteSession::MODE_SEMI_CANVAS,
            'status' => DeliveryRouteSession::STATUS_PLANNED,
            'opening_qty' => 20,
        ]);
    }

    private function territory(CompanyBranch $branch, string $code = 'TNG-A01'): SalesTerritory
    {
        return SalesTerritory::create([
            'company_branch_id' => $branch->id,
            'code' => $code,
            'name' => 'Area ' . $code,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function vehicle(CompanyBranch $branch, string $code = 'RTS-01'): DeliveryVehicle
    {
        return DeliveryVehicle::create([
            'company_branch_id' => $branch->id,
            'code' => $code,
            'name' => 'Armada Route Session',
            'vehicle_type' => DeliveryVehicle::TYPE_BOX_CAR,
            'plate_number' => 'B 9001 RTS',
            'status' => DeliveryVehicle::STATUS_AVAILABLE,
            'is_active' => true,
        ]);
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
            'name' => 'Cabang Route B',
            'code' => 'RTB',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        return [$branchA, $branchB];
    }
}
