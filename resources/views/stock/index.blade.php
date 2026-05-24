@extends('layouts.sidebar')

@section('page-title', 'Stock Management')
@section('breadcrumb', 'Stock')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Daftar Stok Produk</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">Kelola stok produk di gudang</p>
        </div>
        <a href="{{ route('stock.low-stock') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-exclamation-triangle"></i> Low Stock Alert
        </a>
    </div>

    <!-- Search & Filter -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <form action="{{ route('stock.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari produk..." 
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter Stock Status -->
            <select name="stock_status" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('stock.index', array_merge(request()->except('stock_status'), ['stock_status' => null])) }}">Semua Stok</option>
                <option value="{{ route('stock.index', array_merge(request()->except('stock_status'), ['stock_status' => 'in_stock'])) }}" {{ request('stock_status') == 'in_stock' ? 'selected' : '' }}>Tersedia</option>
                <option value="{{ route('stock.index', array_merge(request()->except('stock_status'), ['stock_status' => 'low_stock'])) }}" {{ request('stock_status') == 'low_stock' ? 'selected' : '' }}>Stok Menipis</option>
                <option value="{{ route('stock.index', array_merge(request()->except('stock_status'), ['stock_status' => 'out_of_stock'])) }}" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>Stok Habis</option>
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('stock.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('stock.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('stock.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('stock.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Stock Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                 <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th>Stok Saat Ini</th>
                    <th>Min Stok</th>
                    <th>Max Stok</th>
                    <th>Status</th>
                    <th style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                @php
                    $stock = $product->stock;
                    $quantity = $stock ? $stock->quantity : 0;
                    $minStock = $stock ? $stock->min_stock : 0;
                    $isLowStock = $stock && $stock->quantity <= $stock->min_stock && $stock->quantity > 0;
                    $isOutOfStock = $quantity == 0;
                @endphp
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                            @else
                                <div style="width: 40px; height: 40px; background: var(--k-green-light); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-box-seam" style="color: var(--k-green);"></i>
                                </div>
                            @endif
                            <div>
                                <div style="font-weight: 600; color: var(--k-gray-800);">{{ $product->name }}</div>
                                <div style="font-size: 0.65rem; color: var(--k-gray-500);">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="dms-badge dms-badge-info">{{ $product->category ?? '-' }}</span></td>
                    <td>{{ $product->unit->name ?? '-' }}</td>
                    <td style="font-weight: 600; color: {{ $isLowStock ? 'var(--k-orange)' : ($isOutOfStock ? 'var(--k-red)' : 'var(--k-green)') }}">
                        {{ number_format($quantity) }}
                    </td>
                    <td>{{ number_format($minStock) }}</td>
                    <td>{{ $stock ? number_format($stock->max_stock) : '-' }}</td>
                    <td>
                        @if($isOutOfStock)
                            <span class="dms-badge dms-badge-danger">Stok Habis</span>
                        @elseif($isLowStock)
                            <span class="dms-badge dms-badge-warning">Stok Menipis</span>
                        @else
                            <span class="dms-badge dms-badge-success">Tersedia</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('stock.show', $product) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Detail Stok">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('stock.add-form', $product) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Tambah Stok">
                                <i class="bi bi-plus-circle"></i>
                            </a>
                            <a href="{{ route('stock.reduce-form', $product) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Kurangi Stok">
                                <i class="bi bi-dash-circle"></i>
                            </a>
                            <a href="{{ route('stock.adjustment-form', $product) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Penyesuaian Stok">
                                <i class="bi bi-sliders2"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-box-seam" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data produk</p>
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