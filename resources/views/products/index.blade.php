@extends('layouts.sidebar')

@section('page-title', 'Product Management')
@section('breadcrumb', 'Products')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Daftar Produk</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">Kelola semua produk KurmiGO</p>
        </div>
        <a href="{{ route('products.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Tambah Produk
        </a>
    </div>

    <!-- Search & Filter -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <form action="{{ route('products.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari nama produk, kategori..." 
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter Category -->
            <select name="category" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('products.index', array_merge(request()->except('category'), ['category' => null])) }}">Semua Kategori</option>
                <option value="{{ route('products.index', array_merge(request()->except('category'), ['category' => 'Sayur'])) }}" {{ request('category') == 'Sayur' ? 'selected' : '' }}>Sayur</option>
                <option value="{{ route('products.index', array_merge(request()->except('category'), ['category' => 'Buah'])) }}" {{ request('category') == 'Buah' ? 'selected' : '' }}>Buah</option>
                <option value="{{ route('products.index', array_merge(request()->except('category'), ['category' => 'Lauk'])) }}" {{ request('category') == 'Lauk' ? 'selected' : '' }}>Lauk</option>
                <option value="{{ route('products.index', array_merge(request()->except('category'), ['category' => 'Bumbu'])) }}" {{ request('category') == 'Bumbu' ? 'selected' : '' }}>Bumbu</option>
                <option value="{{ route('products.index', array_merge(request()->except('category'), ['category' => 'Lainnya'])) }}" {{ request('category') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
            </select>
            
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('products.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('products.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('products.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('products.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('products.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('products.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('products.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Products Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                  <tr>
                    <th style="width: 60px;">#</th>
                    <th>Image</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Unit</th>
                    <th>Harga Jual</th>
                    <th>Harga Beli</th>
                    <th>Status</th>
                    <th style="width: 200px;">Aksi</th>
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
                            <div style="width: 40px; height: 40px; background: var(--k-green-light); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-box-seam" style="color: var(--k-green);"></i>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $product->name }}</div>
                        @if($product->description)
                            <div style="font-size: 0.7rem; color: var(--k-gray-500);">{{ Str::limit($product->description, 50) }}</div>
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
                    <td style="font-weight: 600; color: var(--k-green);">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td style="color: var(--k-gray-500);">Rp {{ number_format($product->base_price ?? 0, 0, ',', '.') }}</td>
                    <td>
                        <div class="status-toggle" data-id="{{ $product->id }}">
                            <span class="dms-badge {{ $product->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                                {{ $product->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('products.show', $product) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Lihat Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('products.price-history', $product) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Lihat History Harga">
                                <i class="bi bi-clock-history"></i>
                            </a>
                            <a href="{{ route('products.edit', $product) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $product->id }})" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            <button onclick="deleteProduct({{ $product->id }}, '{{ $product->name }}')" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem; color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-box-seam" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data produk</p>
                        <a href="{{ route('products.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Produk Pertama
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
            Menampilkan {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} produk
        </div>
        <div>
            {{ $products->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
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