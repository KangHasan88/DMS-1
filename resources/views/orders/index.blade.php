@extends('layouts.sidebar')

@section('page-title', 'Pesanan Penjualan')
@section('breadcrumb', 'Operasional / Pesanan Penjualan')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Pesanan</h3>
            <p class="dms-section-subtitle">Kelola pesanan pelanggan dari pembayaran, pemenuhan, sampai pengiriman.</p>
        </div>
        @can('create sales order')
        <a href="{{ route('orders.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Order
        </a>
        @endcan
    </div>

    <!-- Search Form -->
    <div class="dms-toolbar">
        <form action="{{ route('orders.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nomor order..." 
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        
        <div class="dms-toolbar-actions">
            <!-- Advanced Search Toggle -->
            <button type="button" onclick="toggleAdvancedSearch()" class="dms-btn dms-btn-outline">
                <i class="bi bi-sliders2"></i> Advanced Search
            </button>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('orders.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('orders.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('orders.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('orders.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Advanced Search Form -->
    <div id="advancedSearch" style="display: none; margin-bottom: 1.5rem; padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
        <form action="{{ route('orders.index') }}" method="GET" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
            <input type="hidden" name="search" value="{{ request('search') }}">
            
            <div>
                <label class="form-label">Nama Pelanggan</label>
                <input type="text" name="customer_name" class="form-control" value="{{ request('customer_name') }}" placeholder="Nama pelanggan">
            </div>
            
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">-- Semua Status --</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            
            <div>
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            
            <div>
                <label class="form-label">Sumber Order</label>
                <select name="order_source" class="form-control">
                    <option value="">-- Semua --</option>
                    @foreach($orderSources as $key => $label)
                        <option value="{{ $key }}" {{ request('order_source') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="form-label">Mode Pemenuhan</label>
                <select name="fulfillment_type" class="form-control">
                    <option value="">-- Semua --</option>
                    @foreach($fulfillmentTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('fulfillment_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div style="grid-column: span 2; display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
                <a href="{{ route('orders.index') }}" class="dms-btn dms-btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Order</th>
                    <th>Pelanggan</th>
                    <th>Tanggal</th>
                    <th style="text-align: right;">Total</th>
                    <th>Sumber</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi</th>
                 </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>
                        <strong style="font-family: monospace; font-size: 0.75rem;">{{ $order->order_number }}</strong>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span class="dms-strong">{{ $order->user->name ?? '-' }}</span>
                            <span class="dms-muted">{{ $order->user->phone ?? '-' }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem;">{{ $order->created_at->format('d M Y') }}</span>
                            <span class="dms-muted">{{ $order->created_at->format('H:i') }}</span>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <span class="dms-money">
                            Rp {{ number_format($order->total, 0, ',', '.') }}
                        </span>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $order->order_source == 'app' ? 'success' : 'info' }}" style="font-size: 0.6rem;">
                            {{ $order->order_source == 'app' ? 'Aplikasi' : 'Admin' }}
                        </span>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $order->fulfillment_type == 'stock' ? 'warning' : 'info' }}" style="font-size: 0.6rem;">
                            {{ $order->fulfillment_type == 'stock' ? 'Stock' : 'BLJ' }}
                        </span>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $order->status_color }}" style="font-size: 0.6rem;">
                            {{ $order->status_label }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <div class="dms-actions">
                            <a href="{{ route('orders.show', $order) }}" class="dms-btn dms-btn-outline dms-btn-sm" style="text-decoration: none;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit sales order')
                            @if($order->canUpdateStatus())
                            <a href="{{ route('orders.edit', $order) }}" class="dms-btn dms-btn-outline dms-btn-sm" style="text-decoration: none;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @endcan
                            @can('delete sales order')
                            @if($order->status == 'pending_payment')
                            <button onclick="deleteOrder({{ $order->id }}, '{{ $order->order_number }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="dms-empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>Tidak ada data order</p>
                        @can('create sales order')
                        <a href="{{ route('orders.create') }}" class="dms-btn dms-btn-primary">
                            <i class="bi bi-plus-circle"></i> Buat Order Pertama
                        </a>
                        @endcan
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} order
        </div>
        <div>
            {{ $orders->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
@can('delete sales order')
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endcan

<script>
function toggleAdvancedSearch() {
    const advSearch = document.getElementById('advancedSearch');
    advSearch.style.display = advSearch.style.display === 'none' ? 'block' : 'none';
}

function deleteOrder(orderId, orderNumber) {
    if (!confirm(`Apakah Anda yakin ingin menghapus order "${orderNumber}"?`)) {
        return;
    }
    
    const form = document.getElementById('delete-form');
    form.action = `/orders/${orderId}`;
    form.submit();
}
</script>

@endsection
