@extends('layouts.sidebar')

@section('page-title', 'Pemasok')
@section('breadcrumb', 'Pemasok')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Pemasok</h3>
            <p class="dms-section-subtitle">Kelola data pemasok, kategori, dan histori pembelian.</p>
        </div>
        @can('create suppliers')
        <a href="{{ route('suppliers.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Tambah Pemasok
        </a>
        @endcan
    </div>

    <!-- Search & Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('suppliers.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nama, telepon, pasar, nomor lapak..." 
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <!-- Filter Category -->
            <select name="category" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('suppliers.index', array_merge(request()->except('category'), ['category' => null])) }}">Semua Kategori</option>
                @foreach($categories as $key => $label)
                    <option value="{{ route('suppliers.index', array_merge(request()->except('category'), ['category' => $key])) }}" {{ request('category') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <!-- Filter Market -->
            <input type="text" name="market" placeholder="Filter Pasar..." 
                   value="{{ request('market') }}" 
                   onchange="window.location.href = this.value ? '{{ route('suppliers.index', array_merge(request()->except('market'), ['market' => '']) ) }}' + encodeURIComponent(this.value) : '{{ route('suppliers.index', array_merge(request()->except('market'), ['market' => null])) }}'"
                   style="padding: 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; width: 150px;">
            
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('suppliers.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('suppliers.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('suppliers.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('suppliers.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('suppliers.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('suppliers.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('suppliers.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Pemasok Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                  <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama Pemasok</th>
                    <th>Kontak</th>
                    <th>Lokasi</th>
                    <th>Kategori</th>
                    <th>Min Order</th>
                    <th>Status</th>
                    <th style="width: 150px;">Aksi</th>
                  </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $index => $supplier)
                  <tr>
                    <td>{{ $suppliers->firstItem() + $index }}</td>
                    <td>
                        <div class="dms-identity">
                            <div class="dms-avatar-soft">
                                <i class="bi bi-shop"></i>
                            </div>
                            <div>
                                <div class="dms-strong">{{ $supplier->name }}</div>
                                @if($supplier->specialty)
                                    <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $supplier->specialty }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem;">{{ $supplier->phone }}</span>
                            @if($supplier->alternate_phone)
                                <span style="font-size: 0.65rem; color: var(--k-gray-500);">Alt: {{ $supplier->alternate_phone }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            @if($supplier->market_name)
                                <span style="font-size: 0.75rem;"><i class="bi bi-building"></i> {{ $supplier->market_name }}</span>
                            @endif
                            @if($supplier->stall_number)
                                <span style="font-size: 0.65rem; color: var(--k-gray-500);">Lapak: {{ $supplier->stall_number }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-info">{{ $supplier->category_label }}</span>
                    </td>
                    <td>
                        @if($supplier->min_order > 0)
                            Rp {{ number_format($supplier->min_order, 0, ',', '.') }}
                        @else
                            <span style="color: var(--k-gray-500);">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="dms-badge {{ $supplier->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                            {{ $supplier->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td>
                        <div class="dms-actions">
                            <a href="{{ route('suppliers.show', $supplier) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit suppliers')
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $supplier->id }})" class="dms-btn dms-btn-outline dms-btn-sm" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            @endcan
                            @can('delete suppliers')
                            <button onclick="deleteSupplier({{ $supplier->id }}, '{{ $supplier->name }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-shop" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data pemasok</p>
                        @can('create suppliers')
                        <a href="{{ route('suppliers.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Pemasok Pertama
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
            Menampilkan {{ $suppliers->firstItem() ?? 0 }} - {{ $suppliers->lastItem() ?? 0 }} dari {{ $suppliers->total() }} pemasok
        </div>
        <div>
            {{ $suppliers->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
@can('delete suppliers')
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endcan

<script>
function toggleStatus(supplierId) {
    if (!confirm('Apakah Anda yakin ingin mengubah status pemasok ini?')) {
        return;
    }
    
    fetch(`/suppliers/${supplierId}/toggle-status`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Gagal mengubah status');
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan');
    });
}

function deleteSupplier(supplierId, supplierName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus pemasok "${supplierName}"?`)) {
        return;
    }
    
    const form = document.getElementById('delete-form');
    form.action = `/suppliers/${supplierId}`;
    form.submit();
}
</script>

@endsection
