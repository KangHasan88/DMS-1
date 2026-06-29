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
    <div class="dms-po-filter-panel">
        <form action="{{ route('purchase-orders.index') }}" method="GET" class="dms-po-filter-grid">
            <div class="dms-po-filter-control dms-po-filter-wide">
                <label class="form-label">Cari PO</label>
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nomor PO, pemasok..."
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
            </div>
            <div class="dms-po-filter-control">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="dms-po-filter-control">
                <label class="form-label">Pemasok</label>
                <select name="supplier_id" class="form-control">
                    <option value="">Semua Pemasok</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="dms-po-filter-control dms-po-filter-small">
                <label class="form-label">Per Halaman</label>
                <select name="per_page" class="form-control">
                    <option value="5" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 data</option>
                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 data</option>
                    <option value="20" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 data</option>
                    <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 data</option>
                </select>
            </div>
            <div class="dms-po-filter-actions">
                <a href="{{ route('purchase-orders.index') }}" class="dms-btn dms-btn-outline">Reset</a>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </div>
        </form>
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

<style>
.dms-po-filter-panel {
    margin-bottom: 1.25rem;
    padding: 1rem;
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    background: var(--k-gray-50);
}

.dms-po-filter-grid {
    display: grid;
    grid-template-columns: minmax(260px, 1.8fr) minmax(160px, 0.7fr) minmax(190px, 0.9fr) minmax(130px, 0.55fr) auto;
    gap: 0.8rem;
    align-items: end;
}

.dms-po-filter-control {
    min-width: 0;
}

.dms-po-filter-panel .form-label {
    margin-bottom: 0.35rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--k-gray-700);
}

.dms-po-filter-panel .form-control {
    width: 100%;
    min-height: 46px;
}

.dms-po-filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.55rem;
    white-space: nowrap;
}

.dms-po-filter-actions .dms-btn {
    min-height: 46px;
}

@media (max-width: 1180px) {
    .dms-po-filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dms-po-filter-wide,
    .dms-po-filter-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 720px) {
    .dms-po-filter-grid {
        grid-template-columns: 1fr;
    }

    .dms-po-filter-actions {
        flex-direction: column-reverse;
    }

    .dms-po-filter-actions .dms-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

@endsection
