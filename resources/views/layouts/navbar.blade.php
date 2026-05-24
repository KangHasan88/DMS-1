<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'DMS')) - Distributor Management System</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
        }

        /* Navbar Styles */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0 2rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1000;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
        }

        .logo {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo i {
            color: white;
            font-size: 1.8rem;
        }

        .brand-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
        }

        .brand-text p {
            font-size: 0.75rem;
            color: #64748b;
        }

        /* Menu Navigasi */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            color: #64748b;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-link i {
            font-size: 1.2rem;
        }

        .nav-link:hover {
            background: #f1f5f9;
            color: #2563eb;
        }

        .nav-link.active {
            background: #e0f2fe;
            color: #0369a1;
        }

        /* User Section */
        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .notification {
            position: relative;
            cursor: pointer;
        }

        .notification i {
            font-size: 1.3rem;
            color: #64748b;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.6rem;
            padding: 0.15rem 0.35rem;
            border-radius: 5px;
        }

        .user-dropdown {
            position: relative;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .user-btn:hover {
            background: #f1f5f9;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: #dbeafe;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-avatar i {
            font-size: 1.2rem;
            color: #2563eb;
        }

        .user-info {
            text-align: left;
        }

        .user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
        }

        .user-email {
            font-size: 0.7rem;
            color: #64748b;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            min-width: 200px;
            display: none;
            z-index: 1001;
            border: 1px solid #e2e8f0;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background: #f1f5f9;
        }

        .dropdown-item i {
            color: #64748b;
            font-size: 1rem;
        }

        .dropdown-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 0.5rem 0;
        }

        /* Main Content */
        .main-content {
            margin-top: 70px;
            padding: 2rem;
            min-height: calc(100vh - 70px);
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 12px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-title h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .page-title p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
        }

        /* Content Card */
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: start;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <!-- Brand -->
        <a href="{{ route('dashboard') }}" class="navbar-brand">
            <div class="logo">
                <i class="bi bi-boxes"></i>
            </div>
            <div class="brand-text">
                <h1>DMS</h1>
                <p>Distributor Management System</p>
            </div>
        </a>

        <!-- Navigation Menu -->
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="alert('Menu Distributors (Coming Soon)')">
                    <i class="bi bi-people"></i>
                    <span>Distributors</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="alert('Menu Products (Coming Soon)')">
                    <i class="bi bi-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="alert('Menu Orders (Coming Soon)')">
                    <i class="bi bi-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="alert('Menu Inventory (Coming Soon)')">
                    <i class="bi bi-boxes"></i>
                    <span>Inventory</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" onclick="alert('Menu Reports (Coming Soon)')">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>

        <!-- Right Section -->
        <div class="nav-right">
            <!-- Notifications -->
            <div class="notification">
                <i class="bi bi-bell"></i>
                <span class="notification-badge">3</span>
            </div>

            <!-- User Dropdown -->
            <div class="user-dropdown">
                <div class="user-btn" onclick="toggleDropdown()">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->name ?? 'Admin User' }}</div>
                        <div class="user-email">{{ Auth::user()->email ?? 'admin@example.com' }}</div>
                    </div>
                    <i class="bi bi-chevron-down" style="font-size: 0.8rem; color: #64748b;"></i>
                </div>

                <!-- Dropdown Menu -->
                <div class="dropdown-menu" id="userDropdown">
                    <a href="#" class="dropdown-item" onclick="alert('Profile Page (Coming Soon)')">
                        <i class="bi bi-person"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="#" class="dropdown-item" onclick="alert('Settings Page (Coming Soon)')">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item" style="width: 100%; border: none; background: none; cursor: pointer;">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <h2>@yield('page-title', 'Dashboard')</h2>
                <p>@yield('page-description', 'Welcome to Distributor Management System')</p>
            </div>
            <div class="breadcrumb">
                <a href="{{ route('dashboard') }}">Home</a>
                <i class="bi bi-chevron-right"></i>
                <span>@yield('breadcrumb', 'Dashboard')</span>
            </div>
        </div>

        <!-- Content -->
        <div class="content-card">
            @yield('content')
        </div>
    </main>

    <script>
        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.user-btn') && !event.target.matches('.user-btn *')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>