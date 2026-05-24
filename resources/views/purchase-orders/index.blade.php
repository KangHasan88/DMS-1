@extends('layouts.sidebar')

@section('page-title', 'Purchase Order Management')
@section('breadcrumb', 'Purchase Orders')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Daftar Purchase Order</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">Kelola semua pembelian ke supplier</p>
        </div>
        @can('create purchase order')
        <a href="{{ route('purchase-orders.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Buat PO Baru
        </a>
        @endcan
    </div>

    <!-- Search & Filter -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <form action="{{ route('purchase-orders.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari nomor PO, supplier..." 
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('purchase-orders.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <!-- Filter Supplier -->
            <select name="supplier_id" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('supplier_id'), ['supplier_id' => null])) }}">Semua Supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ route('purchase-orders.index', array_merge(request()->except('supplier_id'), ['supplier_id' => $supplier->id])) }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('purchase-orders.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                  <tr>
                    <th>No. PO</th>
                    <th>Supplier</th>
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
                    <td style="font-weight: 600; color: var(--k-green);">
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
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('purchase-orders.show', $po) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit purchase order')
                            @if(in_array($po->status, ['draft', 'pending']))
                            <a href="{{ route('purchase-orders.edit', $po) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @endcan
                            @can('delete purchase order')
                            @if($po->status == 'draft')
                            <button onclick="deletePO({{ $po->id }}, '{{ $po->po_number }}')" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem; color: var(--k-red);" title="Hapus">
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
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.9rem; color: var(--k-gray-600);">
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
    font-size: 0.9rem;
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
</style>
@endsection
