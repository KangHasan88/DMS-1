@extends('layouts.sidebar')

@section('page-title', 'Low Stock Alert')
@section('breadcrumb', 'Stock / Low Stock')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-exclamation-triangle" style="color: var(--k-orange);"></i>
                Low Stock Alert
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">
                Produk dengan stok menipis (stok = minimal stok)
            </p>
        </div>
        <a href="{{ route('stock.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali ke Stock
        </a>
    </div>

    @if($products->count() > 0)
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th>Stok Saat Ini</th>
                    <th>Min Stok</th>
                    <th>Kebutuhan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </thead>
            </thead>
            <tbody>
                @foreach($products as $product)
                @php
                    $stock = $product->stock;
                    $quantity = $stock ? $stock->quantity : 0;
                    $minStock = $stock ? $stock->min_stock : 0;
                    $need = $minStock - $quantity;
                @endphp
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                            @else
                                <div style="width: 40px; height: 40px; background: var(--k-orange-light); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-box-seam" style="color: var(--k-orange);"></i>
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
                    <td style="font-weight: 600; color: var(--k-orange);">{{ number_format($quantity) }}</td>
                    <td>{{ number_format($minStock) }}</td>
                    <td style="font-weight: 600; color: var(--k-red);">{{ number_format($need) }}</td>
                    <td>
                        @if($quantity == 0)
                            <span class="dms-badge dms-badge-danger">Stok Habis</span>
                        @else
                            <span class="dms-badge dms-badge-warning">Stok Menipis</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('stock.add-form', $product) }}" class="dms-btn dms-btn-primary" style="padding: 0.4rem 0.8rem;" title="Tambah Stok">
                                <i class="bi bi-plus-circle"></i> Tambah
                            </a>
                            <a href="{{ route('stock.show', $product) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div style="text-align: center; padding: 3rem;">
        <i class="bi bi-check-circle" style="font-size: 3rem; color: var(--k-green);"></i>
        <p style="margin-top: 1rem; color: var(--k-gray-500);">Semua produk memiliki stok yang cukup</p>
    </div>
    @endif
</div>

<style>
.dms-table th, .dms-table td {
    vertical-align: middle;
}
</style>
@endsection