<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerTypeController;
use App\Http\Controllers\SalesCoverageController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierCategoryController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\ArInvoiceController;
use App\Http\Controllers\ArCreditNoteController;
use App\Http\Controllers\ApInvoiceController;
use App\Http\Controllers\ApDebitNoteController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\AccountingPeriodLockController;
use App\Http\Controllers\ChartAccountController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\CashBankController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DeliveryVendorController;
use App\Http\Controllers\DeliveryTimeSlotController;
use App\Http\Controllers\DeliveryVehicleController;
use App\Http\Controllers\DeliveryCoverageController;
use App\Http\Controllers\DeliveryDriverController;
use App\Http\Controllers\DeliveryRouteSessionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\ReturnablePackageController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\DirectPurchaseController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\OutboundFocController;
use App\Http\Controllers\OutboundReturnController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Saas\RemoteModuleHealthController;
use App\Http\Controllers\Saas\RemoteModuleLaunchController;
use App\Http\Controllers\Saas\RemoteModuleProvisioningController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/health', RemoteModuleHealthController::class)->name('remote-module.health');
Route::get('/sso/launch', RemoteModuleLaunchController::class)->name('remote-module.launch');
Route::post('/module-provisioning', RemoteModuleProvisioningController::class)
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('remote-module.provisioning');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
    
    // ============= DASHBOARD =============
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view dashboard')
        ->name('dashboard');
    
    // ============= PROFILE =============
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
        Route::post('/photo', [ProfileController::class, 'updatePhoto'])->name('photo.update');
        Route::delete('/photo', [ProfileController::class, 'removePhoto'])->name('photo.remove');
        Route::get('/login-history', [ProfileController::class, 'loginHistory'])->name('login-history');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
    
    // ============= USER MANAGEMENT =============
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->only(['create', 'store'])->middleware('permission:create users');
        Route::resource('users', UserController::class)->only(['index', 'show'])->middleware('permission:view users');
        Route::resource('users', UserController::class)->only(['edit', 'update'])->middleware('permission:edit users');
        Route::resource('users', UserController::class)->only(['destroy'])->middleware('permission:delete users');
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleActive'])->middleware('permission:activate users')->name('users.toggle-status');
    });
    
    // ============= ROLE & PERMISSION MANAGEMENT =============
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::middleware(['role:super-admin,admin'])->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        });
        
        Route::middleware(['role:super-admin'])->group(function () {
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
            Route::get('/{role}/permissions', [RoleController::class, 'permissions'])->name('permissions');
            Route::put('/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('permissions.update');
        });
    });

    // ============= COMPANY PROFILE =============
    Route::prefix('company-profile')->name('company-profile.')->group(function () {
        Route::get('/', [CompanyProfileController::class, 'index'])->middleware('permission:view company profile')->name('index');
        Route::put('/company', [CompanyProfileController::class, 'updateCompany'])->middleware('permission:edit company profile')->name('update-company');
        Route::post('/branches', [CompanyProfileController::class, 'storeBranch'])->middleware('permission:edit company profile')->name('branches.store');
        Route::put('/branches/{branch}', [CompanyProfileController::class, 'updateBranch'])->middleware('permission:edit company profile')->name('branches.update');
        Route::post('/branches/{branch}/toggle', [CompanyProfileController::class, 'toggleBranch'])->middleware('permission:edit company profile')->name('branches.toggle');
        Route::post('/branches/{branch}/default', [CompanyProfileController::class, 'setDefaultBranch'])->middleware('permission:edit company profile')->name('branches.default');
    });
    
    // ============= UNIT MANAGEMENT =============
    Route::get('units/list', [UnitController::class, 'getList'])->middleware('permission:view units')->name('units.list');
    Route::resource('units', UnitController::class)->only(['create', 'store'])->middleware('permission:create units');
    Route::resource('units', UnitController::class)->only(['index', 'show'])->middleware('permission:view units');
    Route::resource('units', UnitController::class)->only(['edit', 'update'])->middleware('permission:edit units');
    Route::resource('units', UnitController::class)->only(['destroy'])->middleware('permission:delete units');
    Route::post('units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->middleware('permission:edit units')->name('units.toggle-status');

    // ============= UNIT CATEGORY MANAGEMENT =============
    Route::get('unit-categories', [UnitCategoryController::class, 'index'])->middleware('permission:view units')->name('unit-categories.index');
    Route::get('unit-categories/create', [UnitCategoryController::class, 'create'])->middleware('permission:create units')->name('unit-categories.create');
    Route::post('unit-categories', [UnitCategoryController::class, 'store'])->middleware('permission:create units')->name('unit-categories.store');
    Route::get('unit-categories/{unitCategory}/edit', [UnitCategoryController::class, 'edit'])->middleware('permission:edit units')->name('unit-categories.edit');
    Route::put('unit-categories/{unitCategory}', [UnitCategoryController::class, 'update'])->middleware('permission:edit units')->name('unit-categories.update');
    Route::delete('unit-categories/{unitCategory}', [UnitCategoryController::class, 'destroy'])->middleware('permission:delete units')->name('unit-categories.destroy');
    Route::post('unit-categories/{unitCategory}/toggle-status', [UnitCategoryController::class, 'toggleStatus'])->middleware('permission:edit units')->name('unit-categories.toggle-status');

    // ============= PRODUCT CATEGORY MANAGEMENT =============
    Route::get('product-categories', [ProductCategoryController::class, 'index'])->middleware('permission:view categories')->name('product-categories.index');
    Route::get('product-categories/create', [ProductCategoryController::class, 'create'])->middleware('permission:create categories')->name('product-categories.create');
    Route::post('product-categories', [ProductCategoryController::class, 'store'])->middleware('permission:create categories')->name('product-categories.store');
    Route::get('product-categories/{productCategory}/edit', [ProductCategoryController::class, 'edit'])->middleware('permission:edit categories')->name('product-categories.edit');
    Route::put('product-categories/{productCategory}', [ProductCategoryController::class, 'update'])->middleware('permission:edit categories')->name('product-categories.update');
    Route::delete('product-categories/{productCategory}', [ProductCategoryController::class, 'destroy'])->middleware('permission:delete categories')->name('product-categories.destroy');
    Route::post('product-categories/{productCategory}/toggle-status', [ProductCategoryController::class, 'toggleStatus'])->middleware('permission:edit categories')->name('product-categories.toggle-status');
    
    // ============= PRODUCT MANAGEMENT =============
    Route::get('products/{product}/stock-info', [OrderController::class, 'getStockInfo'])->middleware('permission:view products')->name('products.stock-info');
    Route::resource('products', ProductController::class)->only(['create', 'store'])->middleware('permission:create products');
    Route::resource('products', ProductController::class)->only(['index', 'show'])->middleware('permission:view products');
    Route::resource('products', ProductController::class)->only(['edit', 'update'])->middleware('permission:edit products');
    Route::resource('products', ProductController::class)->only(['destroy'])->middleware('permission:delete products');
    Route::post('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->middleware('permission:edit products')->name('products.toggle-status');
    Route::get('products/{product}/price-history', [ProductController::class, 'priceHistory'])->middleware('permission:view products')->name('products.price-history');
    
    // ============= CUSTOMER TYPE MANAGEMENT =============
    Route::get('customer-types', [CustomerTypeController::class, 'index'])->middleware('permission:view customers')->name('customer-types.index');
    Route::get('customer-types/create', [CustomerTypeController::class, 'create'])->middleware('permission:create customers')->name('customer-types.create');
    Route::post('customer-types', [CustomerTypeController::class, 'store'])->middleware('permission:create customers')->name('customer-types.store');
    Route::get('customer-types/{customerType}/edit', [CustomerTypeController::class, 'edit'])->middleware('permission:edit customers')->name('customer-types.edit');
    Route::put('customer-types/{customerType}', [CustomerTypeController::class, 'update'])->middleware('permission:edit customers')->name('customer-types.update');
    Route::delete('customer-types/{customerType}', [CustomerTypeController::class, 'destroy'])->middleware('permission:delete customers')->name('customer-types.destroy');
    Route::post('customer-types/{customerType}/toggle-status', [CustomerTypeController::class, 'toggleStatus'])->middleware('permission:edit customers')->name('customer-types.toggle-status');

    // ============= CUSTOMER MANAGEMENT =============
    Route::get('customers/maps/reverse-geocode', [CustomerAddressController::class, 'reverseGeocode'])->name('customers.maps.reverse-geocode');
    Route::resource('customers', CustomerController::class)->only(['create', 'store'])->middleware('permission:create customers');
    Route::resource('customers', CustomerController::class)->only(['index', 'show'])->middleware('permission:view customers');
    Route::resource('customers', CustomerController::class)->only(['edit', 'update'])->middleware('permission:edit customers');
    Route::resource('customers', CustomerController::class)->only(['destroy'])->middleware('permission:delete customers');
    Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->middleware('permission:edit customers')->name('customers.toggle-status');
    Route::post('customers/{customer}/topup-wallet', [CustomerController::class, 'topupWallet'])->middleware('permission:process payment')->name('customers.topup-wallet');
    Route::get('customers/{customer}/order-history', [CustomerController::class, 'orderHistory'])->middleware('permission:view order history')->name('customers.order-history');
    Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store'])->middleware('permission:edit customers')->name('customers.addresses.store');
    Route::put('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update'])->middleware('permission:edit customers')->name('customers.addresses.update');
    Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->middleware('permission:edit customers')->name('customers.addresses.destroy');

    // ============= SALES COVERAGE MANAGEMENT =============
    Route::get('sales-coverage', [SalesCoverageController::class, 'index'])->middleware('permission:view sales team')->name('sales-coverage.index');
    Route::post('sales-coverage/territories', [SalesCoverageController::class, 'storeTerritory'])->middleware('permission:manage sales team')->name('sales-coverage.territories.store');
    Route::post('sales-coverage/assignments', [SalesCoverageController::class, 'assignCustomer'])->middleware('permission:manage sales team')->name('sales-coverage.assignments.store');
    Route::put('sales-coverage/assignments/{assignment}', [SalesCoverageController::class, 'updateAssignment'])->middleware('permission:manage sales team')->name('sales-coverage.assignments.update');
    Route::delete('sales-coverage/assignments/{assignment}', [SalesCoverageController::class, 'endAssignment'])->middleware('permission:manage sales team')->name('sales-coverage.assignments.destroy');

    // ============= SUPPLIER CATEGORY MANAGEMENT =============
    Route::get('supplier-categories', [SupplierCategoryController::class, 'index'])->middleware('permission:view suppliers')->name('supplier-categories.index');
    Route::get('supplier-categories/create', [SupplierCategoryController::class, 'create'])->middleware('permission:create suppliers')->name('supplier-categories.create');
    Route::post('supplier-categories', [SupplierCategoryController::class, 'store'])->middleware('permission:create suppliers')->name('supplier-categories.store');
    Route::get('supplier-categories/{supplierCategory}/edit', [SupplierCategoryController::class, 'edit'])->middleware('permission:edit suppliers')->name('supplier-categories.edit');
    Route::put('supplier-categories/{supplierCategory}', [SupplierCategoryController::class, 'update'])->middleware('permission:edit suppliers')->name('supplier-categories.update');
    Route::delete('supplier-categories/{supplierCategory}', [SupplierCategoryController::class, 'destroy'])->middleware('permission:delete suppliers')->name('supplier-categories.destroy');
    Route::post('supplier-categories/{supplierCategory}/toggle-status', [SupplierCategoryController::class, 'toggleStatus'])->middleware('permission:edit suppliers')->name('supplier-categories.toggle-status');

    // ============= SUPPLIER MANAGEMENT =============
    Route::resource('suppliers', SupplierController::class)->only(['create', 'store'])->middleware('permission:create suppliers');
    Route::resource('suppliers', SupplierController::class)->only(['index', 'show'])->middleware('permission:view suppliers');
    Route::resource('suppliers', SupplierController::class)->only(['edit', 'update'])->middleware('permission:edit suppliers');
    Route::resource('suppliers', SupplierController::class)->only(['destroy'])->middleware('permission:delete suppliers');
    Route::post('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->middleware('permission:edit suppliers')->name('suppliers.toggle-status');
    
    // ============= PURCHASE ORDER MANAGEMENT =============
    Route::get('purchase-orders/proposed', [PurchaseOrderController::class, 'proposed'])->middleware('permission:create purchase order')->name('purchase-orders.proposed');
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['create', 'store'])->middleware('permission:create purchase order');
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'show'])->middleware('permission:view purchase order');
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['edit', 'update'])->middleware('permission:edit purchase order');
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['destroy'])->middleware('permission:delete purchase order');
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->middleware('permission:edit purchase order')->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('permission:delete purchase order')->name('purchase-orders.cancel');
    Route::get('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveForm'])->middleware('permission:edit purchase order')->name('purchase-orders.receive-form');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->middleware('permission:edit purchase order')->name('purchase-orders.receive');
    // ============= DIRECT PURCHASE MANAGEMENT =============
    Route::resource('direct-purchases', DirectPurchaseController::class)->only(['create', 'store'])->middleware('permission:create direct purchase');
    Route::resource('direct-purchases', DirectPurchaseController::class)->only(['index', 'show'])->middleware('permission:view direct purchase');
    
    // ============= CONSIGNMENT MANAGEMENT =============
    Route::resource('consignments', ConsignmentController::class)->only(['create', 'store'])->middleware('permission:create consignments');
    Route::resource('consignments', ConsignmentController::class)->only(['index', 'show'])->middleware('permission:view consignments');
    Route::resource('consignments', ConsignmentController::class)->only(['destroy'])->middleware('permission:delete consignments');
    Route::get('consignments/{consignment}/return', [ConsignmentController::class, 'returnForm'])->middleware('permission:edit consignments')->name('consignments.return-form');
    Route::post('consignments/{consignment}/return', [ConsignmentController::class, 'processReturn'])->middleware('permission:edit consignments')->name('consignments.return');
    
    // ============= STOCK MANAGEMENT =============
    Route::resource('stock-opnames', StockOpnameController::class)
        ->only(['index', 'create', 'store', 'show', 'update'])
        ->middleware('permission:manage warehouse');
    Route::post('stock-opnames/{stockOpname}/complete', [StockOpnameController::class, 'complete'])
        ->middleware('permission:manage warehouse')
        ->name('stock-opnames.complete');

    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [StockController::class, 'index'])->middleware('permission:view warehouse')->name('index');
        Route::get('/low-stock', [StockController::class, 'lowStock'])->middleware('permission:view warehouse')->name('low-stock');
        Route::get('/movements', [StockController::class, 'movements'])->middleware('permission:view stock movement')->name('movements');
        Route::get('/{product}', [StockController::class, 'show'])->middleware('permission:view warehouse')->name('show');
        Route::get('/{product}/add', [StockController::class, 'addStockForm'])->middleware('permission:create stock movement')->name('add-form');
        Route::post('/{product}/add', [StockController::class, 'addStock'])->middleware('permission:create stock movement')->name('add');
        Route::get('/{product}/reduce', [StockController::class, 'reduceStockForm'])->middleware('permission:create stock movement')->name('reduce-form');
        Route::post('/{product}/reduce', [StockController::class, 'reduceStock'])->middleware('permission:create stock movement')->name('reduce');
        Route::get('/{product}/adjustment', [StockController::class, 'adjustmentForm'])->middleware('permission:manage warehouse')->name('adjustment-form');
        Route::post('/{product}/adjustment', [StockController::class, 'adjustment'])->middleware('permission:manage warehouse')->name('adjustment');
    });

    Route::get('returnable-packages', [ReturnablePackageController::class, 'index'])
        ->middleware('permission:view returnable packages')
        ->name('returnable-packages.index');
    Route::post('returnable-packages', [ReturnablePackageController::class, 'store'])
        ->middleware('permission:manage returnable packages')
        ->name('returnable-packages.store');
    Route::post('returnable-packages/categories', [ReturnablePackageController::class, 'storeCategory'])
        ->middleware('permission:manage returnable packages')
        ->name('returnable-packages.categories.store');
    Route::patch('returnable-packages/categories/{category}/toggle', [ReturnablePackageController::class, 'toggleCategory'])
        ->middleware('permission:manage returnable packages')
        ->name('returnable-packages.categories.toggle');
    Route::post('returnable-packages/movements', [ReturnablePackageController::class, 'storeMovement'])
        ->middleware('permission:manage returnable packages')
        ->name('returnable-packages.movements.store');
    
    // ============= ORDER MANAGEMENT =============
    Route::resource('orders', OrderController::class)->only(['create', 'store'])->middleware('permission:create sales order');
    Route::resource('orders', OrderController::class)->only(['index', 'show'])->middleware('permission:view sales order');
    Route::resource('orders', OrderController::class)->only(['edit', 'update'])->middleware('permission:edit sales order');
    Route::resource('orders', OrderController::class)->only(['destroy'])->middleware('permission:delete sales order');
    Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->middleware('permission:process orders')->name('orders.update-status');
    Route::post('orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->middleware('permission:process payment')->name('orders.confirm-payment');
    Route::post('orders/{order}/process-procurement', [OrderController::class, 'processProcurement'])->middleware('permission:process orders')->name('orders.process-procurement');
    Route::post('orders/{order}/process-repack', [OrderController::class, 'processRepack'])->middleware('permission:process orders')->name('orders.process-repack');
    Route::post('orders/{order}/start-picking', [OrderController::class, 'startPicking'])->middleware('permission:process orders')->name('orders.start-picking');
    Route::post('orders/{order}/mark-picked', [OrderController::class, 'markPicked'])->middleware('permission:process orders')->name('orders.mark-picked');
    Route::post('orders/{order}/mark-ready', [OrderController::class, 'markReady'])->middleware('permission:process orders')->name('orders.mark-ready');
    Route::post('orders/{order}/mark-shipped', [OrderController::class, 'markShipped'])->middleware('permission:process deliveries')->name('orders.mark-shipped');
    Route::post('orders/{order}/mark-delivered', [OrderController::class, 'markDelivered'])->middleware('permission:process deliveries')->name('orders.mark-delivered');
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->middleware('permission:view invoice')->name('orders.invoice');
    Route::get('orders/{order}/proforma-invoice', [OrderController::class, 'proformaInvoice'])->middleware('permission:view invoice')->name('orders.proforma-invoice');
    Route::get('orders/{order}/delivery-order', [OrderController::class, 'deliveryOrder'])->middleware('permission:view invoice')->name('orders.delivery-order');
    Route::post('order-items/{item}/unavailable', [OrderController::class, 'markItemUnavailable'])->middleware('permission:process orders')->name('order-items.unavailable');

    // ============= AR INVOICE MANAGEMENT =============
    Route::resource('ar-invoices', ArInvoiceController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view invoice');
    Route::post('ar-invoices', [ArInvoiceController::class, 'store'])
        ->middleware('permission:create invoice')
        ->name('ar-invoices.store');
    Route::post('ar-invoices/{arInvoice}/void', [ArInvoiceController::class, 'void'])
        ->middleware('permission:create invoice')
        ->name('ar-invoices.void');

    Route::resource('ar-credit-notes', ArCreditNoteController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view invoice');
    Route::post('ar-credit-notes', [ArCreditNoteController::class, 'store'])
        ->middleware('permission:create invoice')
        ->name('ar-credit-notes.store');
    Route::post('ar-credit-notes/{arCreditNote}/void', [ArCreditNoteController::class, 'void'])
        ->middleware('permission:create invoice')
        ->name('ar-credit-notes.void');

    // ============= AP INVOICE MANAGEMENT =============
    Route::resource('ap-invoices', ApInvoiceController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view invoice');
    Route::post('ap-invoices', [ApInvoiceController::class, 'store'])
        ->middleware('permission:create invoice')
        ->name('ap-invoices.store');
    Route::post('ap-invoices/{apInvoice}/void', [ApInvoiceController::class, 'void'])
        ->middleware('permission:create invoice')
        ->name('ap-invoices.void');

    Route::resource('ap-debit-notes', ApDebitNoteController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view invoice');
    Route::post('ap-debit-notes', [ApDebitNoteController::class, 'store'])
        ->middleware('permission:create invoice')
        ->name('ap-debit-notes.store');
    Route::post('ap-debit-notes/{apDebitNote}/void', [ApDebitNoteController::class, 'void'])
        ->middleware('permission:create invoice')
        ->name('ap-debit-notes.void');

    // ============= CUSTOMER PAYMENT MANAGEMENT =============
    Route::resource('customer-payments', CustomerPaymentController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view payment');
    Route::post('customer-payments', [CustomerPaymentController::class, 'store'])
        ->middleware('permission:process payment')
        ->name('customer-payments.store');
    Route::post('customer-payments/{customerPayment}/void', [CustomerPaymentController::class, 'void'])
        ->middleware('permission:process payment')
        ->name('customer-payments.void');

    // ============= SUPPLIER PAYMENT MANAGEMENT =============
    Route::resource('supplier-payments', SupplierPaymentController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view payment');
    Route::post('supplier-payments', [SupplierPaymentController::class, 'store'])
        ->middleware('permission:process payment')
        ->name('supplier-payments.store');
    Route::post('supplier-payments/{supplierPayment}/void', [SupplierPaymentController::class, 'void'])
        ->middleware('permission:process payment')
        ->name('supplier-payments.void');

    // ============= TAX MANAGEMENT =============
    Route::get('tax/output', [TaxController::class, 'output'])
        ->middleware('permission:view invoice')
        ->name('tax.output');
    Route::get('tax/output/export', [TaxController::class, 'exportOutput'])
        ->middleware('permission:view invoice')
        ->name('tax.output.export');
    Route::post('tax/output/mark-exported', [TaxController::class, 'markOutputExported'])
        ->middleware('permission:create invoice')
        ->name('tax.output.mark-exported');
    Route::put('tax/output/{arInvoice}', [TaxController::class, 'updateOutput'])
        ->middleware('permission:create invoice')
        ->name('tax.output.update');
    Route::get('tax/input', [TaxController::class, 'input'])
        ->middleware('permission:view invoice')
        ->name('tax.input');
    Route::get('tax/input/export', [TaxController::class, 'exportInput'])
        ->middleware('permission:view invoice')
        ->name('tax.input.export');
    Route::post('tax/input/mark-exported', [TaxController::class, 'markInputExported'])
        ->middleware('permission:create invoice')
        ->name('tax.input.mark-exported');
    Route::put('tax/input/{apInvoice}', [TaxController::class, 'updateInput'])
        ->middleware('permission:create invoice')
        ->name('tax.input.update');

    // ============= ACCOUNTING MANAGEMENT =============
    Route::resource('chart-accounts', ChartAccountController::class)
        ->only(['index'])
        ->middleware('permission:view chart of accounts');
    Route::post('chart-accounts', [ChartAccountController::class, 'store'])
        ->middleware('permission:manage chart of accounts')
        ->name('chart-accounts.store');
    Route::put('chart-accounts/{chartAccount}', [ChartAccountController::class, 'update'])
        ->middleware('permission:manage chart of accounts')
        ->name('chart-accounts.update');
    Route::post('chart-accounts/{chartAccount}/toggle', [ChartAccountController::class, 'toggle'])
        ->middleware('permission:manage chart of accounts')
        ->name('chart-accounts.toggle');

    Route::resource('journal-entries', JournalEntryController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view journal entries');
    Route::post('journal-entries', [JournalEntryController::class, 'store'])
        ->middleware('permission:manage journal entries')
        ->name('journal-entries.store');
    Route::post('journal-entries/{journalEntry}/void', [JournalEntryController::class, 'void'])
        ->middleware('permission:manage journal entries')
        ->name('journal-entries.void');

    Route::get('accounting-period-locks', [AccountingPeriodLockController::class, 'index'])
        ->middleware('permission:view journal entries')
        ->name('accounting-period-locks.index');
    Route::post('accounting-period-locks', [AccountingPeriodLockController::class, 'store'])
        ->middleware('permission:manage journal entries')
        ->name('accounting-period-locks.store');
    Route::post('accounting-period-locks/{accountingPeriodLock}/unlock', [AccountingPeriodLockController::class, 'unlock'])
        ->middleware('permission:manage journal entries')
        ->name('accounting-period-locks.unlock');

    Route::get('general-ledger', [GeneralLedgerController::class, 'index'])
        ->middleware('permission:view general ledger')
        ->name('general-ledger.index');

    Route::get('cash-bank', [CashBankController::class, 'index'])
        ->middleware('permission:view general ledger')
        ->name('cash-bank.index');
    Route::post('cash-bank/expenses', [CashBankController::class, 'storeExpense'])
        ->middleware('permission:manage journal entries')
        ->name('cash-bank.expenses.store');
    Route::post('cash-bank/transfers', [CashBankController::class, 'storeTransfer'])
        ->middleware('permission:manage journal entries')
        ->name('cash-bank.transfers.store');

    Route::get('trial-balance', [TrialBalanceController::class, 'index'])
        ->middleware('permission:view general ledger')
        ->name('trial-balance.index');
    
    // ============= DELIVERY MANAGEMENT =============
    Route::get('deliveries/kurir/today', [DeliveryController::class, 'kurirToday'])->middleware('permission:view deliveries')->name('deliveries.kurir.today');
    Route::resource('deliveries', DeliveryController::class)->only(['create', 'store'])->middleware('permission:create deliveries');
    Route::resource('deliveries', DeliveryController::class)->only(['index', 'show'])->middleware('permission:view deliveries');
    Route::resource('deliveries', DeliveryController::class)->only(['edit', 'update'])->middleware('permission:edit deliveries');
    Route::resource('deliveries', DeliveryController::class)->only(['destroy'])->middleware('permission:delete deliveries');
    Route::post('deliveries/{delivery}/update-status', [DeliveryController::class, 'updateStatus'])->middleware('permission:process deliveries')->name('deliveries.update-status');
    Route::post('deliveries/{delivery}/update-location', [DeliveryController::class, 'updateLocation'])->middleware('permission:process deliveries')->name('deliveries.update-location');
    Route::resource('delivery-route-sessions', DeliveryRouteSessionController::class)->only(['index'])->middleware('permission:view deliveries');
    Route::resource('delivery-route-sessions', DeliveryRouteSessionController::class)->only(['create', 'store'])->middleware('permission:create deliveries');
    Route::resource('delivery-route-sessions', DeliveryRouteSessionController::class)->only(['edit', 'update'])->middleware('permission:edit deliveries');
    Route::resource('delivery-route-sessions', DeliveryRouteSessionController::class)->only(['show'])->middleware('permission:view deliveries');
    Route::resource('delivery-vendors', DeliveryVendorController::class)->only(['index'])->middleware('permission:view deliveries');
    Route::resource('delivery-vendors', DeliveryVendorController::class)->only(['create', 'store'])->middleware('permission:create deliveries');
    Route::resource('delivery-vendors', DeliveryVendorController::class)->only(['edit', 'update'])->middleware('permission:edit deliveries');
    Route::resource('delivery-vehicles', DeliveryVehicleController::class)->only(['index'])->middleware('permission:view deliveries');
    Route::resource('delivery-vehicles', DeliveryVehicleController::class)->only(['create', 'store'])->middleware('permission:create deliveries');
    Route::resource('delivery-vehicles', DeliveryVehicleController::class)->only(['edit', 'update'])->middleware('permission:edit deliveries');
    Route::get('delivery-drivers', [DeliveryDriverController::class, 'index'])
        ->middleware('permission:view deliveries')
        ->name('delivery-drivers.index');
    Route::resource('delivery-time-slots', DeliveryTimeSlotController::class)->only(['index'])->middleware('permission:view deliveries');
    Route::resource('delivery-time-slots', DeliveryTimeSlotController::class)->only(['create', 'store'])->middleware('permission:create deliveries');
    Route::resource('delivery-time-slots', DeliveryTimeSlotController::class)->only(['edit', 'update'])->middleware('permission:edit deliveries');
    Route::resource('delivery-coverage', DeliveryCoverageController::class)
        ->only(['index'])
        ->middleware('permission:view deliveries');
    Route::resource('delivery-coverage', DeliveryCoverageController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->middleware('permission:edit deliveries');
    
    // ============= OUTBOUND FOC (HADIAH) =============
    Route::resource('outbound-focs', OutboundFocController::class)->only(['create', 'store'])->middleware('permission:create outbound foc');
    Route::resource('outbound-focs', OutboundFocController::class)->only(['index', 'show'])->middleware('permission:view outbound foc');
    
    // ============= OUTBOUND RETURN (RETUR) =============
    Route::resource('outbound-returns', OutboundReturnController::class)->only(['create', 'store'])->middleware('permission:create outbound return');
    Route::resource('outbound-returns', OutboundReturnController::class)->only(['index', 'show'])->middleware('permission:view outbound return');
    
    // ============= REPORTS =============
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->middleware('permission:view sales report')->name('sales');
        Route::get('/inventory', [ReportController::class, 'inventory'])->middleware('permission:view inventory report')->name('inventory');
        Route::get('/delivery', [ReportController::class, 'delivery'])->middleware('permission:view delivery report')->name('delivery');
        Route::get('/financial', [ReportController::class, 'financial'])->middleware('permission:view financial report')->name('financial');
        Route::get('/ar-aging', [ReportController::class, 'arAging'])->middleware('permission:view piutang')->name('ar-aging');
        Route::get('/ap-aging', [ReportController::class, 'apAging'])->middleware('permission:view piutang')->name('ap-aging');
        Route::get('/export/{type}', [ReportController::class, 'export'])->middleware('permission:export reports')->name('export');
    });
    
    // ============= ACTIVITY LOGS =============
    Route::prefix('activity-logs')->name('activity-logs.')->middleware('permission:view logs')->group(function () {
        Route::get('/export', [ActivityLogController::class, 'export'])->name('export');
        Route::post('/clear', [ActivityLogController::class, 'clear'])->name('clear');
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{activityLog}', [ActivityLogController::class, 'show'])->name('show');
    });
});

// Auth routes (login, register, etc)
require __DIR__.'/auth.php';

// Fallback route untuk 404
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
