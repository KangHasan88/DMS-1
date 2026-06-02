@extends('layouts.sidebar')

@section('page-title', 'Master Satuan')
@section('breadcrumb', 'Master / Satuan')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Satuan</h3>
            <p class="dms-section-subtitle">Kelola satuan produk (kg, ikat, butir, dll)</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            @can('create units')
            <button type="button" class="dms-btn dms-btn-outline" onclick="toggleInlineCategoryForm('unit-category-panel')">
                <i class="bi bi-tags"></i>
                Tambah Kategori
            </button>
            <a href="{{ route('units.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i>
                Tambah Satuan
            </a>
            @endcan
        </div>
    </div>

    @can('create units')
    <div id="unit-category-panel" style="display: none; margin-bottom: 1rem; padding: 1rem; border: 1px solid var(--k-gray-200); border-radius: 8px; background: var(--k-gray-50);">
        <form action="{{ route('unit-categories.store') }}" method="POST" style="display: flex; gap: 0.75rem; align-items: end; flex-wrap: wrap;">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ route('units.index') }}">
            <div class="form-group" style="margin: 0; flex: 1 1 260px;">
                <label class="form-label">Nama Kategori</label>
                <input type="text" name="name" class="form-control" required placeholder="Contoh: Kemasan, Berat, Volume">
            </div>
            <div class="form-group" style="margin: 0; flex: 0 1 160px;">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" class="form-control" min="0" value="0">
            </div>
            <input type="hidden" name="is_active" value="1">
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan
            </button>
            <a href="{{ route('unit-categories.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-list-ul"></i> Lihat Daftar
            </a>
        </form>
    </div>
    @endcan

    <!-- Search & Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('units.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nama, kode, simbol..." 
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <!-- Filter Category -->
            <select name="category" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('units.index', array_merge(request()->except('category'), ['category' => null])) }}">Semua Kategori</option>
                @foreach($categories as $cat)
                <option value="{{ route('units.index', array_merge(request()->except('category'), ['category' => $cat])) }}" {{ request('category') == $cat ? 'selected' : '' }}>
                    {{ $cat }}
                </option>
                @endforeach
            </select>
            
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('units.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('units.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('units.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('units.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('units.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('units.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('units.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Units Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                 <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama</th>
                    <th>Kode</th>
                    <th>Simbol</th>
                    <th>Kategori</th>
                    <th>Produk</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th style="width: 150px;">Aksi</th>
                 </tr>
            </thead>
            <tbody>
                @forelse($units as $index => $unit)
                 <tr>
                     <td>{{ $units->firstItem() + $index }}</td>
                     <td>
                        <div class="dms-strong">{{ $unit->name }}</div>
                        @if($unit->description)
                            <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ Str::limit($unit->description, 40) }}</div>
                        @endif
                     </td>
                     <td><code style="background: var(--k-gray-100); padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $unit->code }}</code></td>
                     <td><strong>{{ $unit->symbol ?? '-' }}</strong></td>
                     <td><span class="dms-badge dms-badge-info">{{ $unit->category ?? '-' }}</span></td>
                     <td>
                        @if($unit->products_count > 0)
                            <span class="dms-badge dms-badge-warning">{{ $unit->products_count }} produk</span>
                        @else
                            <span style="color: var(--k-gray-500);">-</span>
                        @endif
                     </td>
                     <td>{{ $unit->sort_order }}</td>
                     <td>
                        <span class="dms-badge {{ $unit->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                            {{ $unit->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                     </td>
                     <td>
                        <div class="dms-actions">
                            <a href="{{ route('units.show', $unit) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit units')
                            <a href="{{ route('units.edit', $unit) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $unit->id }})" class="dms-btn dms-btn-outline dms-btn-sm" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            @endcan
                            @can('delete units')
                            <button onclick="deleteUnit({{ $unit->id }}, '{{ $unit->name }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                     </td>
                 </tr>
                @empty
                 <tr>
                    <td colspan="9" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-rulers" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data satuan</p>
                        @can('create units')
                        <a href="{{ route('units.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Satuan Pertama
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
            Menampilkan {{ $units->firstItem() ?? 0 }} - {{ $units->lastItem() ?? 0 }} dari {{ $units->total() }} satuan
        </div>
        <div>
            {{ $units->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
@can('delete units')
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endcan

<script>
function toggleInlineCategoryForm(panelId) {
    const panel = document.getElementById(panelId);
    if (!panel) {
        return;
    }

    const willOpen = panel.style.display === 'none' || panel.style.display === '';
    panel.style.display = willOpen ? 'block' : 'none';

    if (willOpen) {
        panel.querySelector('input[name="name"]')?.focus();
    }
}

function toggleStatus(unitId) {
    if (!confirm('Apakah Anda yakin ingin mengubah status satuan ini?')) {
        return;
    }
    
    fetch(`/units/${unitId}/toggle-status`, {
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

function deleteUnit(unitId, unitName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus satuan "${unitName}"?`)) {
        return;
    }
    
    const form = document.getElementById('delete-form');
    form.action = `/units/${unitId}`;
    form.submit();
}
</script>

@endsection
