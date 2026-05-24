@extends('layouts.sidebar')

@section('page-title', 'Master Satuan')
@section('breadcrumb', 'Master / Satuan')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Daftar Satuan</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">Kelola satuan produk (kg, ikat, butir, dll)</p>
        </div>
        <a href="{{ route('units.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Tambah Satuan
        </a>
    </div>

    <!-- Search & Filter -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <form action="{{ route('units.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari nama, kode, simbol..." 
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter Category -->
            <select name="category" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('units.index', array_merge(request()->except('category'), ['category' => null])) }}">Semua Kategori</option>
                @foreach($categories as $cat)
                <option value="{{ route('units.index', array_merge(request()->except('category'), ['category' => $cat])) }}" {{ request('category') == $cat ? 'selected' : '' }}>
                    {{ $cat }}
                </option>
                @endforeach
            </select>
            
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('units.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('units.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('units.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('units.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('units.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('units.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('units.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Units Table -->
    <div style="overflow-x: auto;">
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
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $unit->name }}</div>
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
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('units.show', $unit) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('units.edit', $unit) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $unit->id }})" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            <button onclick="deleteUnit({{ $unit->id }}, '{{ $unit->name }}')" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem; color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                     </td>
                 </tr>
                @empty
                 <tr>
                    <td colspan="9" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-rulers" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data satuan</p>
                        <a href="{{ route('units.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Satuan Pertama
                        </a>
                    </td>
                 </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.9rem; color: var(--k-gray-600);">
            Menampilkan {{ $units->firstItem() ?? 0 }} - {{ $units->lastItem() ?? 0 }} dari {{ $units->total() }} satuan
        </div>
        <div>
            {{ $units->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
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