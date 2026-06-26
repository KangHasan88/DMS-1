@extends('layouts.sidebar')

@section('page-title', 'Pesanan Pembelian')
@section('breadcrumb', 'Pembelian / Pesanan Pembelian')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Pesanan Pembelian</h3>
            <p class="dms-section-subtitle">Kelola PO pemasok dari draft, approval, sampai penerimaan barang.</p>
        </div>
        @can('create purchase order')
        <div class="dms-toolbar-actions">
            <a href="{{ route('purchase-orders.proposed') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-lightbulb"></i>
                Usulan Pembelian
            </a>
            <a href="{{ route('purchase-orders.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i>
                Buat PO Baru
            </a>
        </div>
        @endcan
    </div>

    <!-- Search & Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('purchase-orders.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nomor PO, pemasok..."
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('purchase-orders.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <!-- Filter Pemasok -->
            <select name="supplier_id" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('supplier_id'), ['supplier_id' => null])) }}">Semua Pemasok</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ route('purchase-orders.index', array_merge(request()->except('supplier_id'), ['supplier_id' => $supplier->id])) }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                  <tr>
                    <th>No. PO</th>
                    <th>Pemasok</th>
                    <th>Tanggal PO</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Dibuat Oleh</th>
                    <th style="width: 150px;">Aksi</th>
                  </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrders as $po)
                  <tr>
                    <td><strong>{{ $po->po_number }}</strong></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-shop" style="color: var(--k-green);"></i>
                            <span>{{ $po->supplier->name }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem;">{{ $po->order_date->format('d M Y') }}</span>
                            @if($po->expected_delivery_date)
                                <span style="font-size: 0.65rem; color: var(--k-gray-500);">
                                    <i class="bi bi-calendar"></i> {{ $po->expected_delivery_date->format('d M Y') }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="dms-money">
                        Rp {{ number_format($po->total, 0, ',', '.') }}
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $po->status_color }}">
                            {{ $po->status_label }}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-person-circle" style="color: var(--k-gray-500);"></i>
                            <span style="font-size: 0.75rem;">{{ $po->createdBy->name ?? '-' }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="dms-actions">
                            <a href="{{ route('purchase-orders.show', $po) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit purchase order')
                            @if($po->status === 'draft' && !$po->isApprovalPending())
                            <a href="{{ route('purchase-orders.edit', $po) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @endcan
                            @can('delete purchase order')
                            @if($po->status == 'draft' && !$po->isApprovalPending())
                            <button onclick="deletePO({{ $po->id }}, '{{ $po->po_number }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                            @endcan
                        </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-receipt" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data Purchase Order</p>
                        @can('create purchase order')
                        <a href="{{ route('purchase-orders.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Buat PO Pertama
                        </a>
                        @endcan
                    </td>
                  </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $purchaseOrders->firstItem() ?? 0 }} - {{ $purchaseOrders->lastItem() ?? 0 }} dari {{ $purchaseOrders->total() }} PO
        </div>
        <div>
            {{ $purchaseOrders->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
@can('delete purchase order')
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endcan

<script>
function deletePO(poId, poNumber) {
    if (!confirm(`Apakah Anda yakin ingin menghapus PO "${poNumber}"?`)) {
        return;
    }
    
    const form = document.getElementById('delete-form');
    form.action = `/purchase-orders/${poId}`;
    form.submit();
}
</script>

@endsection
