@extends('layouts.sidebar')

@section('page-title', 'Produk')
@section('breadcrumb', 'Katalog / Produk')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Produk</h3>
            <p class="dms-section-subtitle">Kelola katalog produk, harga, satuan, dan status produk.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            @can('create categories')
            <button type="button" class="dms-btn dms-btn-outline" onclick="toggleInlineCategoryForm('product-category-panel')">
                <i class="bi bi-tags"></i>
                Tambah Kategori
            </button>
            @endcan
            @can('create products')
            <a href="{{ route('products.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i>
                Tambah Produk
            </a>
            @endcan
        </div>
    </div>

    @can('create categories')
    <div id="product-category-panel" style="display: none; margin-bottom: 1rem; padding: 1rem; border: 1px solid var(--k-gray-200); border-radius: 8px; background: var(--k-gray-50);">
        <form action="{{ route('product-categories.store') }}" method="POST" style="display: flex; gap: 0.75rem; align-items: end; flex-wrap: wrap;">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ route('products.index') }}">
            <div class="form-group" style="margin: 0; flex: 1 1 260px;">
                <label class="form-label">Nama Kategori</label>
                <input type="text" name="name" class="form-control" required placeholder="Contoh: Sayur Organik">
            </div>
            <div class="form-group" style="margin: 0; flex: 0 1 160px;">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" class="form-control" min="0" value="0">
            </div>
            <input type="hidden" name="is_active" value="1">
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan
            </button>
            <a href="{{ route('product-categories.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-list-ul"></i> Lihat Daftar
            </a>
        </form>
    </div>
    @endcan

    <!-- Search & Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('products.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nama produk, principal, kategori..." 
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <select name="principal_id" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('products.index', array_merge(request()->except('principal_id'), ['principal_id' => null])) }}">Semua Principal</option>
                @foreach($principals as $principal)
                    <option value="{{ route('products.index', array_merge(request()->except('principal_id'), ['principal_id' => $principal->id])) }}" {{ (string) request('principal_id') === (string) $principal->id ? 'selected' : '' }}>
                        {{ $principal->name }}
                    </option>
                @endforeach
            </select>

            <!-- Filter Category -->
            <select name="category" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('products.index', array_merge(request()->except('category'), ['category' => null])) }}">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ route('products.index', array_merge(request()->except('category'), ['category' => $category])) }}" {{ request('category') == $category ? 'selected' : '' }}>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
            
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('products.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('products.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('products.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('products.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('products.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('products.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('products.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Products Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                  <tr>
                    <th style="width: 60px;">#</th>
                    <th>Image</th>
                    <th>Nama Produk</th>
                    <th>Principal</th>
                    <th>Kategori</th>
                    <th>Unit</th>
                    <th>Harga Jual</th>
                    <th>Harga Beli</th>
                    <th>Status</th>
                    <th style="width: 220px;">Aksi</th>
                  </tr>
            </thead>
            <tbody>
                @forelse($products as $index => $product)
                  <tr>
                    <td>{{ $products->firstItem() + $index }}</td>
                    <td>
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                        @else
                            <div class="dms-avatar-soft">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="dms-strong">{{ $product->name }}</div>
                        @if($product->description)
                            <div style="font-size: 0.7rem; color: var(--k-gray-500);">{{ Str::limit($product->description, 50) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($product->principal)
                            <span class="dms-badge dms-badge-info">{{ $product->principal->name }}</span>
                        @else
                            <span style="color: var(--k-gray-400);">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-info">{{ $product->category ?? '-' }}</span>
                    </td>
                    <td>
                        @if($product->unit)
                            {{ $product->unit->name }}
                            @if($product->unit->symbol)
                                <span style="color: var(--k-gray-500); font-size: 0.65rem;">({{ $product->unit->symbol }})</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="dms-money">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td style="color: var(--k-gray-500);">Rp {{ number_format($product->base_price ?? 0, 0, ',', '.') }}</td>
                    <td>
                        <div class="status-toggle" data-id="{{ $product->id }}">
                            <span class="dms-badge {{ $product->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                                {{ $product->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="dms-actions">
                            <a href="{{ route('products.show', $product) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Lihat Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('products.price-history', $product) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Lihat History Harga">
                                <i class="bi bi-clock-history"></i>
                            </a>
                            @can('edit products')
                            <a href="{{ route('products.edit', $product) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $product->id }})" class="dms-btn dms-btn-outline dms-btn-sm" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            @endcan
                            @can('delete products')
                            <button onclick="deleteProduct({{ $product->id }}, '{{ $product->name }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-box-seam" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data produk</p>
                        @can('create products')
                        <a href="{{ route('products.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Produk Pertama
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
            Menampilkan {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} produk
        </div>
        <div>
            {{ $products->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
@can('delete products')
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

function toggleStatus(productId) {
    if (!confirm('Apakah Anda yakin ingin mengubah status produk ini?')) {
        return;
    }
    
    fetch(`/products/${productId}/toggle-status`, {
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

function deleteProduct(productId, productName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus produk "${productName}"?`)) {
        return;
    }
    
    const form = document.getElementById('delete-form');
    form.action = `/products/${productId}`;
    form.submit();
}
</script>

@endsection
