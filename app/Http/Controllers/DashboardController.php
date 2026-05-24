<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // ========================================
        // DATA EXISTING (Tetap dipertahankan)
        // ========================================
        
        // Hitung total
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $totalRoles = DB::table('roles')->count();
        $totalPermissions = DB::table('permissions')->count();
        
        // Recent users
        $recentUsers = User::with('roles')
            ->latest()
            ->take(5)
            ->get();

        // Users by role
        $userStatsByRole = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as total'))
            ->groupBy('roles.name')
            ->get();

        // ========================================
        // DATA KURMIGO (Untuk Dashboard Toko)
        // ========================================
        
        // Total Customers (hanya yang role customer dan aktif)
        $totalCustomers = User::role('customer')->active()->count();
        
        // Total Products (yang aktif)
        $totalProducts = Product::where('is_active', true)->count();
        
        // Total Orders
        $totalOrders = Order::count();
        
        // Orders Today (yang dibuat hari ini)
        $ordersToday = Order::whereDate('created_at', today())->count();
        
        // Orders for Delivery Today (yang akan dikirim hari ini)
        $ordersDeliveryToday = Order::whereDate('delivery_date', today())
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();
        
        // Active Deliveries (belum completed)
        $activeDeliveries = Delivery::where('status', '!=', 'completed')->count();
        
        // Pending Deliveries (assigned tapi belum diambil)
        $pendingDeliveries = Delivery::where('status', 'assigned')->count();
        
        // Total Revenue (order yang sudah delivered)
        $totalRevenue = Order::where('status', 'delivered')->sum('total');
        
        // Revenue This Month
        $revenueThisMonth = Order::where('status', 'delivered')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
        
        // ========================================
        // TREND DATA (Untuk Persentase)
        // ========================================
        
        // New customers this month
        $newUsersThisMonth = User::role('customer')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // New orders this week
        $newOrdersThisWeek = Order::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        
        // New orders yesterday (untuk perbandingan)
        $ordersYesterday = Order::whereDate('created_at', today()->subDay())->count();
        
        // Percentage change for orders (compared to yesterday)
        $ordersChange = $ordersYesterday > 0 
            ? round(($ordersToday - $ordersYesterday) / $ordersYesterday * 100) 
            : ($ordersToday > 0 ? 100 : 0);
        
        // ========================================
        // RECENT ORDERS
        // ========================================
        
        $recentOrders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // ========================================
        // ORDER STATUS SUMMARY
        // ========================================
        
        $orderStatusSummary = [
            ['label' => 'Pending Payment', 'count' => Order::where('status', Order::STATUS_PENDING_PAYMENT)->count(), 'color' => 'warning'],
            ['label' => 'Paid', 'count' => Order::where('status', Order::STATUS_PAID)->count(), 'color' => 'info'],
            ['label' => 'Checking Stock', 'count' => Order::where('status', Order::STATUS_CHECKING_STOCK)->count(), 'color' => 'info'],
            ['label' => 'Procuring', 'count' => Order::where('status', Order::STATUS_PROCURING)->count(), 'color' => 'info'],
            ['label' => 'Repacking', 'count' => Order::where('status', Order::STATUS_REPACKING)->count(), 'color' => 'info'],
            ['label' => 'Ready', 'count' => Order::where('status', Order::STATUS_READY)->count(), 'color' => 'success'],
            ['label' => 'Shipped', 'count' => Order::where('status', Order::STATUS_SHIPPED)->count(), 'color' => 'success'],
            ['label' => 'Delivered', 'count' => Order::where('status', Order::STATUS_DELIVERED)->count(), 'color' => 'success'],
            ['label' => 'Cancelled', 'count' => Order::where('status', Order::STATUS_CANCELLED)->count(), 'color' => 'danger'],
        ];
        
        // Filter yang count > 0
        $orderStatusSummary = array_values(array_filter($orderStatusSummary, function($item) {
            return $item['count'] > 0;
        }));
        
        // ========================================
        // ADDITIONAL DATA FOR 8 CARDS
        // ========================================
        
        // Pending Orders (belum dibayar)
        $pendingOrdersCount = Order::where('status', Order::STATUS_PENDING_PAYMENT)->count();
        
        // Completed Orders (delivered this month)
        $completedOrdersCount = Order::where('status', Order::STATUS_DELIVERED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // ========================================
        // TOP PRODUCTS (Best Seller)
        // ========================================
        
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('units', 'products.unit_id', '=', 'units.id')
            ->select(
                'products.id',
                'products.name',
                'units.name as unit_name',
                'units.symbol as unit_symbol',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->where('order_items.is_available', true)
            ->groupBy('products.id', 'products.name', 'units.name', 'units.symbol')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();
        
        // ========================================
        // STOCK SUMMARY
        // ========================================
        
        // Low stock products (stock <= min_stock and stock > 0)
        $lowStockProducts = Product::lowStock()->count();
        
        // Out of stock products (stock = 0)
        $outOfStockProducts = Product::outOfStock()->count();
        
        // Stock movement this month
        $stockInThisMonth = StockMovement::where('type', StockMovement::TYPE_IN)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('quantity');
        
        $stockOutThisMonth = StockMovement::where('type', StockMovement::TYPE_OUT)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('quantity');
        
        // ========================================
        // WEEKLY SALES CHART DATA (Untuk Chart nanti)
        // ========================================
        
        $weeklySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $weeklySales[] = [
                'date' => $date->format('D'),
                'full_date' => $date->format('Y-m-d'),
                'total' => Order::whereDate('created_at', $date)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->sum('total'),
                'count' => Order::whereDate('created_at', $date)->count(),
            ];
        }
        
        // ========================================
        // COMPACT DATA UNTUK VIEW
        // ========================================
        
        // Data untuk stat cards utama (existing)
        $stats = [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'total_roles' => $totalRoles,
            'total_permissions' => $totalPermissions,
            'recent_users' => $recentUsers,
            
            // KurmiGO specific
            'total_customers' => $totalCustomers,
            'total_products' => $totalProducts,
            'total_orders' => $totalOrders,
            'orders_today' => $ordersToday,
            'orders_delivery_today' => $ordersDeliveryToday,
            'active_deliveries' => $activeDeliveries,
            'pending_deliveries' => $pendingDeliveries,
            'total_revenue' => $totalRevenue,
            'revenue_this_month' => $revenueThisMonth,
            'new_customers_this_month' => $newUsersThisMonth,
            'new_orders_this_week' => $newOrdersThisWeek,
            'orders_change' => $ordersChange,
        ];
        
        // Kirim semua data ke view menggunakan array
        return view('dashboard', [
            // Existing data
            'stats' => $stats,
            'userStatsByRole' => $userStatsByRole,
            
            // KurmiGO data (individual variables)
            'totalUsers' => $totalUsers,
            'totalOrders' => $totalOrders,
            'activeDeliveries' => $activeDeliveries,
            'pendingDeliveries' => $pendingDeliveries,
            'totalRevenue' => $totalRevenue,
            'newUsersThisMonth' => $newUsersThisMonth,
            'newOrdersThisWeek' => $newOrdersThisWeek,
            'recentOrders' => $recentOrders,
            'orderStatusSummary' => $orderStatusSummary,
            'topProducts' => $topProducts,
            'weeklySales' => $weeklySales,
            'ordersToday' => $ordersToday,
            'totalProducts' => $totalProducts,
            'totalCustomers' => $totalCustomers,
            'revenueThisMonth' => $revenueThisMonth,
            
            // Additional data for 8 cards
            'pendingOrdersCount' => $pendingOrdersCount,
            'completedOrdersCount' => $completedOrdersCount,
            
            // Stock summary
            'lowStockProducts' => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'stockInThisMonth' => $stockInThisMonth,
            'stockOutThisMonth' => $stockOutThisMonth,
        ]);
    }
    
    /**
     * Get chart data via AJAX (untuk realtime update)
     */
    public function getChartData()
    {
        $weeklySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $weeklySales[] = [
                'date' => $date->format('D'),
                'full_date' => $date->format('Y-m-d'),
                'total' => Order::whereDate('created_at', $date)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->sum('total'),
                'count' => Order::whereDate('created_at', $date)->count(),
            ];
        }
        
        return response()->json([
            'weekly_sales' => $weeklySales,
            'total_orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue_today' => Order::whereDate('created_at', today())
                ->where('status', Order::STATUS_DELIVERED)
                ->sum('total'),
        ]);
    }
}