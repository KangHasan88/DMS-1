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
            ->assertSee('Pesanan Penjualan')
            ->assertSee('Barang Bonus')
            ->assertSee('Retur Penjualan')
            ->assertSee('Pesanan Pembelian')
            ->assertSee('Pemasok')
            ->assertSee('Peran & Hak Akses')
            ->assertSee('Katalog')
            ->assertSee('Relasi Bisnis')
            ->assertSee('Laporan Inventori')
            ->assertDontSee('Hadiah / FOC')
            ->assertDontSee('PO Pembelian')
            ->assertDontSee('Role & Hak Akses');
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
            ->assertSee('Complimentary Items')
            ->assertSee('Sales Returns')
            ->assertSee('Stock Management')
            ->assertSee('Catalog')
            ->assertSee('Business Relations')
            ->assertSee('Inventory Report')
            ->assertDontSee('FOC / Gift')
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

    public function test_dummy_notifications_are_hidden_until_real_feature_exists(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('bi bi-bell', false)
            ->assertDontSee('notification-badge', false);
    }

    public function test_sidebar_uses_kurmigo_robot_branding(): void
    {
        $user = $this->superAdmin();

        $this->assertFileExists(public_path('images/brand/kurmigo-robot.png'));

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('DMS', false)
            ->assertSee('KURMIGO', false)
            ->assertSee('images/brand/kurmigo-robot.png', false)
            ->assertSee('brand-robot', false);
    }

    public function test_stock_navigation_stays_active_on_stock_work_pages(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user)->get('/stock/low-stock');

        $response->assertOk();
        $this->assertStringContainsString(
            'href="'.route('stock.index').'" class="nav-link active"',
            $response->getContent()
        );
    }

    public function test_sidebar_does_not_render_empty_permission_items(): void
    {
        $user = $this->userWithRole('finance');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertDoesNotMatchRegularExpression(
            '/<li class="nav-item">\s*<\/li>/',
            $response->getContent()
        );
    }

    public function test_legacy_navbar_layout_is_removed(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/layouts/navbar.blade.php'));
    }

    public function test_legacy_admin_layout_is_removed(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/layouts/admin.blade.php'));
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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
