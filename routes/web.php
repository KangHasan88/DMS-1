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
use App\Http\Controllers\CustomerTypeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierCategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\DirectPurchaseController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\OutboundFocController;
use App\Http\Controllers\OutboundReturnController;
use App\Http\Controllers\LocaleController;
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
    Route::middleware(['role:super-admin,admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleActive'])->name('users.toggle-status');
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
    Route::resource('customers', CustomerController::class)->only(['create', 'store'])->middleware('permission:create customers');
    Route::resource('customers', CustomerController::class)->only(['index', 'show'])->middleware('permission:view customers');
    Route::resource('customers', CustomerController::class)->only(['edit', 'update'])->middleware('permission:edit customers');
    Route::resource('customers', CustomerController::class)->only(['destroy'])->middleware('permission:delete customers');
    Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->middleware('permission:edit customers')->name('customers.toggle-status');
    Route::post('customers/{customer}/topup-wallet', [CustomerController::class, 'topupWallet'])->middleware('permission:process payment')->name('customers.topup-wallet');
    Route::get('customers/{customer}/order-history', [CustomerController::class, 'orderHistory'])->middleware('permission:view order history')->name('customers.order-history');

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
    
    // ============= ORDER MANAGEMENT =============
    Route::resource('orders', OrderController::class)->only(['create', 'store'])->middleware('permission:create sales order');
    Route::resource('orders', OrderController::class)->only(['index', 'show'])->middleware('permission:view sales order');
    Route::resource('orders', OrderController::class)->only(['edit', 'update'])->middleware('permission:edit sales order');
    Route::resource('orders', OrderController::class)->only(['destroy'])->middleware('permission:delete sales order');
    Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->middleware('permission:process orders')->name('orders.update-status');
    Route::post('orders/{order}/process-procurement', [OrderController::class, 'processProcurement'])->middleware('permission:process orders')->name('orders.process-procurement');
    Route::post('orders/{order}/process-repack', [OrderController::class, 'processRepack'])->middleware('permission:process orders')->name('orders.process-repack');
    Route::post('orders/{order}/mark-ready', [OrderController::class, 'markReady'])->middleware('permission:process orders')->name('orders.mark-ready');
    Route::post('orders/{order}/mark-shipped', [OrderController::class, 'markShipped'])->middleware('permission:process deliveries')->name('orders.mark-shipped');
    Route::post('orders/{order}/mark-delivered', [OrderController::class, 'markDelivered'])->middleware('permission:process deliveries')->name('orders.mark-delivered');
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->middleware('permission:view invoice')->name('orders.invoice');
    Route::post('order-items/{item}/unavailable', [OrderController::class, 'markItemUnavailable'])->middleware('permission:process orders')->name('order-items.unavailable');
    
    // ============= DELIVERY MANAGEMENT =============
    Route::get('deliveries/kurir/today', [DeliveryController::class, 'kurirToday'])->middleware('permission:view deliveries')->name('deliveries.kurir.today');
    Route::resource('deliveries', DeliveryController::class)->only(['create', 'store'])->middleware('permission:create deliveries');
    Route::resource('deliveries', DeliveryController::class)->only(['index', 'show'])->middleware('permission:view deliveries');
    Route::resource('deliveries', DeliveryController::class)->only(['edit', 'update'])->middleware('permission:edit deliveries');
    Route::resource('deliveries', DeliveryController::class)->only(['destroy'])->middleware('permission:delete deliveries');
    Route::post('deliveries/{delivery}/update-status', [DeliveryController::class, 'updateStatus'])->middleware('permission:process deliveries')->name('deliveries.update-status');
    Route::post('deliveries/{delivery}/update-location', [DeliveryController::class, 'updateLocation'])->middleware('permission:process deliveries')->name('deliveries.update-location');
    
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
        Route::get('/export/{type}', [ReportController::class, 'export'])->middleware('permission:export reports')->name('export');
    });
    
    // ============= ACTIVITY LOGS =============
    Route::prefix('activity-logs')->name('activity-logs.')->middleware(['role:super-admin,admin'])->group(function () {
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
