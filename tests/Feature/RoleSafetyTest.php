<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_role_cannot_be_renamed(): void
    {
        $user = $this->userWithRole('super-admin');
        $role = Role::findByName('super-admin');

        $response = $this->actingAs($user)->put(route('roles.update', $role), [
            'name' => 'owner',
            'permissions' => ['view dashboard'],
        ]);

        $response
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('error', 'Role sistem tidak dapat diedit.');

        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
        $this->assertDatabaseMissing('roles', ['name' => 'owner']);
    }

    public function test_super_admin_role_permissions_cannot_be_changed(): void
    {
        $user = $this->userWithRole('super-admin');
        $role = Role::findByName('super-admin');
        $permissionCount = $role->permissions()->count();

        $response = $this->actingAs($user)->put(route('roles.permissions.update', $role), [
            'permissions' => ['view dashboard'],
        ]);

        $response
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('error', 'Permission role sistem tidak dapat diubah.');

        $this->assertSame($permissionCount, $role->fresh()->permissions()->count());
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
