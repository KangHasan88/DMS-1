@extends('layouts.sidebar')

@section('page-title', 'Pasar Pemasok')
@section('breadcrumb', 'Pemasok / Pasar Pemasok')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Pasar Pemasok</h3>
            <p class="dms-section-subtitle">Kelola daftar pasar atau lokasi pemasok.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('suppliers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
            @can('create suppliers')
            <a href="{{ route('supplier-markets.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i>
                Tambah Pasar
            </a>
            @endcan
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('supplier-markets.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari pasar pemasok..."
                       value="{{ request('search') }}"
                       class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('supplier-markets.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('supplier-markets.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('supplier-markets.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>

            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('supplier-markets.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('supplier-markets.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('supplier-markets.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('supplier-markets.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama Pasar</th>
                    <th>Deskripsi</th>
                    <th>Pemasok</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($markets as $index => $market)
                <tr>
                    <td>{{ $markets->firstItem() + $index }}</td>
                    <td>
                        <div class="dms-strong">{{ $market->name }}</div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">{{ $market->slug }}</div>
                    </td>
                    <td>{{ $market->description ? Str::limit($market->description, 60) : '-' }}</td>
                    <td>
                        @php($suppliersCount = $market->suppliersCount())
                        @if($suppliersCount > 0)
                            <span class="dms-badge dms-badge-warning">{{ $suppliersCount }} pemasok</span>
                        @else
                            <span style="color: var(--k-gray-500);">-</span>
                        @endif
                    </td>
                    <td>{{ $market->sort_order }}</td>
                    <td>
                        <span class="dms-badge {{ $market->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                            {{ $market->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td>
                        <div class="dms-actions">
                            @can('edit suppliers')
                            <a href="{{ route('supplier-markets.edit', $market) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $market->id }})" class="dms-btn dms-btn-outline dms-btn-sm" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            @endcan
                            @can('delete suppliers')
                            <button onclick="deleteMarket({{ $market->id }}, '{{ $market->name }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-building" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada pasar pemasok</p>
                        @can('create suppliers')
                        <a href="{{ route('supplier-markets.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Pasar Pertama
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $markets->firstItem() ?? 0 }} - {{ $markets->lastItem() ?? 0 }} dari {{ $markets->total() }} pasar
        </div>
        <div>
            {{ $markets->withQueryString()->links() }}
        </div>
    </div>
</div>

@can('delete suppliers')
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endcan

<script>
function toggleStatus(marketId) {
    if (!confirm('Apakah Anda yakin ingin mengubah status pasar ini?')) {
        return;
    }

    fetch(`/supplier-markets/${marketId}/toggle-status`, {
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
    .catch(() => alert('Terjadi kesalahan'));
}

function deleteMarket(marketId, marketName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus pasar "${marketName}"?`)) {
        return;
    }

    const form = document.getElementById('delete-form');
    form.action = `/supplier-markets/${marketId}`;
    form.submit();
}
</script>
@endsection
