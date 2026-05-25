<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'KURMIGO')) - Kurir Kami, Go!</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Font Awesome 6 (untuk icon kurir) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* ========================================
           THEME HIJAU TUA PROFESSIONAL - KURMIGO
           Dark Green Professional Theme
           ======================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        /* Color Palette - Hijau Tua Professional */
        :root {
            --k-white: #ffffff;
            --k-white-off: #f8fafc;
            --k-gray-50: #f9fafb;
            --k-gray-100: #f1f5f9;
            --k-gray-200: #e2e8f0;
            --k-gray-300: #cbd5e1;
            --k-gray-400: #94a3b8;
            --k-gray-500: #64748b;
            --k-gray-600: #475569;
            --k-gray-700: #334155;
            --k-gray-800: #1e293b;
            --k-gray-900: #0f172a;
            --k-black: #000000;
            
            /* Hijau Tua Professional */
            --k-green: #166534;
            --k-green-dark: #14532d;
            --k-green-darker: #0f3d1f;
            --k-green-light: #dcfce7;
            --k-green-mid: #15803d;
            --k-green-accent: #22c55e;
            --k-orange: #f97316;
            --k-orange-light: #ffedd5;
            
            --k-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --k-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --k-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            --k-shadow-green: 0 4px 12px rgba(22, 101, 52, 0.15);
        }

        /* Layout Container */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* ========================================
           SIDEBAR - Putih dengan Border
           ======================================== */
        .sidebar {
            width: 260px;
            background: var(--k-white);
            color: var(--k-gray-800);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            border-right: 1px solid var(--k-gray-200);
            z-index: 1000;
        }

        /* Custom Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--k-gray-100);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--k-gray-300);
            border-radius: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--k-green);
        }

        /* Sidebar Header - Compact */
        .sidebar-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--k-gray-200);
            margin-bottom: 0.5rem;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--k-green-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon i {
            color: var(--k-green);
            font-size: 1.2rem;
        }

        .logo-text h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--k-gray-800);
            margin: 0;
            line-height: 1.2;
        }

        .logo-text p {
            font-size: 0.55rem;
            color: var(--k-gray-500);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* User Profile - Ultra Compact */
        .user-profile-compact {
            padding: 0.6rem 1rem;
            border-bottom: 1px solid var(--k-gray-200);
            margin-bottom: 0.75rem;
        }

        .user-info-compact {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .user-avatar-compact {
            width: 28px;
            height: 28px;
            background: var(--k-gray-100);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--k-gray-200);
        }

        .user-avatar-compact i {
            font-size: 1rem;
            color: var(--k-gray-500);
        }

        .user-details-compact {
            flex: 1;
            min-width: 0;
        }

        .user-name-compact {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--k-gray-800);
            margin-bottom: 0.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email-compact {
            font-size: 0.6rem;
            color: var(--k-gray-500);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Navigation Menu - Ultra Compact */
        .nav-menu {
            padding: 0 0.75rem 0.75rem 0.75rem;
        }

        .nav-section {
            margin-bottom: 1rem;
        }

        .nav-section-title {
            font-size: 0.55rem;
            text-transform: uppercase;
            color: var(--k-gray-500);
            font-weight: 600;
            padding: 0 0.6rem;
            margin-bottom: 0.4rem;
            letter-spacing: 0.5px;
        }

        .nav-item {
            list-style: none;
            margin-bottom: 0.1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.4rem 0.6rem;
            color: var(--k-gray-600);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .nav-link i {
            font-size: 1rem;
            width: 18px;
            color: var(--k-gray-500);
        }

        .nav-link span {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .nav-link:hover {
            background: var(--k-gray-100);
            color: var(--k-gray-800);
        }

        .nav-link:hover i {
            color: var(--k-green);
        }

        .nav-link.active {
            background: var(--k-green-light);
            color: var(--k-green);
        }

        .nav-link.active i {
            color: var(--k-green);
        }

        /* Badge di menu */
        .nav-badge {
            margin-left: auto;
            background: var(--k-green);
            color: var(--k-white);
            font-size: 0.5rem;
            font-weight: 600;
            padding: 0.1rem 0.4rem;
            border-radius: 30px;
        }

        /* ========================================
           MAIN CONTENT
           ======================================== */
        .main-content {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
            background: var(--k-white-off);
        }

        /* ========================================
           TOP BAR - Compact
           ======================================== */
        .top-bar {
            background: var(--k-white);
            padding: 0.6rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--k-gray-200);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .page-title h1 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--k-gray-800);
            margin: 0;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--k-gray-500);
            font-size: 0.65rem;
            margin-top: 0.1rem;
        }

        .breadcrumb a {
            color: var(--k-gray-500);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: var(--k-green);
        }

        /* Top Bar Right */
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        /* User Info Card - Compact */
        .user-info-card {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.2rem 0.6rem 0.2rem 0.4rem;
            background: var(--k-gray-50);
            border-radius: 24px;
            border: 1px solid var(--k-gray-200);
        }

        .avatar-small {
            width: 24px;
            height: 24px;
            background: var(--k-gray-100);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-small i {
            font-size: 0.8rem;
            color: var(--k-gray-600);
        }

        .user-detail .name {
            font-weight: 600;
            color: var(--k-gray-800);
            font-size: 0.7rem;
        }

        .user-detail .role {
            font-size: 0.55rem;
            color: var(--k-gray-500);
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .role-badge {
            background: var(--k-green);
            color: var(--k-white);
            padding: 0.05rem 0.3rem;
            border-radius: 20px;
            font-size: 0.45rem;
            font-weight: 600;
        }

        /* Notification */
        .notification {
            position: relative;
            cursor: pointer;
            padding: 0.2rem;
        }

        .notification i {
            font-size: 1rem;
            color: var(--k-gray-500);
        }

        .notification:hover i {
            color: var(--k-green);
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--k-green);
            color: var(--k-white);
            font-size: 0.45rem;
            font-weight: 600;
            padding: 0.1rem 0.25rem;
            border-radius: 20px;
            min-width: 14px;
            text-align: center;
        }

        /* Date Display */
        .date-display {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            background: var(--k-gray-50);
            border-radius: 24px;
            color: var(--k-gray-600);
            font-size: 0.65rem;
            border: 1px solid var(--k-gray-200);
        }

        .date-display i {
            color: var(--k-green);
            font-size: 0.7rem;
        }

        /* Logout Button */
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            background: transparent;
            border: 1px solid var(--k-gray-300);
            border-radius: 24px;
            color: var(--k-gray-600);
            font-size: 0.65rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: var(--k-green);
            border-color: var(--k-green);
            color: var(--k-white);
        }

        .logout-btn i {
            font-size: 0.8rem;
        }

        .language-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.15rem;
            padding: 0.15rem;
            background: var(--k-gray-50);
            border: 1px solid var(--k-gray-200);
            border-radius: 24px;
        }

        .language-toggle form {
            margin: 0;
        }

        .language-toggle button {
            border: 0;
            border-radius: 20px;
            padding: 0.2rem 0.45rem;
            background: transparent;
            color: var(--k-gray-500);
            font-size: 0.6rem;
            font-weight: 700;
            cursor: pointer;
        }

        .language-toggle button.active {
            background: var(--k-green);
            color: var(--k-white);
        }

        /* ========================================
           CONTENT AREA
           ======================================== */
        .content-area {
            padding: 1.25rem;
        }

        /* Cards */
        .dms-card {
            background: var(--k-white);
            border-radius: 0.6rem;
            padding: 0.875rem;
            border: 1px solid var(--k-gray-200);
            box-shadow: var(--k-shadow-sm);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.875rem;
            margin-bottom: 1.25rem;
        }

        .stat-card {
            background: var(--k-white);
            border-radius: 0.6rem;
            padding: 0.875rem;
            border: 1px solid var(--k-gray-200);
            transition: all 0.2s;
        }

        .stat-card:hover {
            border-color: var(--k-green);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .stat-icon {
            width: 32px;
            height: 32px;
            background: var(--k-green-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--k-green);
        }

        .stat-trend {
            font-size: 0.6rem;
            padding: 0.1rem 0.4rem;
            border-radius: 20px;
            background: #e6f7e6;
            color: #16a34a;
            font-weight: 600;
        }

        .stat-trend.down {
            background: #fee2e2;
            color: #dc2626;
        }

        .stat-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--k-gray-800);
            margin-bottom: 0.1rem;
        }

        .stat-label {
            color: var(--k-gray-500);
            font-size: 0.65rem;
        }

        /* Table */
        .dms-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dms-table th {
            text-align: left;
            padding: 0.6rem;
            color: var(--k-gray-500);
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 1px solid var(--k-gray-200);
        }

        .dms-table td {
            padding: 0.6rem;
            border-bottom: 1px solid var(--k-gray-200);
            color: var(--k-gray-700);
            font-size: 0.7rem;
        }

        .dms-table tbody tr:hover {
            background: var(--k-green-light);
        }

        /* Badges */
        .dms-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.6rem;
            font-weight: 600;
            display: inline-block;
        }

        .dms-badge-success {
            background: var(--k-green-light);
            color: var(--k-green);
        }

        .dms-badge-warning {
            background: #fef3c7;
            color: #d97706;
        }

        .dms-badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .dms-badge-info {
            background: var(--k-gray-100);
            color: var(--k-gray-600);
        }

        /* Buttons */
        .dms-btn {
            padding: 0.35rem 0.9rem;
            border-radius: 1.5rem;
            font-weight: 500;
            font-size: 0.7rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.2s;
        }

        .dms-btn-primary {
            background: var(--k-green);
            color: var(--k-white);
        }

        .dms-btn-primary:hover {
            background: var(--k-green-dark);
            transform: translateY(-1px);
        }

        .dms-btn-outline {
            background: transparent;
            border: 1px solid var(--k-gray-300);
            color: var(--k-gray-600);
        }

        .dms-btn-outline:hover {
            border-color: var(--k-green);
            color: var(--k-green);
        }

        /* Form Controls */
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            background: var(--k-white);
            border: 1px solid var(--k-gray-300);
            border-radius: 0.5rem;
            color: var(--k-gray-800);
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--k-green);
            box-shadow: 0 0 0 3px var(--k-green-light);
        }

        .form-label {
            display: block;
            margin-bottom: 0.3rem;
            color: var(--k-gray-700);
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 260px;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-bar-right .date-display,
            .top-bar-right .user-detail {
                display: none;
            }
        }

        /* Menu Toggle Button untuk Mobile */
        .menu-toggle {
            display: none;
            font-size: 1.1rem;
            color: var(--k-gray-600);
            cursor: pointer;
            margin-right: 0.6rem;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .page-title {
                display: flex;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <!-- Logo -->
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="logo-wrapper">
                    <div class="logo-icon">
                        <i class="fas fa-person-walking-luggage"></i>
                    </div>
                    <div class="logo-text">
                        <h2>KURMIGO</h2>
                        <p>KURIR KAMI, GO!</p>
                    </div>
                </a>
            </div>

            <!-- User Profile - Compact -->
            <div class="user-profile-compact">
                <div class="user-info-compact">
                    <div class="user-avatar-compact">
                        @if(Auth::user()->photo)
                            <img src="{{ asset('storage/' . Auth::user()->photo) }}" alt="{{ Auth::user()->name }}" style="width: 28px; height: 28px; border-radius: 8px; object-fit: cover;">
                        @else
                            <i class="bi bi-person-circle"></i>
                        @endif
                    </div>
                    <div class="user-details-compact">
                        <div class="user-name-compact">{{ Auth::user()->name ?? 'Admin' }}</div>
                        <div class="user-email-compact">{{ Auth::user()->email ?? 'admin@kurmigo.com' }}</div>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="nav-menu">
                <!-- SECTION: HOME -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.home') }}</div>
                    <ul style="list-style: none; padding: 0;">
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>{{ __('navigation.dashboard') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- SECTION: OPERATIONS -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.operations') }}</div>
                    <ul style="list-style: none; padding: 0;">
                        <!-- Sales Orders -->
                        @can('view sales order')
                        <li class="nav-item">
                            <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                                <i class="bi bi-cart"></i>
                                <span>{{ __('navigation.orders') }}</span>
                                @php
                                    $pendingOrders = \App\Models\Order::whereIn('status', ['pending_payment', 'paid'])->count();
                                @endphp
                                @if($pendingOrders > 0)
                                    <span class="nav-badge">{{ $pendingOrders }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan
                        
                        <!-- Deliveries -->
                        @can('view deliveries')
                        <li class="nav-item">
                            <a href="{{ route('deliveries.index') }}" class="nav-link {{ request()->routeIs('deliveries.*') ? 'active' : '' }}">
                                <i class="bi bi-truck"></i>
                                <span>{{ __('navigation.deliveries') }}</span>
                                @php
                                    $activeDeliveries = \App\Models\Delivery::where('status', '!=', 'completed')->count();
                                @endphp
                                @if($activeDeliveries > 0)
                                    <span class="nav-badge">{{ $activeDeliveries }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan
                        
                        <!-- FOC Out (Hadiah) -->
                        @can('view outbound foc')
                        <li class="nav-item">
                            <a href="{{ route('outbound-focs.index') }}" class="nav-link {{ request()->routeIs('outbound-focs.*') ? 'active' : '' }}">
                                <i class="bi bi-gift"></i>
                                <span>{{ __('navigation.foc_out') }}</span>
                            </a>
                        </li>
                        @endcan
                        
                        <!-- Return Out (Retur) -->
                        @can('view outbound return')
                        <li class="nav-item">
                            <a href="{{ route('outbound-returns.index') }}" class="nav-link {{ request()->routeIs('outbound-returns.*') ? 'active' : '' }}">
                                <i class="bi bi-arrow-return-left"></i>
                                <span>{{ __('navigation.return_out') }}</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>

                <!-- SECTION: PROCUREMENT -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.procurement') }}</div>
                    <ul style="list-style: none; padding: 0;">
                        <!-- Purchase Orders (Tempo) -->
                        @can('view purchase order')
                        <li class="nav-item">
                            <a href="{{ route('purchase-orders.index') }}" class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                                <i class="bi bi-receipt"></i>
                                <span>{{ __('navigation.purchase_orders') }}</span>
                                @php
                                    $pendingPOs = \App\Models\PurchaseOrder::where('status', 'pending')->count();
                                @endphp
                                @if($pendingPOs > 0)
                                    <span class="nav-badge">{{ $pendingPOs }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan
                        
                        <!-- Direct Purchase (Cash) -->
                        @can('view direct purchase')
                        <li class="nav-item">
                            <a href="{{ route('direct-purchases.index') }}" class="nav-link {{ request()->routeIs('direct-purchases.*') ? 'active' : '' }}">
                                <i class="bi bi-cash"></i>
                                <span>{{ __('navigation.direct_purchase') }}</span>
                            </a>
                        </li>
                        @endcan
                        
                        <!-- Consignment (Titip Jual) -->
                        @can('view consignments')
                        <li class="nav-item">
                            <a href="{{ route('consignments.index') }}" class="nav-link {{ request()->routeIs('consignments.*') ? 'active' : '' }}">
                                <i class="bi bi-hand-thumbs-up"></i>
                                <span>{{ __('navigation.consignment') }}</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>

                <!-- SECTION: INVENTORY -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.inventory') }}</div>
                    <ul style="list-style: none; padding: 0;">
                        <!-- Stock Management -->
                        @can('view warehouse')
                        <li class="nav-item">
                            <a href="{{ route('stock.index') }}" class="nav-link {{ request()->routeIs('stock.index') ? 'active' : '' }}">
                                <i class="bi bi-box-seam"></i>
                                <span>{{ __('navigation.stock_management') }}</span>
                                @php
                                    $lowStockCount = \App\Models\Product::lowStock()->count();
                                @endphp
                                @if($lowStockCount > 0)
                                    <span class="nav-badge" style="background: var(--k-orange);">{{ $lowStockCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan
                        
                        <!-- Stock Movement Log -->
                        @can('view stock movement')
                        <li class="nav-item">
                            <a href="{{ route('stock.movements') }}" class="nav-link {{ request()->routeIs('stock.movements') ? 'active' : '' }}">
                                <i class="bi bi-journal-bookmark-fill"></i>
                                <span>{{ __('navigation.stock_movements') }}</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>

                <!-- SECTION: MASTER DATA -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.master_data') }}</div>
                    <ul style="list-style: none; padding: 0;">
                        <!-- Master Satuan -->
                        @can('view units')
                        <li class="nav-item">
                            <a href="{{ route('units.index') }}" class="nav-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
                                <i class="bi bi-rulers"></i>
                                <span>{{ __('navigation.units') }}</span>
                            </a>
                        </li>
                        @endcan
                        
                        <!-- Customers -->
                        @can('view customers')
                        <li class="nav-item">
                            <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                <i class="bi bi-people"></i>
                                <span>{{ __('navigation.customers') }}</span>
                            </a>
                        </li>
                        @endcan
                        
                        <!-- Suppliers -->
                        @can('view suppliers')
                        <li class="nav-item">
                            <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                                <i class="bi bi-shop"></i>
                                <span>{{ __('navigation.suppliers') }}</span>
                            </a>
                        </li>
                        @endcan
                        
                        <!-- Products -->
                        @can('view products')
                        <li class="nav-item">
                            <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                                <i class="bi bi-box-seam"></i>
                                <span>{{ __('navigation.products') }}</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>

                <!-- SECTION: REPORTS -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.reports') }}</div>
                    <ul style="list-style: none; padding: 0;">
                        <li class="nav-item">
                            @can('view sales report')
                            <a href="{{ route('reports.sales') }}" class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                                <i class="bi bi-graph-up"></i>
                                <span>{{ __('navigation.sales_report') }}</span>
                            </a>
                            @endcan
                        </li>
                        <li class="nav-item">
                            @can('view inventory report')
                            <a href="{{ route('reports.inventory') }}" class="nav-link {{ request()->routeIs('reports.inventory') ? 'active' : '' }}">
                                <i class="bi bi-box-seam"></i>
                                <span>{{ __('navigation.inventory_report') }}</span>
                            </a>
                            @endcan
                        </li>
                        <li class="nav-item">
                            @can('view delivery report')
                            <a href="{{ route('reports.delivery') }}" class="nav-link {{ request()->routeIs('reports.delivery') ? 'active' : '' }}">
                                <i class="bi bi-truck"></i>
                                <span>{{ __('navigation.delivery_report') }}</span>
                            </a>
                            @endcan
                        </li>
                        <li class="nav-item">
                            @can('view financial report')
                            <a href="{{ route('reports.financial') }}" class="nav-link {{ request()->routeIs('reports.financial') ? 'active' : '' }}">
                                <i class="bi bi-currency-dollar"></i>
                                <span>{{ __('navigation.financial_report') }}</span>
                            </a>
                            @endcan
                        </li>
                    </ul>
                </div>

                <!-- SECTION: MANAGEMENT -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.management') }}</div>
                    <ul style="list-style: none; padding: 0;">
                        @can('view users')
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="bi bi-people-fill"></i>
                                <span>{{ __('navigation.users') }}</span>
                            </a>
                        </li>
                        @endcan
                        
                        @can('view roles')
                        <li class="nav-item">
                            <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                <i class="bi bi-shield"></i>
                                <span>{{ __('navigation.roles') }}</span>
                            </a>
                        </li>
                        @endcan

                        @can('view logs')
                        <li class="nav-item">
                            <a href="{{ route('activity-logs.index') }}" class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>{{ __('navigation.activity_logs') }}</span>
                            </a>
                        </li>
                        @endcan
                        
                        <li class="nav-item">
                            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                                <i class="bi bi-person-circle"></i>
                                <span>{{ __('navigation.profile') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <i class="bi bi-list menu-toggle" onclick="toggleSidebar()"></i>
                    <div>
                        <h1>@yield('page-title', 'Dashboard')</h1>
                        <div class="breadcrumb">
                            <a href="{{ route('dashboard') }}">{{ __('navigation.home') }}</a>
                            <i class="bi bi-chevron-right"></i>
                            <span>@yield('breadcrumb', 'Dashboard')</span>
                        </div>
                    </div>
                </div>

                <div class="top-bar-right">
                    <div class="language-toggle" aria-label="{{ __('navigation.language') }}">
                        @foreach(['id' => __('navigation.indonesian'), 'en' => __('navigation.english')] as $locale => $label)
                            <form method="POST" action="{{ route('locale.update') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $locale }}">
                                <button type="submit" class="{{ app()->getLocale() === $locale ? 'active' : '' }}">{{ $label }}</button>
                            </form>
                        @endforeach
                    </div>

                    <div class="user-info-card">
                        <div class="avatar-small">
                            @if(Auth::user()->photo)
                                <img src="{{ asset('storage/' . Auth::user()->photo) }}" alt="{{ Auth::user()->name }}" style="width: 24px; height: 24px; border-radius: 24px; object-fit: cover;">
                            @else
                                <i class="bi bi-person-circle"></i>
                            @endif
                        </div>
                        <div class="user-detail">
                            <div class="name">{{ Auth::user()->name ?? 'Admin' }}</div>
                            <div class="role">
                                <i class="bi bi-shield-check"></i>
                                <span>{{ Auth::user()->roles->pluck('name')->first() ?? 'Admin' }}</span>
                                <span class="role-badge">KURMIGO</span>
                            </div>
                        </div>
                    </div>

                    <div class="date-display">
                        <i class="bi bi-calendar"></i>
                        <span>{{ date('d M Y') }}</span>
                    </div>
                    
                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="logout-btn">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>{{ __('navigation.logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Content Area with Flash Messages -->
            <div class="content-area">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="alert alert-success" style="background: #dcfce7; border-left: 4px solid #166534; padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-check-circle-fill" style="color: #166534; font-size: 1.2rem;"></i>
                        <span style="color: #14532d;">{{ session('success') }}</span>
                        <button onclick="this.parentElement.style.display='none'" style="margin-left: auto; background: none; border: none; cursor: pointer; color: #166534;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-error" style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-exclamation-triangle-fill" style="color: #dc2626; font-size: 1.2rem;"></i>
                        <span style="color: #991b1b;">{{ session('error') }}</span>
                        <button onclick="this.parentElement.style.display='none'" style="margin-left: auto; background: none; border: none; cursor: pointer; color: #dc2626;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                @endif
                
                @if(session('warning'))
                    <div class="alert alert-warning" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-exclamation-triangle-fill" style="color: #f59e0b; font-size: 1.2rem;"></i>
                        <span style="color: #92400e;">{{ session('warning') }}</span>
                        <button onclick="this.parentElement.style.display='none'" style="margin-left: auto; background: none; border: none; cursor: pointer; color: #f59e0b;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                @endif
                
                @if(session('info'))
                    <div class="alert alert-info" style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-info-circle-fill" style="color: #3b82f6; font-size: 1.2rem;"></i>
                        <span style="color: #1e40af;">{{ session('info') }}</span>
                        <button onclick="this.parentElement.style.display='none'" style="margin-left: auto; background: none; border: none; cursor: pointer; color: #3b82f6;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                @endif
                
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuIcon = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768) {
                if (sidebar && menuIcon && !sidebar.contains(event.target) && !menuIcon.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
        
        // Auto hide flash messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>

    @stack('scripts')
</body>
</html>
