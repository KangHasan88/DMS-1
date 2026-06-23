<?php

namespace Tests\Feature;

use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\DeliveryVehicle;
use App\Models\DriverVehicleAssignment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryVehicleLegacyAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_vehicle_keeps_inactive_branch_and_current_driver_visible(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $company = CompanyProfile::defaultProfile();
        $inactiveBranch = CompanyBranch::create([
            'company_profile_id' => $company->id,
            'name' => 'Cabang Armada Lama',
            'code' => 'CAL',
            'is_active' => false,
            'sort_order' => 99,
        ]);
        $driver = User::factory()->create([
            'name' => 'Driver Lama',
            'company_branch_id' => $inactiveBranch->id,
            'is_active' => false,
        ]);
        $driver->assignRole('kurir');
        $vehicle = DeliveryVehicle::create([
            'company_branch_id' => $inactiveBranch->id,
            'code' => 'CAL-MTR-001',
            'name' => 'Motor Legacy',
            'vehicle_type' => DeliveryVehicle::TYPE_MOTORCYCLE,
            'plate_number' => 'B 1001 CAL',
            'status' => DeliveryVehicle::STATUS_AVAILABLE,
            'is_active' => true,
        ]);
        $assignment = DriverVehicleAssignment::create([
            'driver_id' => $driver->id,
            'delivery_vehicle_id' => $vehicle->id,
            'started_at' => now(),
            'assigned_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('delivery-vehicles.edit', $vehicle));

        $response->assertOk()
            ->assertSee('Cabang Armada Lama - CAL - nonaktif')
            ->assertSee('Cabang ini sedang nonaktif')
            ->assertSee('Driver Lama - CAL - nonaktif')
            ->assertSee('Driver ini sedang nonaktif');

        $this->assertTrue($response->viewData('companyBranches')->contains('id', $inactiveBranch->id));
        $this->assertTrue($response->viewData('drivers')->contains('id', $driver->id));

        $this->actingAs($admin)
            ->put(route('delivery-vehicles.update', $vehicle), [
                'company_branch_id' => $inactiveBranch->id,
                'code' => 'CAL-MTR-001',
                'name' => 'Motor Legacy Update',
                'vehicle_type' => DeliveryVehicle::TYPE_MOTORCYCLE,
                'plate_number' => 'B 1001 CAL',
                'capacity' => '50 kg',
                'status' => DeliveryVehicle::STATUS_AVAILABLE,
                'primary_driver_id' => $driver->id,
                'is_active' => 1,
            ])
            ->assertRedirect(route('delivery-vehicles.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull($assignment->fresh()->ended_at);
        $this->assertDatabaseHas('delivery_vehicles', [
            'id' => $vehicle->id,
            'name' => 'Motor Legacy Update',
            'company_branch_id' => $inactiveBranch->id,
        ]);
    }
}
