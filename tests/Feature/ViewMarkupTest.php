<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViewMarkupTest extends TestCase
{
    public function test_blade_views_do_not_contain_broken_placeholder_markers(): void
    {
        $brokenMarkers = collect(File::allFiles(resource_path('views')))
            ->flatMap(function ($file) {
                return collect(file($file->getPathname()))
                    ->map(fn (string $line, int $index) => [
                        'path' => $file->getRelativePathname(),
                        'line' => $index + 1,
                        'content' => trim($line),
                    ]);
            })
            ->filter(function (array $line) {
                return $line['content'] === '??'
                    || str_contains($line['content'], '??<')
                    || str_contains($line['content'], '>??')
                    || str_contains($line['content'], '}}??')
                    || str_contains($line['content'], '??{{');
            })
            ->map(fn (array $line) => "{$line['path']}:{$line['line']} {$line['content']}")
            ->values();

        $this->assertSame([], $brokenMarkers->all());
    }

    public function test_supplier_module_uses_indonesian_pemasok_copy(): void
    {
        $files = [
            resource_path('views/suppliers/index.blade.php'),
            resource_path('views/suppliers/show.blade.php'),
            resource_path('views/suppliers/create.blade.php'),
            resource_path('views/suppliers/edit.blade.php'),
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            $this->assertStringNotContainsString('Supplier Management', $content);
            $this->assertStringNotContainsString('Detail Supplier', $content);
            $this->assertStringNotContainsString('Tambah Supplier', $content);
            $this->assertStringNotContainsString('Edit Supplier', $content);
            $this->assertStringNotContainsString('Nama Supplier', $content);
        }
    }

    public function test_customer_module_uses_indonesian_pelanggan_copy(): void
    {
        $files = [
            resource_path('views/customers/index.blade.php'),
            resource_path('views/customers/show.blade.php'),
            resource_path('views/customers/create.blade.php'),
            resource_path('views/customers/edit.blade.php'),
            resource_path('views/customers/order-history.blade.php'),
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            $this->assertStringNotContainsString('Customer Management', $content);
            $this->assertStringNotContainsString('Detail Customer', $content);
            $this->assertStringNotContainsString('Tambah Customer', $content);
            $this->assertStringNotContainsString('Edit Customer', $content);
            $this->assertStringNotContainsString('Tipe Customer', $content);
        }
    }

    public function test_sidebar_brand_palette_keeps_orange_accent_and_semantic_success(): void
    {
        $content = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        $this->assertStringContainsString('--k-blue: #123b7a;', $content);
        $this->assertStringContainsString('--k-blue-darker: #071a3d;', $content);
        $this->assertStringContainsString('--k-orange: #f97316;', $content);
        $this->assertStringContainsString('--k-success: #16a34a;', $content);
        $this->assertStringContainsString('background: var(--k-orange-light);', $content);
        $this->assertStringContainsString('background: var(--k-success-light);', $content);
        $this->assertStringContainsString('color: var(--k-success);', $content);
    }

    public function test_core_ui_foundation_classes_are_used_by_pilot_pages(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        foreach (['.dms-section-header', '.dms-toolbar', '.dms-table-wrap', '.dms-empty-state', '.dms-pagination'] as $class) {
            $this->assertStringContainsString($class, $layout);
        }

        $indexFiles = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => $file->getFilename() === 'index.blade.php')
            ->map(fn ($file) => $file->getPathname())
            ->values();

        foreach ($indexFiles as $file) {
            $content = file_get_contents($file);

            $this->assertStringNotContainsString('`r`n', $content);
            $this->assertStringNotContainsString('--dms-', $content);
            $this->assertStringContainsString('dms-section-header', $content);
            $this->assertStringContainsString('dms-table-wrap', $content);

            if (str_contains($content, '->links()') || str_contains($content, '->withQueryString()->links()')) {
                $this->assertStringContainsString('dms-pagination', $content);
            }
        }

        $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));

        $this->assertStringNotContainsString('`r`n', $dashboard);
        $this->assertStringContainsString('dms-section-header', $dashboard);
        $this->assertStringContainsString('dms-table-wrap', $dashboard);
    }

    public function test_form_ui_foundation_is_available_and_used_by_core_forms(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        foreach (['.dms-form-header', '.dms-form-grid', '.dms-form-actions', '.dms-form-error', '.dms-form-help'] as $class) {
            $this->assertStringContainsString($class, $layout);
        }

        foreach ([
            resource_path('views/products/create.blade.php'),
            resource_path('views/customers/create.blade.php'),
            resource_path('views/suppliers/create.blade.php'),
            resource_path('views/units/create.blade.php'),
        ] as $file) {
            $content = file_get_contents($file);

            $this->assertStringContainsString('dms-form-header', $content);
            $this->assertStringContainsString('dms-form-actions', $content);
            $this->assertStringNotContainsString('<style>', $content);
            $this->assertStringNotContainsString('`r`n', $content);
        }
    }

    public function test_auth_pages_use_kurmigo_branding(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/guest.blade.php'));
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertStringContainsString('kurmigo-robot.png', $layout);
        $this->assertStringContainsString('DMS KURMIGO', $layout);
        $this->assertStringNotContainsString('Distribution Management System', $layout);
        $this->assertStringContainsString('Kelola<br>Distribusi<br><span>Lebih Terkendali</span>', $layout);
        $this->assertStringContainsString('DMS KURMIGO membantu tim mengatur pesanan', $layout);
        $this->assertStringNotContainsString('DMS KURMIGO adalah Distribution Management System untuk', $layout);
        $this->assertStringContainsString('--auth-blue: #061a3f;', $layout);
        $this->assertStringContainsString('auth-shell', $layout);
        $this->assertStringContainsString('Masuk ke System', $login);
        $this->assertStringContainsString('mengakses DMS KURMIGO', $login);
        $this->assertStringNotContainsString('Distribution Management System', $login);
        $this->assertStringContainsString('auth-button', $login);
    }

    public function test_dashboard_content_typography_and_labels_are_professional(): void
    {
        $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));
        $layout = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        foreach (['No. Pesanan', 'Pelanggan', 'Jumlah', 'Status', 'Tanggal', 'Aksi'] as $label) {
            $this->assertStringContainsString($label, $dashboard);
        }

        foreach (['ORDER ID', 'CUSTOMER', 'AMOUNT', 'ACTION', 'Pesanan Pending', 'Produk Stok Menipis'] as $legacyLabel) {
            $this->assertStringNotContainsString($legacyLabel, $dashboard);
        }

        $this->assertStringContainsString('font-size: 0.78rem;', $layout);
        $this->assertStringContainsString('background: #f8fafc;', $layout);
        $this->assertStringContainsString('font-weight: 600;', $layout);
    }

    public function test_table_action_buttons_stay_in_one_row(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));
        $products = file_get_contents(resource_path('views/products/index.blade.php'));

        $this->assertStringContainsString('flex-wrap: nowrap;', $layout);
        $this->assertStringContainsString('white-space: nowrap;', $layout);
        $this->assertStringContainsString('flex: 0 0 32px;', $layout);
        $this->assertStringContainsString('<th style="width: 220px;">Aksi</th>', $products);
    }

    public function test_product_category_options_are_loaded_from_master_data(): void
    {
        $create = file_get_contents(resource_path('views/products/create.blade.php'));
        $edit = file_get_contents(resource_path('views/products/edit.blade.php'));
        $index = file_get_contents(resource_path('views/products/index.blade.php'));
        $sidebar = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        foreach ([$create, $edit, $index] as $content) {
            $this->assertStringContainsString('$categories', $content);
            $this->assertStringNotContainsString('<option value="Sayur"', $content);
            $this->assertStringNotContainsString('<option value="Buah"', $content);
            $this->assertStringNotContainsString('<option value="Lauk"', $content);
            $this->assertStringNotContainsString('<option value="Bumbu"', $content);
        }

        $this->assertStringContainsString('product-categories.index', $sidebar);
        $this->assertStringContainsString('Kategori Produk', $sidebar);
    }

    public function test_supplier_category_options_are_loaded_from_master_data(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/SupplierController.php'));
        $create = file_get_contents(resource_path('views/suppliers/create.blade.php'));
        $edit = file_get_contents(resource_path('views/suppliers/edit.blade.php'));
        $sidebar = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        $this->assertStringContainsString('SupplierCategory::active()', $controller);
        $this->assertStringNotContainsString('Supplier::CATEGORIES', $controller);

        foreach ([$create, $edit] as $content) {
            $this->assertStringContainsString('$categories', $content);
            $this->assertStringContainsString('supplier-categories.index', $content);
        }

        $this->assertStringContainsString('supplier-categories.index', $sidebar);
        $this->assertStringContainsString('Kategori Pemasok', $sidebar);
    }

    public function test_global_typography_uses_professional_scale(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        foreach ([
            '--k-font-xs: 0.72rem;',
            '--k-font-sm: 0.78rem;',
            '--k-font-md: 0.82rem;',
            '--k-font-lg: 1.02rem;',
            '--k-font-page-title: 1.12rem;',
            'font-family: \'Inter\'',
            'font-size: var(--k-font-page-title);',
            'font-size: var(--k-font-lg);',
            'font-size: var(--k-font-md);',
            'font-size: var(--k-font-sm);',
        ] as $expected) {
            $this->assertStringContainsString($expected, $layout);
        }

        $this->assertStringNotContainsString('font-weight: 750;', $layout);
        $this->assertStringNotContainsString('font-size: 0.45rem;', $layout);
        $this->assertStringNotContainsString('font-size: 0.55rem;', $layout);
    }

    public function test_sidebar_scroll_position_is_preserved_between_pages(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        $this->assertStringContainsString("const sidebarScrollKey = 'dms.sidebar.scrollTop';", $layout);
        $this->assertStringContainsString('localStorage.setItem(sidebarScrollKey, String(sidebar.scrollTop));', $layout);
        $this->assertStringContainsString('sidebar.scrollTop = Number(savedScrollTop);', $layout);
        $this->assertStringContainsString("sidebar.querySelectorAll('.nav-link')", $layout);
        $this->assertStringContainsString("activeNavLink.scrollIntoView({ block: 'center' });", $layout);
    }
}
