<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\DirectPurchaseController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\OutboundFocController;
use App\Http\Controllers\OutboundReturnController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // ============= DASHBOARD =============
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
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
        Route::delete('users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
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
    Route::resource('units', UnitController::class);
    Route::post('units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('units.toggle-status');
    Route::get('units/list', [UnitController::class, 'getList'])->name('units.list');
    
    // ============= PRODUCT MANAGEMENT =============
    Route::resource('products', ProductController::class);
    Route::post('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
    Route::get('products/{product}/price-history', [ProductController::class, 'priceHistory'])->name('products.price-history');
    
    // ============= CUSTOMER MANAGEMENT =============
    Route::resource('customers', CustomerController::class);
    Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
    Route::post('customers/{customer}/topup-wallet', [CustomerController::class, 'topupWallet'])->name('customers.topup-wallet');
    Route::get('customers/{customer}/order-history', [CustomerController::class, 'orderHistory'])->name('customers.order-history');
    
    // ============= SUPPLIER MANAGEMENT =============
    Route::resource('suppliers', SupplierController::class);
    Route::post('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
    
    // ============= PURCHASE ORDER MANAGEMENT =============
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::get('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveForm'])->name('purchase-orders.receive-form');
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::get('purchase-orders/{purchaseOrder}/payment', [PurchaseOrderController::class, 'paymentForm'])->name('purchase-orders.payment-form');
    Route::post('purchase-orders/{purchaseOrder}/payment', [PurchaseOrderController::class, 'processPayment'])->name('purchase-orders.payment');
    
    // ============= DIRECT PURCHASE MANAGEMENT =============
    Route::resource('direct-purchases', DirectPurchaseController::class);
    
    // ============= CONSIGNMENT MANAGEMENT =============
    Route::resource('consignments', ConsignmentController::class);
    Route::get('consignments/{consignment}/return', [ConsignmentController::class, 'returnForm'])->name('consignments.return-form');
    Route::post('consignments/{consignment}/return', [ConsignmentController::class, 'processReturn'])->name('consignments.return');
    
    // ============= STOCK MANAGEMENT =============
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [StockController::class, 'index'])->name('index');
        Route::get('/low-stock', [StockController::class, 'lowStock'])->name('low-stock');
        Route::get('/movements', [StockController::class, 'movements'])->name('movements');
        Route::get('/{product}', [StockController::class, 'show'])->name('show');
        Route::get('/{product}/add', [StockController::class, 'addStockForm'])->name('add-form');
        Route::post('/{product}/add', [StockController::class, 'addStock'])->name('add');
        Route::get('/{product}/reduce', [StockController::class, 'reduceStockForm'])->name('reduce-form');
        Route::post('/{product}/reduce', [StockController::class, 'reduceStock'])->name('reduce');
        Route::get('/{product}/adjustment', [StockController::class, 'adjustmentForm'])->name('adjustment-form');
        Route::post('/{product}/adjustment', [StockController::class, 'adjustment'])->name('adjustment');
    });
    
    // ============= ORDER MANAGEMENT =============
    Route::resource('orders', OrderController::class);
    Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/process-procurement', [OrderController::class, 'processProcurement'])->name('orders.process-procurement');
    Route::post('orders/{order}/process-repack', [OrderController::class, 'processRepack'])->name('orders.process-repack');
    Route::post('orders/{order}/mark-ready', [OrderController::class, 'markReady'])->name('orders.mark-ready');
    Route::post('orders/{order}/mark-shipped', [OrderController::class, 'markShipped'])->name('orders.mark-shipped');
    Route::post('orders/{order}/mark-delivered', [OrderController::class, 'markDelivered'])->name('orders.mark-delivered');
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::post('order-items/{item}/unavailable', [OrderController::class, 'markItemUnavailable'])->name('order-items.unavailable');
    Route::get('products/{product}/stock-info', [OrderController::class, 'getStockInfo'])->name('products.stock-info');
    
    // ============= DELIVERY MANAGEMENT =============
    Route::resource('deliveries', DeliveryController::class);
    Route::post('deliveries/{delivery}/update-status', [DeliveryController::class, 'updateStatus'])->name('deliveries.update-status');
    Route::get('deliveries/kurir/today', [DeliveryController::class, 'kurirToday'])->name('deliveries.kurir.today');
    Route::post('deliveries/{delivery}/update-location', [DeliveryController::class, 'updateLocation'])->name('deliveries.update-location');
    
    // ============= OUTBOUND FOC (HADIAH) =============
    Route::resource('outbound-focs', OutboundFocController::class);
    
    // ============= OUTBOUND RETURN (RETUR) =============
    Route::resource('outbound-returns', OutboundReturnController::class);
    
    // ============= REPORTS =============
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/delivery', [ReportController::class, 'delivery'])->name('delivery');
        Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
    });
    
    // ============= ACTIVITY LOGS =============
    Route::prefix('activity-logs')->name('activity-logs.')->middleware(['role:super-admin,admin'])->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{activityLog}', [ActivityLogController::class, 'show'])->name('show');
        Route::post('/clear', [ActivityLogController::class, 'clear'])->name('clear');
        Route::get('/export', [ActivityLogController::class, 'export'])->name('export');
    });
});

// Auth routes (login, register, etc)
require __DIR__.'/auth.php';

// Fallback route untuk 404
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
