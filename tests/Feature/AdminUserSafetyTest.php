<?php

namespace Tests\Feature;

use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_cannot_create_super_admin_user(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Escalated User',
            'email' => 'escalated@example.com',
            'username' => 'escalated',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => ['super-admin'],
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('roles');
        $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    }

    public function test_admin_cannot_promote_existing_user_to_super_admin(): void
    {
        $admin = $this->userWithRole('admin');
        $user = $this->userWithRole('sales');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'roles' => ['super-admin'],
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('roles');
        $this->assertFalse($user->fresh()->hasRole('super-admin'));
    }

    public function test_user_cannot_toggle_their_own_active_status(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->postJson(route('admin.users.toggle-status', $admin));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Tidak dapat mengubah status akun sendiri.',
            ]);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_branch_admin_only_sees_users_from_own_branch(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $ownBranchUser = $this->userWithRole('sales', [
            'name' => 'Sales Cabang A',
            'company_branch_id' => $branchA->id,
        ]);
        $otherBranchUser = $this->userWithRole('sales', [
            'name' => 'Sales Cabang B',
            'company_branch_id' => $branchB->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($ownBranchUser->name)
            ->assertDontSee($otherBranchUser->name);
    }

    public function test_branch_admin_cannot_edit_user_from_other_branch(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $otherBranchUser = $this->userWithRole('sales', ['company_branch_id' => $branchB->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $otherBranchUser))
            ->assertForbidden();
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
            'name' => 'Cabang Dua',
            'code' => 'DUB',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        return [$branchA, $branchB];
    }
}
