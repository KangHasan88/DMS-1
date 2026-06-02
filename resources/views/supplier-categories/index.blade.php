@extends('layouts.sidebar')

@section('page-title', 'Kategori Pemasok')
@section('breadcrumb', 'Relasi Bisnis / Kategori Pemasok')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Kategori Pemasok</h3>
            <p class="dms-section-subtitle">Kelola kategori pemasok sesuai kebutuhan pembelian.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('suppliers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
            @can('create suppliers')
            <a href="{{ route('supplier-categories.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i>
                Tambah Kategori
            </a>
            @endcan
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('supplier-categories.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari kategori pemasok..."
                       value="{{ request('search') }}"
                       class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('supplier-categories.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('supplier-categories.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('supplier-categories.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>

            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('supplier-categories.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('supplier-categories.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('supplier-categories.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('supplier-categories.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama Kategori</th>
                    <th>Kode</th>
                    <th>Deskripsi</th>
                    <th>Pemasok</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $index => $category)
                <tr>
                    <td>{{ $categories->firstItem() + $index }}</td>
                    <td><div class="dms-strong">{{ $category->name }}</div></td>
                    <td><code style="background: var(--k-gray-100); padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $category->code }}</code></td>
                    <td>{{ $category->description ? Str::limit($category->description, 60) : '-' }}</td>
                    <td>
                        @php($suppliersCount = $category->suppliersCount())
                        @if($suppliersCount > 0)
                            <span class="dms-badge dms-badge-warning">{{ $suppliersCount }} pemasok</span>
                        @else
                            <span style="color: var(--k-gray-500);">-</span>
                        @endif
                    </td>
                    <td>{{ $category->sort_order }}</td>
                    <td>
                        <span class="dms-badge {{ $category->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                            {{ $category->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td>
                        <div class="dms-actions">
                            @can('edit suppliers')
                            <a href="{{ route('supplier-categories.edit', $category) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $category->id }})" class="dms-btn dms-btn-outline dms-btn-sm" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            @endcan
                            @can('delete suppliers')
                            <button onclick="deleteCategory({{ $category->id }}, '{{ $category->name }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-bookmark" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada kategori pemasok</p>
                        @can('create suppliers')
                        <a href="{{ route('supplier-categories.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Kategori Pertama
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
            Menampilkan {{ $categories->firstItem() ?? 0 }} - {{ $categories->lastItem() ?? 0 }} dari {{ $categories->total() }} kategori
        </div>
        <div>
            {{ $categories->withQueryString()->links() }}
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
function toggleStatus(categoryId) {
    if (!confirm('Apakah Anda yakin ingin mengubah status kategori ini?')) {
        return;
    }

    fetch(`/supplier-categories/${categoryId}/toggle-status`, {
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

function deleteCategory(categoryId, categoryName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus kategori "${categoryName}"?`)) {
        return;
    }

    const form = document.getElementById('delete-form');
    form.action = `/supplier-categories/${categoryId}`;
    form.submit();
}
</script>
@endsection
