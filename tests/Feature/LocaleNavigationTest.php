<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sidebar_defaults_to_indonesian_navigation_labels(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Operasional')
            ->assertSee('Pesanan')
            ->assertSee('Katalog')
            ->assertSee('Relasi Bisnis')
            ->assertSee('Laporan Inventori');
    }

    public function test_user_can_switch_navigation_language_to_english(): void
    {
        $user = $this->superAdmin(['locale' => 'id']);

        $this->actingAs($user)
            ->from('/dashboard')
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect('/dashboard');

        $this->assertSame('en', $user->fresh()->locale);

        $this->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Operations')
            ->assertSee('Sales Orders')
            ->assertSee('Catalog')
            ->assertSee('Business Relations')
            ->assertSee('Inventory Report')
            ->assertDontSee('Pesanan');
    }

    public function test_profile_link_is_in_topbar_not_sidebar_management(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('profile.edit'), false)
            ->assertSee('title="Buka Profil"', false)
            ->assertDontSee('user-profile-compact', false)
            ->assertDontSee('<span>Profil Saya</span>', false);
    }

    public function test_language_toggle_rejects_unsupported_locale(): void
    {
        $user = $this->superAdmin(['locale' => 'id']);

        $this->actingAs($user)
            ->from('/dashboard')
            ->post(route('locale.update'), ['locale' => 'jp'])
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors('locale');

        $this->assertSame('id', $user->fresh()->locale);
    }

    private function superAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('super-admin');

        return $user;
    }
}
