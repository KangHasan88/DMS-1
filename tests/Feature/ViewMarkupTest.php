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

        $this->assertStringContainsString('--k-blue: #061a3f;', $content);
        $this->assertStringContainsString('--k-orange: #ff7a00;', $content);
        $this->assertStringContainsString('--k-success: #16a34a;', $content);
        $this->assertStringContainsString('background: var(--k-orange);', $content);
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
        $this->assertStringContainsString('Distribution Management System', $layout);
        $this->assertStringContainsString('Kelola<br>Distribusi<br><span>Lebih Terkendali</span>', $layout);
        $this->assertStringContainsString('DMS KURMIGO membantu tim mengatur pesanan', $layout);
        $this->assertStringNotContainsString('DMS KURMIGO adalah Distribution Management System untuk', $layout);
        $this->assertStringContainsString('--auth-blue: #061a3f;', $layout);
        $this->assertStringContainsString('auth-shell', $layout);
        $this->assertStringContainsString('Masuk ke DMS', $login);
        $this->assertStringContainsString('dashboard operasional KURMIGO', $login);
        $this->assertStringNotContainsString('Distribution Management System', $login);
        $this->assertStringContainsString('auth-button', $login);
    }
}
