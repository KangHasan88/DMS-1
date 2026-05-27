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
           KURMIGO BRAND SYSTEM
           Dark blue theme inspired by the public website
           ======================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background:
                radial-gradient(circle at 92% 8%, rgba(3, 13, 32, 0.07), transparent 26rem),
                radial-gradient(circle at 10% 2%, rgba(6, 26, 63, 0.08), transparent 24rem),
                #f7faff;
            color: #1e293b;
        }

        /* Color Palette - KURMIGO Professional */
        :root {
            --k-white: #ffffff;
            --k-white-off: #f7faff;
            --k-gray-50: #f8fbff;
            --k-gray-100: #eef4ff;
            --k-gray-200: #dce7f7;
            --k-gray-300: #cbd5e1;
            --k-gray-400: #94a3b8;
            --k-gray-500: #64748b;
            --k-gray-600: #475569;
            --k-gray-700: #334155;
            --k-gray-800: #1e293b;
            --k-gray-900: #0f172a;
            --k-black: #000000;
            
            --k-blue: #061a3f;
            --k-blue-dark: #04122d;
            --k-blue-darker: #030d20;
            --k-blue-light: #e9eef8;
            --k-blue-mid: #0b2f6f;
            --k-blue-accent: #123d86;
            --k-orange: #ff7a00;
            --k-orange-dark: #e55700;
            --k-orange-light: #fff1df;
            --k-orange-soft: #fff8f1;
            --k-success: #16a34a;
            --k-success-dark: #15803d;
            --k-success-light: #e6f7e6;
            --k-warning: var(--k-orange);
            --k-warning-dark: var(--k-orange-dark);
            --k-warning-light: var(--k-orange-light);
            --k-red: #dc2626;

            /* Backward-compatible aliases used by existing views as brand color. */
            --k-green: var(--k-blue);
            --k-green-dark: var(--k-blue-dark);
            --k-green-darker: var(--k-blue-darker);
            --k-green-light: var(--k-blue-light);
            --k-green-mid: var(--k-blue-mid);
            --k-green-accent: var(--k-blue-accent);
            
            --k-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05);
            --k-shadow-md: 0 10px 24px rgba(3, 13, 32, 0.08);
            --k-shadow-lg: 0 18px 42px rgba(3, 13, 32, 0.10);
            --k-shadow-green: 0 8px 18px rgba(6, 26, 63, 0.20);
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
            background: rgba(255, 255, 255, 0.94);
            color: var(--k-gray-800);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            border-right: 1px solid var(--k-gray-200);
            box-shadow: 10px 0 32px rgba(15, 82, 186, 0.06);
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
            background: var(--k-blue);
        }

        /* Sidebar Header - Compact */
        .sidebar-header {
            padding: 0.95rem 0.95rem 0.1rem;
            margin-bottom: 0;
            min-height: 116px;
            position: relative;
            overflow: visible;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            text-decoration: none;
            min-height: 100px;
            padding-left: 7.2rem;
        }

        .brand-robot {
            position: absolute;
            left: 0.25rem;
            top: 0.2rem;
            width: 132px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 18px 22px rgba(3, 13, 32, 0.18));
            z-index: 4;
            pointer-events: none;
        }

        .logo-text h2 {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--k-gray-900);
            margin: 0;
            line-height: 1.05;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .logo-text h2 .brand-blue {
            color: var(--k-blue);
        }

        .logo-text h2 .brand-orange {
            color: var(--k-blue);
        }

        .logo-text p {
            display: block;
            font-size: 0.45rem;
            color: var(--k-gray-500);
            margin: 0.15rem 0 0;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            white-space: nowrap;
        }

        /* Navigation Menu - Ultra Compact */
        .nav-menu {
            padding: 0 0.75rem 0.75rem 0.75rem;
            position: relative;
            z-index: 2;
        }

        .nav-section {
            margin-bottom: 0.9rem;
        }

        .nav-section:first-child {
            padding-top: 0;
        }

        .nav-section:first-child .nav-section-title {
            display: none;
        }

        .nav-section-title {
            font-size: 0.55rem;
            text-transform: uppercase;
            color: #7b8aa3;
            font-weight: 700;
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
            padding: 0.46rem 0.62rem;
            color: var(--k-gray-600);
            text-decoration: none;
            border-radius: 8px;
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
            background: var(--k-blue-light);
            color: var(--k-blue);
        }

        .nav-link:hover i {
            color: var(--k-blue);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--k-blue-dark), var(--k-blue));
            color: var(--k-white);
            box-shadow: var(--k-shadow-green);
        }

        .nav-link.active i {
            color: var(--k-white);
        }

        /* Badge di menu */
        .nav-badge {
            margin-left: auto;
            background: var(--k-orange);
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
            background:
                radial-gradient(circle at 88% 3%, rgba(3, 13, 32, 0.07), transparent 24rem),
                radial-gradient(circle at 12% 0, rgba(6, 26, 63, 0.08), transparent 26rem),
                var(--k-white-off);
        }

        /* ========================================
           TOP BAR - Compact
           ======================================== */
        .top-bar {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            padding: 0.7rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--k-gray-200);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 8px 24px rgba(15, 82, 186, 0.05);
        }

        .page-title h1 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--k-gray-900);
            margin: 0;
            line-height: 1.25;
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
            color: var(--k-blue);
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
            background: var(--k-white);
            border-radius: 24px;
            border: 1px solid var(--k-gray-200);
            box-shadow: var(--k-shadow-sm);
        }

        .avatar-small {
            width: 24px;
            height: 24px;
            background: var(--k-blue);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-small i {
            font-size: 0.8rem;
            color: var(--k-white);
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
            background: var(--k-orange);
            color: var(--k-white);
            padding: 0.05rem 0.3rem;
            border-radius: 20px;
            font-size: 0.45rem;
            font-weight: 600;
        }

        /* Date Display */
        .date-display {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            background: var(--k-white);
            border-radius: 24px;
            color: var(--k-gray-600);
            font-size: 0.65rem;
            border: 1px solid var(--k-gray-200);
            box-shadow: var(--k-shadow-sm);
        }

        .date-display i {
            color: var(--k-orange);
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
            background: var(--k-blue);
            border-color: var(--k-blue);
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
            background: var(--k-white);
            border: 1px solid var(--k-gray-200);
            border-radius: 24px;
            box-shadow: var(--k-shadow-sm);
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
            background: var(--k-blue);
            color: var(--k-white);
        }

        /* ========================================
           CONTENT AREA
           ======================================== */
        .content-area {
            padding: 1.1rem 1.35rem;
        }

        /* Cards */
        .dms-card {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid var(--k-gray-200);
            box-shadow: var(--k-shadow-md);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.875rem;
            margin-bottom: 1.25rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 8px;
            padding: 0.95rem;
            border: 1px solid var(--k-gray-200);
            box-shadow: var(--k-shadow-md);
            transition: all 0.2s;
        }

        .stat-card:hover {
            border-color: var(--k-green);
            transform: translateY(-2px);
            box-shadow: var(--k-shadow-lg);
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
            background: linear-gradient(135deg, var(--k-blue-light), #ffffff);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--k-blue);
        }

        .stat-trend {
            font-size: 0.6rem;
            padding: 0.1rem 0.4rem;
            border-radius: 20px;
            background: var(--k-success-light);
            color: var(--k-success);
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
            border-collapse: separate;
            border-spacing: 0;
        }

        .dms-table th {
            text-align: left;
            padding: 0.6rem;
            color: var(--k-gray-600);
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid var(--k-gray-200);
            background: var(--k-blue-light);
        }

        .dms-table td {
            padding: 0.6rem;
            border-bottom: 1px solid var(--k-gray-200);
            color: var(--k-gray-700);
            font-size: 0.7rem;
        }

        .dms-table tbody tr:hover {
            background: var(--k-blue-light);
        }

        /* Badges */
        .dms-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.6rem;
            font-weight: 600;
            display: inline-block;
        }

        .dms-badge-success {
            background: var(--k-success-light);
            color: var(--k-success);
        }

        .dms-badge-warning {
            background: var(--k-warning-light);
            color: var(--k-warning-dark);
        }

        .dms-badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .dms-badge-info {
            background: var(--k-blue-light);
            color: var(--k-blue);
        }

        /* Buttons */
        .dms-btn {
            padding: 0.35rem 0.9rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.7rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.2s;
        }

        .dms-btn-primary {
            background: linear-gradient(135deg, var(--k-blue-dark), var(--k-blue));
            color: var(--k-white);
            box-shadow: var(--k-shadow-green);
        }

        .dms-btn-primary:hover {
            background: linear-gradient(135deg, var(--k-blue-darker), var(--k-blue-dark));
            transform: translateY(-1px);
        }

        .dms-btn-outline {
            background: transparent;
            border: 1px solid var(--k-gray-300);
            color: var(--k-gray-600);
        }

        .dms-btn-outline:hover {
            border-color: var(--k-blue);
            color: var(--k-blue);
            background: var(--k-blue-light);
        }

        /* Form Controls */
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            background: var(--k-white);
            border: 1px solid var(--k-gray-300);
            border-radius: 8px;
            color: var(--k-gray-800);
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--k-blue);
            box-shadow: 0 0 0 3px var(--k-blue-light);
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

            .brand-robot {
                position: absolute;
                left: 0.65rem;
                top: 0.65rem;
                width: 86px;
            }

            .logo-wrapper {
                padding-left: 5.6rem;
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
                    <div class="logo-text">
                        <h2><span class="brand-blue">DMS KURMIGO</span></h2>
                        <p>Digitalisasi dan Otomasi</p>
                    </div>
                </a>
                <img src="{{ asset('images/brand/kurmigo-robot.png') }}" alt="" class="brand-robot" aria-hidden="true">
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
                                    $activeDeliveriesQuery = \App\Models\Delivery::where('status', '!=', 'completed');
                                    if (Auth::user()?->hasRole('kurir') && !Auth::user()?->hasAnyRole(['super-admin', 'admin', 'manager'])) {
                                        $activeDeliveriesQuery->where('kurir_id', Auth::id());
                                    }
                                    $activeDeliveries = $activeDeliveriesQuery->count();
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
                            <a href="{{ route('stock.index') }}" class="nav-link {{ request()->routeIs('stock.index', 'stock.low-stock', 'stock.show', 'stock.add*', 'stock.reduce*', 'stock.adjustment*') ? 'active' : '' }}">
                                <i class="bi bi-box-seam"></i>
                                <span>{{ __('navigation.stock_management') }}</span>
                                @php
                                    $lowStockCount = \App\Models\Product::lowStock()->count();
                                @endphp
                                @if($lowStockCount > 0)
                                    <span class="nav-badge">{{ $lowStockCount }}</span>
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

                <!-- SECTION: CATALOG -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.catalog') }}</div>
                    <ul style="list-style: none; padding: 0;">
                        <!-- Products -->
                        @can('view products')
                        <li class="nav-item">
                            <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                                <i class="bi bi-box-seam"></i>
                                <span>{{ __('navigation.products') }}</span>
                            </a>
                        </li>
                        @endcan

                        <!-- Units -->
                        @can('view units')
                        <li class="nav-item">
                            <a href="{{ route('units.index') }}" class="nav-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
                                <i class="bi bi-rulers"></i>
                                <span>{{ __('navigation.units') }}</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>

                <!-- SECTION: BUSINESS RELATIONS -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.business_relations') }}</div>
                    <ul style="list-style: none; padding: 0;">
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
                    </ul>
                </div>

                <!-- SECTION: REPORTS -->
                <div class="nav-section">
                    <div class="nav-section-title">{{ __('navigation.reports') }}</div>
                    <ul style="list-style: none; padding: 0;">
                        @can('view sales report')
                        <li class="nav-item">
                            <a href="{{ route('reports.sales') }}" class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                                <i class="bi bi-graph-up"></i>
                                <span>{{ __('navigation.sales_report') }}</span>
                            </a>
                        </li>
                        @endcan
                        @can('view inventory report')
                        <li class="nav-item">
                            <a href="{{ route('reports.inventory') }}" class="nav-link {{ request()->routeIs('reports.inventory') ? 'active' : '' }}">
                                <i class="bi bi-box-seam"></i>
                                <span>{{ __('navigation.inventory_report') }}</span>
                            </a>
                        </li>
                        @endcan
                        @can('view delivery report')
                        <li class="nav-item">
                            <a href="{{ route('reports.delivery') }}" class="nav-link {{ request()->routeIs('reports.delivery') ? 'active' : '' }}">
                                <i class="bi bi-truck"></i>
                                <span>{{ __('navigation.delivery_report') }}</span>
                            </a>
                        </li>
                        @endcan
                        @can('view financial report')
                        <li class="nav-item">
                            <a href="{{ route('reports.financial') }}" class="nav-link {{ request()->routeIs('reports.financial') ? 'active' : '' }}">
                                <i class="bi bi-currency-dollar"></i>
                                <span>{{ __('navigation.financial_report') }}</span>
                            </a>
                        </li>
                        @endcan
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

                    <a href="{{ route('profile.edit') }}" class="user-info-card" title="{{ __('navigation.open_profile') }}" style="text-decoration: none;">
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
                    </a>

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
