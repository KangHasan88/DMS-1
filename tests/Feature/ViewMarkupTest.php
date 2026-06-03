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

    public function test_customer_type_options_are_loaded_from_master_data(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/CustomerController.php'));
        $create = file_get_contents(resource_path('views/customers/create.blade.php'));
        $edit = file_get_contents(resource_path('views/customers/edit.blade.php'));
        $index = file_get_contents(resource_path('views/customers/index.blade.php'));
        $show = file_get_contents(resource_path('views/customers/show.blade.php'));
        $sidebar = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        $this->assertStringContainsString('CustomerType::active()', $controller);
        $this->assertStringNotContainsString('regular,premium,wholesale', $controller);

        foreach ([$create, $edit, $index] as $content) {
            $this->assertStringContainsString('$customerTypes', $content);
            $this->assertStringNotContainsString('<option value="regular"', $content);
            $this->assertStringNotContainsString('<option value="premium"', $content);
            $this->assertStringNotContainsString('<option value="wholesale"', $content);
        }

        foreach ([$create, $edit] as $content) {
            $this->assertStringContainsString('customer-types.index', $content);
        }

        $this->assertStringContainsString('Tambah Tipe', $index);
        $this->assertStringContainsString('customer-type-panel', $index);
        $this->assertStringContainsString('toggleInlineCategoryForm', $index);
        $this->assertStringContainsString('customer-types.store', $index);
        $this->assertStringContainsString('customer-types.index', $index);
        $this->assertStringContainsString('Lihat Daftar', $index);
        $this->assertStringContainsString('customer_type_label', $index);
        $this->assertStringContainsString('customer_type_label', $show);
        $this->assertStringNotContainsString('customer-types.index', $sidebar);
        $this->assertStringNotContainsString('Tipe Pelanggan', $sidebar);
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
        $this->assertStringContainsString('<th style="width: 88px; text-align: center;">{{ $isIndonesian ? \'Aksi\' : \'Action\' }}</th>', $dashboard);
        $this->assertStringContainsString('aria-label="{{ $isIndonesian ? \'Lihat Detail Pesanan\' : \'View Order Detail\' }}"', $dashboard);
        $this->assertStringNotContainsString('</i> Detail', $dashboard);
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

    public function test_order_create_form_uses_clear_copy_and_searchable_selects(): void
    {
        $create = file_get_contents(resource_path('views/orders/create.blade.php'));
        $index = file_get_contents(resource_path('views/orders/index.blade.php'));
        $show = file_get_contents(resource_path('views/orders/show.blade.php'));
        $edit = file_get_contents(resource_path('views/orders/edit.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/OrderController.php'));

        $this->assertStringContainsString("@section('page-title', 'Buat Order Baru')", $create);
        $this->assertStringContainsString('<h3 class="dms-form-title">Detail Order Penjualan</h3>', $create);
        $this->assertStringNotContainsString('<h3 class="dms-form-title">Buat Order Baru</h3>', $create);
        $this->assertStringContainsString('class="dms-combobox js-searchable-dropdown"', $create);
        $this->assertStringContainsString('data-select-id="customer-select"', $create);
        $this->assertStringContainsString('class="dms-combobox js-searchable-dropdown product-search"', $create);
        $this->assertStringContainsString('class="dms-combobox-search"', $create);
        $this->assertStringContainsString('initializeSearchableDropdowns(newRow);', $create);
        $this->assertStringContainsString('class="dms-products-table-wrap"', $create);
        $this->assertStringContainsString("classList.toggle('dms-combobox-row-open', shouldOpen)", $create);
        $this->assertStringNotContainsString('padding-bottom: 14rem', $create);
        $this->assertStringContainsString('button.textContent = option.textContent.trim();', $create);
        $this->assertStringContainsString('BLJ (Beli langsung jual)', $create);
        $this->assertStringContainsString('Mode BLJ: Barang dibeli dari pabrik/supplier', $create);
        $this->assertStringContainsString('<option value="none">Tanpa Ongkir</option>', $create);
        $this->assertStringContainsString('Biaya Packing / Repack <span style="color: var(--k-gray-500); font-weight: 400;">(opsional)</span>', $create);
        $this->assertStringContainsString('id="packing_fee" class="form-control" value="0"', $create);
        $this->assertStringContainsString("'shipping_type' => 'nullable|in:none,flat,weight,distance'", $controller);
        $this->assertStringContainsString("'shipping_rate' => 'nullable|numeric|min:0'", $controller);
        $this->assertStringContainsString('$packingFee = $request->packing_fee ?? 0;', $controller);
        $this->assertStringNotContainsString("'packing_fee' => 1000", $controller);
        $this->assertStringNotContainsString('JIT (', $create);
        $this->assertStringNotContainsString('Mode JIT:', $create);
        $this->assertStringNotContainsString('</thead>' . PHP_EOL . '                    </thead>', $create);

        foreach ([$index, $show, $edit, $controller] as $content) {
            $this->assertStringContainsString('BLJ', $content);
            $this->assertStringNotContainsString('Just In Time', $content);
            $this->assertStringNotContainsString('Beli ke Pasar', $content);
            $this->assertStringNotContainsString('Mode JIT', $content);
        }
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

        $this->assertStringContainsString('Tambah Kategori', $index);
        $this->assertStringContainsString('product-category-panel', $index);
        $this->assertStringContainsString('toggleInlineCategoryForm', $index);
        $this->assertStringContainsString('product-categories.store', $index);
        $this->assertStringContainsString('product-categories.index', $index);
        $this->assertStringContainsString('Lihat Daftar', $index);
        $this->assertStringNotContainsString('product-categories.index', $sidebar);
        $this->assertStringNotContainsString('Kategori Produk', $sidebar);
    }

    public function test_supplier_category_options_are_loaded_from_master_data(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/SupplierController.php'));
        $create = file_get_contents(resource_path('views/suppliers/create.blade.php'));
        $edit = file_get_contents(resource_path('views/suppliers/edit.blade.php'));
        $index = file_get_contents(resource_path('views/suppliers/index.blade.php'));
        $sidebar = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        $this->assertStringContainsString('SupplierCategory::active()', $controller);
        $this->assertStringNotContainsString('Supplier::CATEGORIES', $controller);

        foreach ([$create, $edit] as $content) {
            $this->assertStringContainsString('$categories', $content);
            $this->assertStringContainsString('supplier-categories.index', $content);
        }

        $this->assertStringContainsString('Tambah Kategori', $index);
        $this->assertStringContainsString('supplier-category-panel', $index);
        $this->assertStringContainsString('toggleInlineCategoryForm', $index);
        $this->assertStringContainsString('supplier-categories.store', $index);
        $this->assertStringContainsString('supplier-categories.index', $index);
        $this->assertStringContainsString('Lihat Daftar', $index);
        $this->assertStringNotContainsString('supplier-categories.index', $sidebar);
        $this->assertStringNotContainsString('Kategori Pemasok', $sidebar);
    }

    public function test_supplier_market_master_is_not_exposed_on_supplier_pages(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/SupplierController.php'));
        $create = file_get_contents(resource_path('views/suppliers/create.blade.php'));
        $edit = file_get_contents(resource_path('views/suppliers/edit.blade.php'));
        $index = file_get_contents(resource_path('views/suppliers/index.blade.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $sidebar = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        $this->assertStringNotContainsString('SupplierMarket::active()', $controller);
        $this->assertStringNotContainsString('supplier-markets', $routes);

        foreach ([$create, $edit] as $content) {
            $this->assertStringNotContainsString('$markets', $content);
            $this->assertStringNotContainsString('name="market_name"', $content);
            $this->assertStringNotContainsString('supplier-markets.index', $content);
        }

        $this->assertStringNotContainsString('Tambah Pasar', $index);
        $this->assertStringNotContainsString('supplier-market-panel', $index);
        $this->assertStringNotContainsString('supplier-markets.store', $index);
        $this->assertStringNotContainsString('supplier-markets.index', $index);
        $this->assertStringNotContainsString('$markets', $index);
        $this->assertStringNotContainsString('name="market" placeholder="Filter Pasar', $index);
        $this->assertStringNotContainsString('supplier-markets.index', $sidebar);
        $this->assertStringNotContainsString('Pasar Pemasok', $sidebar);
    }

    public function test_supplier_stall_number_is_not_exposed_on_supplier_pages(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/SupplierController.php'));
        $create = file_get_contents(resource_path('views/suppliers/create.blade.php'));
        $edit = file_get_contents(resource_path('views/suppliers/edit.blade.php'));
        $index = file_get_contents(resource_path('views/suppliers/index.blade.php'));
        $show = file_get_contents(resource_path('views/suppliers/show.blade.php'));

        foreach ([$create, $edit, $index, $show] as $content) {
            $this->assertStringNotContainsString('stall_number', $content);
            $this->assertStringNotContainsString('Nomor Lapak/Kios', $content);
            $this->assertStringNotContainsString('Lapak:', $content);
        }

        $this->assertStringNotContainsString('stall_number', $controller);
        $this->assertStringNotContainsString('nomor lapak', strtolower($index));
    }

    public function test_unit_category_options_are_loaded_from_master_data(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/UnitController.php'));
        $create = file_get_contents(resource_path('views/units/create.blade.php'));
        $edit = file_get_contents(resource_path('views/units/edit.blade.php'));
        $index = file_get_contents(resource_path('views/units/index.blade.php'));
        $sidebar = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));

        $this->assertStringContainsString('UnitCategory::active()', $controller);
        $this->assertStringNotContainsString("Unit::select('category')", $controller);

        foreach ([$create, $edit, $index] as $content) {
            $this->assertStringContainsString('$categories', $content);
            $this->assertStringNotContainsString('<option value="Berat"', $content);
            $this->assertStringNotContainsString('<option value="Jumlah"', $content);
            $this->assertStringNotContainsString('<option value="Volume"', $content);
            $this->assertStringNotContainsString('<option value="Panjang"', $content);
            $this->assertStringNotContainsString('<option value="Lainnya"', $content);
        }

        foreach ([$create, $edit] as $content) {
            $this->assertStringContainsString('unit-categories.index', $content);
        }

        $this->assertStringContainsString('Tambah Kategori', $index);
        $this->assertStringContainsString('unit-category-panel', $index);
        $this->assertStringContainsString('toggleInlineCategoryForm', $index);
        $this->assertStringContainsString('unit-categories.store', $index);
        $this->assertStringContainsString('unit-categories.index', $index);
        $this->assertStringContainsString('Lihat Daftar', $index);
        $this->assertStringNotContainsString('unit-categories.index', $sidebar);
        $this->assertStringNotContainsString('Kategori Satuan', $sidebar);
    }

    public function test_sidebar_keeps_primary_records_visible_and_moves_supporting_categories_to_page_actions(): void
    {
        $sidebar = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));
        $productCategories = file_get_contents(resource_path('views/product-categories/index.blade.php'));
        $unitCategories = file_get_contents(resource_path('views/unit-categories/index.blade.php'));
        $supplierCategories = file_get_contents(resource_path('views/supplier-categories/index.blade.php'));
        $customerTypes = file_get_contents(resource_path('views/customer-types/index.blade.php'));

        $catalogStart = strpos($sidebar, '<!-- SECTION: CATALOG -->');
        $units = strpos($sidebar, 'units.index', $catalogStart);
        $products = strpos($sidebar, 'products.index', $catalogStart);

        $this->assertNotFalse($catalogStart);
        $this->assertNotFalse($units);
        $this->assertNotFalse($products);
        $this->assertLessThan($products, $units);
        $this->assertStringNotContainsString('product-categories.index', $sidebar);
        $this->assertStringNotContainsString('unit-categories.index', $sidebar);

        $relationsStart = strpos($sidebar, '<!-- SECTION: BUSINESS RELATIONS -->');
        $customers = strpos($sidebar, 'customers.index', $relationsStart);
        $suppliers = strpos($sidebar, 'suppliers.index', $relationsStart);

        $this->assertNotFalse($relationsStart);
        $this->assertNotFalse($customers);
        $this->assertNotFalse($suppliers);
        $this->assertLessThan($suppliers, $customers);
        $this->assertStringNotContainsString('supplier-categories.index', $sidebar);
        $this->assertStringNotContainsString('supplier-markets.index', $sidebar);
        $this->assertStringNotContainsString('customer-types.index', $sidebar);

        $this->assertStringContainsString('products.index', $productCategories);
        $this->assertStringContainsString('units.index', $unitCategories);
        $this->assertStringContainsString('suppliers.index', $supplierCategories);
        $this->assertStringContainsString('customers.index', $customerTypes);
        $this->assertStringContainsString('Kembali', $productCategories);
        $this->assertStringContainsString('Kembali', $unitCategories);
        $this->assertStringContainsString('Kembali', $supplierCategories);
        $this->assertStringContainsString('Kembali', $customerTypes);
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
