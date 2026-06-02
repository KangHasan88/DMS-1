@extends('layouts.sidebar')

@section('page-title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
@php
    $isIndonesian = app()->getLocale() === 'id';
@endphp
<!-- STATISTICS CARDS -->
<div class="stats-grid">
    <!-- Card 1: Total Users -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: var(--k-green-light);">
                <i class="bi bi-people-fill" style="color: var(--k-green);"></i>
            </div>
            <div class="stat-trend">
                <i class="bi bi-arrow-up"></i> 12%
            </div>
        </div>
        <div class="stat-value">{{ number_format($totalUsers ?? 0) }}</div>
        <div class="stat-label">{{ $isIndonesian ? 'Total Pengguna' : 'Total Users' }}</div>
        <small style="color: var(--k-gray-400);">+{{ number_format($newUsersThisMonth ?? 0) }} {{ $isIndonesian ? 'bulan ini' : 'this month' }}</small>
    </div>

    <!-- Card 2: Total Orders -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: var(--k-green-light);">
                <i class="bi bi-box-seam" style="color: var(--k-green);"></i>
            </div>
            <div class="stat-trend">
                <i class="bi bi-arrow-up"></i> 8%
            </div>
        </div>
        <div class="stat-value">{{ number_format($totalOrders ?? 0) }}</div>
        <div class="stat-label">{{ $isIndonesian ? 'Total Pesanan' : 'Total Orders' }}</div>
        <small style="color: var(--k-gray-400);">+{{ number_format($newOrdersThisWeek ?? 0) }} {{ $isIndonesian ? 'minggu ini' : 'this week' }}</small>
    </div>

    <!-- Card 3: Total Revenue -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: var(--k-green-light);">
                <i class="bi bi-currency-dollar" style="color: var(--k-green);"></i>
            </div>
            <div class="stat-trend">
                <i class="bi bi-arrow-up"></i> 15%
            </div>
        </div>
        <div class="stat-value">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</div>
        <div class="stat-label">{{ $isIndonesian ? 'Total Pendapatan' : 'Total Revenue' }}</div>
        <small style="color: var(--k-gray-400);">{{ $isIndonesian ? 'Bulan ini' : 'This month' }}</small>
    </div>

    <!-- Card 4: Active Deliveries -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: var(--k-orange-light);">
                <i class="bi bi-truck" style="color: var(--k-orange);"></i>
            </div>
            <div class="stat-trend down">
                <i class="bi bi-arrow-down"></i> 3%
            </div>
        </div>
        <div class="stat-value">{{ number_format($activeDeliveries ?? 0) }}</div>
        <div class="stat-label">{{ $isIndonesian ? 'Pengiriman Aktif' : 'Active Deliveries' }}</div>
        <small style="color: var(--k-gray-400);">{{ number_format($pendingDeliveries ?? 0) }} {{ $isIndonesian ? 'menunggu' : 'pending' }}</small>
    </div>

    <!-- Card 5: Pending Orders -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #fef3c7;">
                <i class="bi bi-clock" style="color: #f59e0b;"></i>
            </div>
        </div>
        <div class="stat-value">{{ $pendingOrdersCount ?? 0 }}</div>
        <div class="stat-label">{{ $isIndonesian ? 'Pesanan Menunggu' : 'Pending Orders' }}</div>
        <small style="color: var(--k-gray-400);">{{ $isIndonesian ? 'Menunggu pembayaran' : 'Waiting for payment' }}</small>
        @if(($pendingOrdersCount ?? 0) > 0)
            <div style="margin-top: 0.5rem;">
                <a href="{{ route('orders.index', ['status' => 'pending_payment']) }}" style="font-size: 0.6rem; color: var(--k-orange); text-decoration: none;">
                    {{ $isIndonesian ? 'Proses Sekarang' : 'Process Now' }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        @endif
    </div>

    <!-- Card 6: Low Stock Alert -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #fee2e2;">
                <i class="bi bi-exclamation-triangle" style="color: #dc2626;"></i>
            </div>
        </div>
        <div class="stat-value">{{ $lowStockProducts ?? 0 }}</div>
        <div class="stat-label">{{ $isIndonesian ? 'Stok Menipis' : 'Low Stock' }}</div>
        <small style="color: var(--k-gray-400);">{{ $outOfStockProducts ?? 0 }} {{ $isIndonesian ? 'stok habis' : 'out of stock' }}</small>
        @if(($lowStockProducts ?? 0) > 0)
            <div style="margin-top: 0.5rem;">
                <a href="{{ route('stock.low-stock') }}" style="font-size: 0.6rem; color: var(--k-red); text-decoration: none;">
                    {{ $isIndonesian ? 'Lihat Detail' : 'View Detail' }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        @endif
    </div>

    <!-- Card 7: Net Stock Movement -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: var(--k-gray-100);">
                <i class="bi bi-arrow-left-right" style="color: var(--k-gray-600);"></i>
            </div>
        </div>
        <div class="stat-value">
            @php
                $netStock = ($stockInThisMonth ?? 0) - ($stockOutThisMonth ?? 0);
                $netStockClass = $netStock > 0 ? 'var(--k-green)' : ($netStock < 0 ? 'var(--k-red)' : 'var(--k-gray-500)');
            @endphp
            <span style="color: {{ $netStockClass }};">
                @if($netStock > 0)
                    <i class="bi bi-arrow-up" style="font-size: 0.8rem;"></i>
                @elseif($netStock < 0)
                    <i class="bi bi-arrow-down" style="font-size: 0.8rem;"></i>
                @endif
                {{ number_format(abs($netStock)) }}
            </span>
        </div>
        <div class="stat-label">{{ $isIndonesian ? 'Mutasi Stok Bersih' : 'Net Stock Movement' }}</div>
        <small style="color: var(--k-gray-400);">
            {{ $isIndonesian ? 'Masuk' : 'In' }}: {{ number_format($stockInThisMonth ?? 0) }} | {{ $isIndonesian ? 'Keluar' : 'Out' }}: {{ number_format($stockOutThisMonth ?? 0) }}
        </small>
    </div>

    <!-- Card 8: Completed Orders -->
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon" style="background: #dcfce7;">
                <i class="bi bi-check-circle" style="color: #16a34a;"></i>
            </div>
        </div>
        <div class="stat-value">{{ $completedOrdersCount ?? 0 }}</div>
        <div class="stat-label">{{ $isIndonesian ? 'Pesanan Selesai' : 'Completed Orders' }}</div>
        <small style="color: var(--k-gray-400);">{{ $isIndonesian ? 'Bulan ini' : 'This month' }}</small>
        @if(($completedOrdersCount ?? 0) > 0)
            <div style="margin-top: 0.5rem;">
                <a href="{{ route('orders.index', ['status' => 'delivered']) }}" style="font-size: 0.6rem; color: var(--k-green); text-decoration: none;">
                    {{ $isIndonesian ? 'Lihat Semua' : 'View All' }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        @endif
    </div>
</div>

<!-- QUICK LINKS -->
<div class="dms-card" style="margin-bottom: 1.5rem;">
    <div class="dms-toolbar-actions">
        @can('create sales order')
        <a href="{{ route('orders.create') }}" class="dms-btn dms-btn-primary" style="text-decoration: none;">
            <i class="bi bi-plus-circle"></i> {{ $isIndonesian ? 'Pesanan Baru' : 'New Order' }}
        </a>
        @endcan
        <a href="{{ route('deliveries.index') }}" class="dms-btn dms-btn-primary" style="text-decoration: none;">
            <i class="bi bi-truck"></i> {{ $isIndonesian ? 'Lacak Pengiriman' : 'Track Delivery' }}
        </a>
        <button class="dms-btn dms-btn-outline" onclick="alert('Feature coming soon!')">
            <i class="bi bi-file-earmark-spreadsheet"></i> {{ $isIndonesian ? 'Export Laporan' : 'Export Report' }}
        </button>
        <button onclick="openSearchModal()" class="dms-btn dms-btn-outline">
            <i class="bi bi-search"></i> {{ $isIndonesian ? 'Cari Pesanan' : 'Search Orders' }}
        </button>
    </div>
</div>

<!-- Search Orders Modal -->
<div id="searchModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 1.5rem; width: 500px; max-width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-search"></i> {{ $isIndonesian ? 'Cari Pesanan' : 'Search Orders' }}
            </h3>
            <button onclick="closeSearchModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <form id="searchOrderForm" method="GET" action="{{ route('orders.index') }}">
            <div style="margin-bottom: 1rem;">
                <label class="form-label">{{ $isIndonesian ? 'Nomor Pesanan' : 'Order ID' }}</label>
                <input type="text" name="search" class="form-control" placeholder="Contoh: KMG202603260001" style="width: 100%; padding: 0.6rem; border: 1px solid var(--k-gray-300); border-radius: 8px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label class="form-label">{{ $isIndonesian ? 'Nama Pelanggan' : 'Customer Name' }}</label>
                <input type="text" name="customer_name" class="form-control" placeholder="Nama pelanggan" style="width: 100%; padding: 0.6rem; border: 1px solid var(--k-gray-300); border-radius: 8px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label class="form-label">{{ $isIndonesian ? 'Tanggal Pesanan' : 'Order Date' }}</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="date" name="date_from" class="form-control" style="flex: 1; padding: 0.6rem; border: 1px solid var(--k-gray-300); border-radius: 8px;">
                    <span style="align-self: center;">{{ $isIndonesian ? 's/d' : 'to' }}</span>
                    <input type="date" name="date_to" class="form-control" style="flex: 1; padding: 0.6rem; border: 1px solid var(--k-gray-300); border-radius: 8px;">
                </div>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label class="form-label">{{ $isIndonesian ? 'Status Pesanan' : 'Order Status' }}</label>
                <select name="status" class="form-control" style="width: 100%; padding: 0.6rem; border: 1px solid var(--k-gray-300); border-radius: 8px;">
                    <option value="">-- {{ $isIndonesian ? 'Semua Status' : 'All Statuses' }} --</option>
                    <option value="pending_payment">{{ $isIndonesian ? 'Menunggu Pembayaran' : 'Pending Payment' }}</option>
                    <option value="paid">{{ $isIndonesian ? 'Dibayar' : 'Paid' }}</option>
                    <option value="checking_stock">{{ $isIndonesian ? 'Cek Stok' : 'Checking Stock' }}</option>
                    <option value="procuring">{{ $isIndonesian ? 'Belanja' : 'Procuring' }}</option>
                    <option value="repacking">Repacking</option>
                    <option value="ready">{{ $isIndonesian ? 'Siap Kirim' : 'Ready' }}</option>
                    <option value="shipped">{{ $isIndonesian ? 'Dikirim' : 'Shipped' }}</option>
                    <option value="delivered">{{ $isIndonesian ? 'Terkirim' : 'Delivered' }}</option>
                    <option value="cancelled">{{ $isIndonesian ? 'Dibatalkan' : 'Cancelled' }}</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" onclick="closeSearchModal()" class="dms-btn dms-btn-outline">{{ $isIndonesian ? 'Batal' : 'Cancel' }}</button>
                <button type="submit" class="dms-btn dms-btn-primary">{{ $isIndonesian ? 'Cari Pesanan' : 'Search Orders' }}</button>
            </div>
        </form>
    </div>
</div>

<!-- RECENT ORDERS TABLE -->
<div class="dms-card">
    <div class="dms-section-header">
        <h3 class="dms-section-title" style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-clock-history" style="color: var(--k-orange);"></i>
            {{ $isIndonesian ? 'Pesanan Terbaru' : 'Recent Orders' }}
        </h3>
        <a href="{{ route('orders.index') }}" style="color: var(--k-blue); text-decoration: none; font-size: 0.8rem; font-weight: 700;">
            {{ $isIndonesian ? 'Lihat Semua' : 'View All' }} <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>{{ $isIndonesian ? 'No. Pesanan' : 'Order ID' }}</th>
                    <th>{{ $isIndonesian ? 'Pelanggan' : 'Customer' }}</th>
                    <th style="text-align: right;">{{ $isIndonesian ? 'Jumlah' : 'Amount' }}</th>
                    <th>Status</th>
                    <th>{{ $isIndonesian ? 'Tanggal' : 'Date' }}</th>
                    <th style="width: 88px; text-align: center;">{{ $isIndonesian ? 'Aksi' : 'Action' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders ?? [] as $order)
                <tr>
                    <td>
                        <strong style="font-family: monospace; font-size: 0.76rem; font-weight: 700;">{{ $order->order_number ?? '#' . $order->id }}</strong>
                    </td>
                    <td>
                        <div class="dms-identity">
                            <div class="dms-avatar-soft">
                                <i class="bi bi-person"></i>
                            </div>
                            <span class="dms-strong">{{ $order->user->name ?? 'N/A' }}</span>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <span class="dms-money">
                            Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}
                        </span>
                    </td>
                    <td>
                        @php
                            $statusColors = [
                                'pending_payment' => 'warning',
                                'paid' => 'info',
                                'checking_stock' => 'info',
                                'procuring' => 'info',
                                'repacking' => 'info',
                                'ready' => 'success',
                                'shipped' => 'success',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $statusLabels = [
                                'pending_payment' => $isIndonesian ? 'Menunggu' : 'Pending',
                                'paid' => $isIndonesian ? 'Dibayar' : 'Paid',
                                'checking_stock' => $isIndonesian ? 'Cek Stok' : 'Stock Check',
                                'procuring' => $isIndonesian ? 'Belanja' : 'Procuring',
                                'repacking' => 'Repacking',
                                'ready' => $isIndonesian ? 'Siap Kirim' : 'Ready',
                                'shipped' => $isIndonesian ? 'Dikirim' : 'Shipped',
                                'delivered' => $isIndonesian ? 'Terkirim' : 'Delivered',
                                'cancelled' => $isIndonesian ? 'Dibatalkan' : 'Cancelled'
                            ];
                            $color = $statusColors[$order->status] ?? 'info';
                            $label = $statusLabels[$order->status] ?? ucfirst($order->status);
                        @endphp
                        <span class="dms-badge dms-badge-{{ $color }}">
                            {{ $label }}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span>{{ $order->created_at->format('d M Y') }}</span>
                            <span class="dms-muted">{{ $order->created_at->format('H:i') }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="dms-actions">
                            <a href="{{ route('orders.show', $order) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="{{ $isIndonesian ? 'Lihat Detail' : 'View Detail' }}" aria-label="{{ $isIndonesian ? 'Lihat Detail Pesanan' : 'View Order Detail' }}">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="dms-empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>Belum ada {{ $isIndonesian ? 'Pesanan Terbaru' : 'Recent Orders' }}</p>
                        @can('create sales order')
                        <a href="{{ route('orders.create') }}" class="dms-btn dms-btn-primary" style="text-decoration: none;">
                            <i class="bi bi-plus-circle"></i> {{ $isIndonesian ? 'Buat Pesanan Pertama' : 'Create First Order' }}
                        </a>
                        @endcan
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function openSearchModal() {
    document.getElementById('searchModal').style.display = 'flex';
}

function closeSearchModal() {
    document.getElementById('searchModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('searchModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSearchModal();
    }
});
</script>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.875rem;
        margin-bottom: 1.25rem;
    }
    
    .stat-trend {
        font-size: 0.75rem;
        font-weight: 500;
        color: #10b981;
        background: #e6f7e6;
        padding: 0.2rem 0.5rem;
        border-radius: 30px;
    }
    
    .stat-trend.down {
        color: #ef4444;
        background: #fee2e2;
    }
    
    .stat-trend i {
        font-size: 0.65rem;
    }
    
    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            gap: 1rem;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
        }
        
        .dms-card {
            padding: 1rem;
        }
        
        .dms-table th,
        .dms-table td {
            padding: 0.5rem;
        }
    }
</style>
@endsection
