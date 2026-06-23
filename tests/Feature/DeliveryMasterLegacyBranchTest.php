<?php

namespace Tests\Feature;

use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\DeliveryTimeSlot;
use App\Models\DeliveryVendor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryMasterLegacyBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_vendor_keeps_inactive_existing_branch_visible(): void
    {
        $admin = $this->actingAdmin();
        $branch = $this->inactiveBranch();
        $vendor = DeliveryVendor::create([
            'company_branch_id' => $branch->id,
            'name' => 'Vendor Legacy',
            'code' => 'VLG',
            'vendor_type' => DeliveryVendor::TYPE_CUSTOM,
            'payment_term' => DeliveryVendor::PAYMENT_TERM_CASH,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('delivery-vendors.edit', $vendor));

        $response->assertOk()
            ->assertSee('Cabang Legacy - LGY - nonaktif')
            ->assertSee('Cabang ini sedang nonaktif');

        $this->assertTrue($response->viewData('companyBranches')->contains('id', $branch->id));
    }

    public function test_edit_time_slot_keeps_inactive_existing_branch_visible(): void
    {
        $admin = $this->actingAdmin();
        $branch = $this->inactiveBranch();
        $slot = DeliveryTimeSlot::create([
            'company_branch_id' => $branch->id,
            'name' => 'Slot Legacy',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'period_label' => 'Pagi',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('delivery-time-slots.edit', $slot));

        $response->assertOk()
            ->assertSee('Cabang Legacy - LGY - nonaktif')
            ->assertSee('Cabang ini sedang nonaktif');

        $this->assertTrue($response->viewData('companyBranches')->contains('id', $branch->id));
    }

    private function actingAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function inactiveBranch(): CompanyBranch
    {
        $company = CompanyProfile::defaultProfile();

        return CompanyBranch::create([
            'company_profile_id' => $company->id,
            'name' => 'Cabang Legacy',
            'code' => 'LGY',
            'is_active' => false,
            'sort_order' => 99,
        ]);
    }
}
