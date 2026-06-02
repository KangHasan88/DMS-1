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
            ->assertSee('Usulan Pembelian')
            ->assertSee('Pesanan Pembelian')
            ->assertSee('Pemasok')
            ->assertSee('Peran & Hak Akses')
            ->assertSee('Katalog')
            ->assertSee('Relasi Bisnis')
            ->assertSee('Laporan Inventori')
            ->assertSee('Stock Opname')
            ->assertDontSee('Hadiah / FOC')
            ->assertDontSee('PO Pembelian')
            ->assertDontSee('Role & Hak Akses');
    }

    public function test_user_can_switch_navigation_language_to_english(): void
    {
        $user = $this->superAdmin(['locale' => 'id']);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->from('/dashboard')
            ->post(route('locale.update'), ['locale' => 'en', '_token' => 'test-token'])
            ->assertRedirect('/dashboard');

        $this->assertSame('en', $user->fresh()->locale);

        $this->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Operations')
            ->assertSee('Sales Orders')
            ->assertSee('Complimentary Items')
            ->assertSee('Sales Returns')
            ->assertSee('Proposed Orders')
            ->assertSee('Stock Management')
            ->assertSee('Stock Opname')
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

    public function test_sidebar_uses_compact_dms_branding(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('DMS', false)
            ->assertDontSee('DMS KURMIGO', false)
            ->assertDontSee('Digitalisasi dan Otomasi')
            ->assertDontSee('images/brand/kurmigo-robot.png', false)
            ->assertDontSee('brand-robot', false);
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

    public function test_primary_module_headers_use_professional_indonesian_copy(): void
    {
        $views = [
            'stock.index',
            'deliveries.index',
            'outbound-focs.index',
            'purchase-orders.index',
            'orders.index',
            'outbound-returns.index',
            'roles.index',
            'consignments.index',
            'products.index',
            'customers.index',
            'suppliers.index',
            'direct-purchases.index',
            'stock-opnames.index',
        ];

        foreach ($views as $view) {
            $content = file_get_contents(resource_path('views/'.str_replace('.', '/', $view).'.blade.php'));
            preg_match("/@section\\('page-title', '([^']+)'\\)/", $content, $matches);

            $this->assertStringNotContainsString("Management')", $content, $view.' should not use generic Management page title.');
            $this->assertStringNotContainsString('Kelola semua', $content, $view.' should use specific subtitle copy.');
            $this->assertStringNotContainsString('Daftar ', $content, $view.' should avoid redundant Daftar headings.');
            $this->assertStringNotContainsString(
                '<h3 class="dms-section-title">'.($matches[1] ?? '').'</h3>',
                $content,
                $view.' should not repeat the page title inside the main content card.'
            );
        }
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

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->from('/dashboard')
            ->post(route('locale.update'), ['locale' => 'jp', '_token' => 'test-token'])
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
