@extends('layouts.sidebar')

@section('page-title', 'Order Management')
@section('breadcrumb', 'Orders')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Daftar Order</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">Kelola semua order KurmiGO</p>
        </div>
        @can('create sales order')
        <a href="{{ route('orders.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Order
        </a>
        @endcan
    </div>

    <!-- Search Form -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 2; min-width: 250px;">
            <form action="{{ route('orders.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari nomor order..." 
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Advanced Search Toggle -->
            <button type="button" onclick="toggleAdvancedSearch()" class="dms-btn dms-btn-outline">
                <i class="bi bi-sliders2"></i> Advanced Search
            </button>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
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
    <div style="overflow-x: auto;">
        <table class="dms-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">No. Order</th>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Pelanggan</th>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Tanggal</th>
                    <th style="padding: 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Total</th>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Sumber</th>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Mode</th>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Status</th>
                    <th style="padding: 0.75rem; text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Aksi</th>
                 </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr style="border-bottom: 1px solid var(--k-gray-200);">
                    <td style="padding: 0.75rem;">
                        <strong style="font-family: monospace; font-size: 0.75rem;">{{ $order->order_number }}</strong>
                    </td>
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem; font-weight: 500;">{{ $order->user->name ?? '-' }}</span>
                            <span style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $order->user->phone ?? '-' }}</span>
                        </div>
                    </td>
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem;">{{ $order->created_at->format('d M Y') }}</span>
                            <span style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $order->created_at->format('H:i') }}</span>
                        </div>
                    </td>
                    <td style="padding: 0.75rem; text-align: right;">
                        <span style="font-weight: 600; font-size: 0.75rem; color: var(--k-green);">
                            Rp {{ number_format($order->total, 0, ',', '.') }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem;">
                        <span class="dms-badge dms-badge-{{ $order->order_source == 'app' ? 'success' : 'info' }}" style="font-size: 0.6rem;">
                            {{ $order->order_source == 'app' ? 'Aplikasi' : 'Admin' }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem;">
                        <span class="dms-badge dms-badge-{{ $order->fulfillment_type == 'stock' ? 'warning' : 'info' }}" style="font-size: 0.6rem;">
                            {{ $order->fulfillment_type == 'stock' ? 'Stock' : 'JIT' }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem;">
                        <span class="dms-badge dms-badge-{{ $order->status_color }}" style="font-size: 0.6rem;">
                            {{ $order->status_label }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <a href="{{ route('orders.show', $order) }}" class="dms-btn dms-btn-outline" style="padding: 0.25rem 0.7rem; font-size: 0.65rem; text-decoration: none;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit sales order')
                            @if($order->canUpdateStatus())
                            <a href="{{ route('orders.edit', $order) }}" class="dms-btn dms-btn-outline" style="padding: 0.25rem 0.7rem; font-size: 0.65rem; text-decoration: none;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @endcan
                            @can('delete sales order')
                            @if($order->status == 'pending_payment')
                            <button onclick="deleteOrder({{ $order->id }}, '{{ $order->order_number }}')" class="dms-btn dms-btn-outline" style="padding: 0.25rem 0.7rem; font-size: 0.65rem; color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data order</p>
                        @can('create sales order')
                        <a href="{{ route('orders.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Buat Order Pertama
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.9rem; color: var(--k-gray-600);">
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

<style>
.pagination {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}
.pagination li {
    display: inline-block;
}
.pagination li a, .pagination li span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.5rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 8px;
    color: var(--k-gray-600);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.2s;
}
.pagination li.active span {
    background: var(--k-green);
    color: white;
    border-color: var(--k-green);
}
.pagination li a:hover {
    background: var(--k-gray-100);
    border-color: var(--k-green);
}
.pagination .disabled span {
    background: var(--k-gray-100);
    color: var(--k-gray-400);
    border-color: var(--k-gray-200);
    cursor: not-allowed;
}
.form-label {
    display: block;
    margin-bottom: 0.3rem;
    color: var(--k-gray-700);
    font-size: 0.7rem;
    font-weight: 500;
}
.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 6px;
    font-size: 0.8rem;
    transition: all 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: var(--k-green);
    box-shadow: 0 0 0 2px var(--k-green-light);
}
.dms-table th, .dms-table td {
    vertical-align: middle;
}
</style>
@endsection
