@extends('layouts.sidebar')

@section('page-title', 'Tipe Pelanggan')
@section('breadcrumb', 'Pelanggan / Tipe Pelanggan')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Tipe Pelanggan</h3>
            <p class="dms-section-subtitle">Kelola tipe pelanggan sesuai segmentasi bisnis.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('customers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
            @can('create customers')
            <a href="{{ route('customer-types.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i>
                Tambah Tipe
            </a>
            @endcan
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('customer-types.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari tipe pelanggan..."
                       value="{{ request('search') }}"
                       class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('customer-types.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('customer-types.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('customer-types.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>

            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('customer-types.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('customer-types.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('customer-types.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('customer-types.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama Tipe</th>
                    <th>Kode</th>
                    <th>Deskripsi</th>
                    <th>Pelanggan</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $index => $type)
                <tr>
                    <td>{{ $types->firstItem() + $index }}</td>
                    <td><div class="dms-strong">{{ $type->name }}</div></td>
                    <td><code style="background: var(--k-gray-100); padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $type->code }}</code></td>
                    <td>{{ $type->description ? Str::limit($type->description, 60) : '-' }}</td>
                    <td>
                        @php($customersCount = $type->customersCount())
                        @if($customersCount > 0)
                            <span class="dms-badge dms-badge-warning">{{ $customersCount }} pelanggan</span>
                        @else
                            <span style="color: var(--k-gray-500);">-</span>
                        @endif
                    </td>
                    <td>{{ $type->sort_order }}</td>
                    <td>
                        <span class="dms-badge {{ $type->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                            {{ $type->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td>
                        <div class="dms-actions">
                            @can('edit customers')
                            <a href="{{ route('customer-types.edit', $type) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $type->id }})" class="dms-btn dms-btn-outline dms-btn-sm" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            @endcan
                            @can('delete customers')
                            <button onclick="deleteType({{ $type->id }}, '{{ $type->name }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-tags" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada tipe pelanggan</p>
                        @can('create customers')
                        <a href="{{ route('customer-types.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Tipe Pertama
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
            Menampilkan {{ $types->firstItem() ?? 0 }} - {{ $types->lastItem() ?? 0 }} dari {{ $types->total() }} tipe
        </div>
        <div>
            {{ $types->withQueryString()->links() }}
        </div>
    </div>
</div>

@can('delete customers')
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endcan

<script>
function toggleStatus(typeId) {
    if (!confirm('Apakah Anda yakin ingin mengubah status tipe ini?')) {
        return;
    }

    fetch(`/customer-types/${typeId}/toggle-status`, {
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

function deleteType(typeId, typeName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus tipe "${typeName}"?`)) {
        return;
    }

    const form = document.getElementById('delete-form');
    form.action = `/customer-types/${typeId}`;
    form.submit();
}
</script>
@endsection
