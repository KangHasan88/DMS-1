<?php

namespace Tests\Feature;

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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
