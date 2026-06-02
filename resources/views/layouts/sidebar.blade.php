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
            background: linear-gradient(180deg, #f8fafc 0%, #f3f7fc 100%);
            color: #1e293b;
            font-size: 14px;
            line-height: 1.5;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
        }

        /* Color Palette - DMS SaaS Professional */
        :root {
            --k-white: #ffffff;
            --k-white-off: #f6f8fb;
            --k-gray-50: #f8fafc;
            --k-gray-100: #eef3f8;
            --k-gray-200: #d8e2ee;
            --k-gray-300: #c6d3e1;
            --k-gray-400: #8fa0b6;
            --k-gray-500: #64748b;
            --k-gray-600: #475569;
            --k-gray-700: #334155;
            --k-gray-800: #1e293b;
            --k-gray-900: #0f172a;
            --k-black: #000000;
            
            --k-blue: #123b7a;
            --k-blue-dark: #0b2759;
            --k-blue-darker: #071a3d;
            --k-blue-light: #edf4ff;
            --k-blue-mid: #1e4f9c;
            --k-blue-accent: #2563eb;
            --k-orange: #f97316;
            --k-orange-dark: #c2410c;
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
            
            --k-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
            --k-shadow-md: 0 10px 28px rgba(15, 23, 42, 0.06);
            --k-shadow-lg: 0 18px 44px rgba(15, 23, 42, 0.10);
            --k-shadow-green: 0 8px 18px rgba(18, 59, 122, 0.12);

            --k-font-xs: 0.72rem;
            --k-font-sm: 0.78rem;
            --k-font-md: 0.82rem;
            --k-font-lg: 1.02rem;
            --k-font-page-title: 1.12rem;
            --k-font-stat: 1.3rem;
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
            background: rgba(255, 255, 255, 0.98);
            color: var(--k-gray-800);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            border-right: 1px solid var(--k-gray-200);
            box-shadow: 8px 0 28px rgba(15, 23, 42, 0.04);
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
            padding: 1.1rem 0.95rem 0.65rem;
            margin-bottom: 0.2rem;
            min-height: 72px;
            position: relative;
            overflow: visible;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            text-decoration: none;
            min-height: 44px;
            padding-left: 0;
        }

        .logo-text h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--k-blue-darker);
            margin: 0;
            line-height: 1;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .logo-text h2 .brand-blue {
            color: var(--k-blue);
        }

        .logo-text h2 .brand-orange {
            color: var(--k-blue);
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
            font-size: 0.62rem;
            text-transform: uppercase;
            color: #718198;
            font-weight: 700;
            padding: 0 0.6rem;
            margin-bottom: 0.4rem;
            letter-spacing: 0.04em;
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
            font-weight: 600;
        }

        .nav-link i {
            font-size: 1rem;
            width: 18px;
            color: var(--k-gray-500);
        }

        .nav-link span {
            font-size: var(--k-font-sm);
            font-weight: 600;
        }

        .nav-link:hover {
            background: var(--k-blue-light);
            color: var(--k-blue);
        }

        .nav-link:hover i {
            color: var(--k-blue);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--k-blue-darker), var(--k-blue));
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
            font-size: 0.62rem;
            font-weight: 700;
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
                linear-gradient(180deg, rgba(237, 244, 255, 0.72) 0, rgba(246, 248, 251, 0) 11rem),
                var(--k-white-off);
        }

        /* ========================================
           TOP BAR - Compact
           ======================================== */
        .top-bar {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            padding: 0.85rem 1.55rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--k-gray-200);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        }

        .page-title h1 {
            font-size: var(--k-font-page-title);
            font-weight: 700;
            color: var(--k-blue-darker);
            margin: 0;
            line-height: 1.22;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: #63758c;
            font-size: var(--k-font-xs);
            margin-top: 0.2rem;
        }

        .breadcrumb a {
            color: var(--k-gray-700);
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
            font-size: var(--k-font-xs);
        }

        .user-detail .role {
            font-size: 0.62rem;
            color: var(--k-gray-500);
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .role-badge {
            background: var(--k-orange-light);
            color: var(--k-orange-dark);
            padding: 0.05rem 0.3rem;
            border-radius: 20px;
            font-size: 0.58rem;
            font-weight: 700;
        }

        /* Date Display */
        .date-display {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            background: var(--k-white);
            border-radius: 24px;
            color: #4a5b70;
            font-size: var(--k-font-xs);
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
            font-size: var(--k-font-xs);
            font-weight: 600;
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
            font-size: 0.68rem;
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
            padding: 1.25rem 1.45rem 1.5rem;
        }

        /* Cards */
        .dms-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 8px;
            padding: 1.15rem;
            border: 1px solid var(--k-gray-200);
            box-shadow: var(--k-shadow-md);
        }

        .dms-section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.1rem;
        }

        .dms-section-title {
            font-size: var(--k-font-lg);
            font-weight: 700;
            line-height: 1.25;
            color: var(--k-blue-darker);
            margin: 0;
        }

        .dms-section-subtitle {
            font-size: var(--k-font-sm);
            color: #5d7088;
            margin: 0.25rem 0 0;
        }

        .dms-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: #fbfdff;
            border: 1px solid var(--k-gray-200);
            border-radius: 8px;
        }

        .dms-search-form {
            display: flex;
            gap: 0.5rem;
            flex: 1 1 320px;
            min-width: min(100%, 280px);
        }

        .dms-search-field {
            position: relative;
            flex: 1;
        }

        .dms-search-field i {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--k-gray-400);
            pointer-events: none;
        }

        .dms-search-field .form-control {
            padding-left: 2.25rem;
        }

        .dms-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .dms-toolbar-actions .form-control {
            width: auto;
            min-width: 180px;
        }

        .dms-search-form .dms-btn {
            min-width: 64px;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(18, 59, 122, 0.10);
        }

        .dms-table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e3ebf5;
            border-radius: 8px;
            background: var(--k-white);
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
            font-size: var(--k-font-stat);
            font-weight: 700;
            color: var(--k-gray-900);
            margin-bottom: 0.1rem;
            line-height: 1.15;
        }

        .stat-label {
            color: var(--k-gray-600);
            font-size: var(--k-font-xs);
            font-weight: 600;
            line-height: 1.35;
        }

        /* Table */
        .dms-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .dms-table th {
            text-align: left;
            padding: 0.64rem 0.78rem;
            color: #5b6f86;
            font-size: var(--k-font-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            border-bottom: 1px solid #e3ebf5;
            background: #f8fafc;
        }

        .dms-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .dms-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .dms-identity {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .dms-avatar-soft {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: var(--k-blue-light);
            color: var(--k-blue);
        }

        .dms-strong {
            font-weight: 500;
            color: #0f172a;
        }

        .dms-muted {
            color: var(--k-gray-500);
            font-size: 0.65rem;
        }

        .dms-money {
            font-weight: 600;
            color: #17345f;
            white-space: nowrap;
        }

        .dms-empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--k-gray-500);
        }

        .dms-empty-state i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            margin-bottom: 0.8rem;
            border-radius: 14px;
            background: var(--k-blue-light);
            color: var(--k-blue);
            font-size: 1.7rem;
        }

        .dms-empty-state p {
            margin: 0 0 1rem;
            font-size: 0.82rem;
            color: #52657d;
        }

        .dms-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .dms-pagination-summary {
            font-size: var(--k-font-md);
            color: #52657d;
        }

        .pagination {
            display: flex;
            gap: 0.4rem;
            list-style: none;
            padding: 0;
            margin: 0;
            flex-wrap: wrap;
        }

        .pagination li {
            display: inline-block;
        }

        .pagination li a,
        .pagination li span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 0.5rem;
            border: 1px solid var(--k-gray-300);
            border-radius: 8px;
            color: var(--k-gray-600);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .pagination li.active span {
            background: var(--k-blue);
            color: var(--k-white);
            border-color: var(--k-blue);
        }

        .pagination li a:hover {
            background: var(--k-blue-light);
            border-color: var(--k-blue);
            color: var(--k-blue);
        }

        .pagination .disabled span {
            background: var(--k-gray-100);
            color: var(--k-gray-400);
            border-color: var(--k-gray-200);
            cursor: not-allowed;
        }

        .dms-table td {
            padding: 0.62rem 0.78rem;
            border-bottom: 1px solid #edf2f7;
            color: #475569;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .dms-table tbody tr:hover {
            background: #fbfdff;
        }

        /* Badges */
        .dms-badge {
            padding: 0.22rem 0.62rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-block;
            line-height: 1.2;
        }

        .dms-badge-success {
            background: #ecfdf3;
            color: #15803d;
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
            background: #f1f5f9;
            color: #1e3a5f;
        }

        .dms-badge-primary {
            background: var(--k-blue);
            color: var(--k-white);
        }

        .dms-badge-secondary {
            background: var(--k-gray-100);
            color: var(--k-gray-600);
        }

        /* Buttons */
        .dms-btn {
            min-height: 36px;
            padding: 0.42rem 0.9rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: var(--k-font-sm);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.2s;
        }

        .dms-btn-sm {
            min-width: 32px;
            width: 32px;
            height: 32px;
            padding: 0;
            font-size: 0.65rem;
            justify-content: center;
            flex: 0 0 32px;
        }

        .dms-btn-primary {
            background: linear-gradient(135deg, var(--k-blue-darker), var(--k-blue));
            color: var(--k-white);
            box-shadow: var(--k-shadow-green);
        }

        .dms-btn-primary:hover {
            background: linear-gradient(135deg, var(--k-blue-darker), var(--k-blue-dark));
            transform: translateY(-1px);
        }

        .dms-btn-outline {
            background: var(--k-white);
            border: 1px solid #d6e1ef;
            color: #52657d;
        }

        .dms-btn-outline:hover {
            border-color: var(--k-blue);
            color: var(--k-blue);
            background: var(--k-blue-light);
        }

        /* Form Controls */
        .form-control {
            width: 100%;
            min-height: 40px;
            padding: 0.55rem 0.75rem;
            background: var(--k-white);
            border: 1px solid var(--k-gray-300);
            border-radius: 8px;
            color: var(--k-gray-900);
            font-size: var(--k-font-md);
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
            font-size: var(--k-font-sm);
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .dms-form-header {
            margin-bottom: 1.25rem;
        }

        .dms-form-title {
            font-size: var(--k-font-lg);
            font-weight: 700;
            color: var(--k-blue-darker);
            line-height: 1.25;
            margin: 0;
        }

        .dms-form-subtitle {
            font-size: var(--k-font-sm);
            color: #5d7088;
            margin: 0.25rem 0 0;
        }

        .dms-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .dms-form-grid-wide {
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            gap: 1.5rem;
            align-items: start;
        }

        .dms-form-span-2 {
            grid-column: span 2;
        }

        .dms-form-section {
            margin-bottom: 1.25rem;
        }

        .dms-form-section-title {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--k-blue-darker);
            margin: 0 0 0.75rem;
            padding-bottom: 0.45rem;
            border-bottom: 1px solid var(--k-gray-200);
        }

        .dms-form-section-title i {
            color: var(--k-orange);
        }

        .dms-form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--k-gray-200);
            flex-wrap: wrap;
        }

        .dms-form-help,
        .dms-help {
            display: block;
            margin-top: 0.3rem;
            color: var(--k-gray-500);
            font-size: 0.68rem;
        }

        .dms-form-error,
        .dms-error {
            display: block;
            margin-top: 0.3rem;
            color: var(--k-red);
            font-size: 0.68rem;
            font-weight: 600;
        }

        .dms-required {
            color: var(--k-red);
            font-weight: 800;
        }

        .dms-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: var(--k-gray-700);
            font-size: 0.8rem;
        }

        .dms-upload-panel {
            text-align: center;
            padding: 1.25rem;
            background: var(--k-gray-50);
            border-radius: 8px;
            border: 1px solid var(--k-gray-200);
        }

        .dms-preview-box {
            width: 200px;
            height: 200px;
            margin: 0 auto 1rem;
            border-radius: 8px;
            overflow: hidden;
            border: 2px dashed var(--k-gray-300);
            background: var(--k-white);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dms-money-field {
            position: relative;
        }

        .dms-money-field > span {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--k-gray-500);
            font-size: 0.75rem;
        }

        .dms-money-field .form-control {
            padding-left: 2.35rem;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
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

            .logo-wrapper {
                padding-left: 0;
            }

            .top-bar-right .date-display,
            .top-bar-right .user-detail {
                display: none;
            }

            .dms-section-header,
            .dms-toolbar,
            .dms-search-form,
            .dms-toolbar-actions,
            .dms-pagination,
            .dms-form-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .dms-form-grid,
            .dms-form-grid-wide {
                grid-template-columns: 1fr;
            }

            .dms-form-span-2 {
                grid-column: span 1;
            }

            .dms-btn,
            .dms-search-form .dms-btn,
            .dms-toolbar-actions .dms-btn,
            .dms-toolbar-actions .form-control {
                width: 100%;
                justify-content: center;
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
                        <h2><span class="brand-blue">DMS</span></h2>
                    </div>
                </a>
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
                            <a href="{{ route('purchase-orders.proposed') }}" class="nav-link {{ request()->routeIs('purchase-orders.proposed') ? 'active' : '' }}">
                                <i class="bi bi-lightbulb"></i>
                                <span>{{ __('navigation.proposed_purchase_orders') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('purchase-orders.index') }}" class="nav-link {{ request()->routeIs('purchase-orders.*') && ! request()->routeIs('purchase-orders.proposed') ? 'active' : '' }}">
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

                        <!-- Stock Opname -->
                        @can('manage warehouse')
                        <li class="nav-item">
                            <a href="{{ route('stock-opnames.index') }}" class="nav-link {{ request()->routeIs('stock-opnames.*') ? 'active' : '' }}">
                                <i class="bi bi-clipboard-check"></i>
                                <span>{{ __('navigation.stock_opnames') }}</span>
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
