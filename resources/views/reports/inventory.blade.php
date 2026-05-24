@extends('layouts.sidebar')

@section('page-title', 'Inventory Report')
@section('breadcrumb', 'Reports / Inventory')

@section('content')
<div class="dms-card">
    <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.35rem;">Inventory Report</h3>
    <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-bottom: 1.25rem;">Ringkasan produk, stok, dan pergerakan barang.</p>

    @include('reports._filters', ['exportType' => 'inventory'])
    @include('reports._summary', ['items' => [
        ['label' => 'Total Products', 'value' => number_format($summary['total_products']), 'icon' => 'bi-box-seam'],
        ['label' => 'Active Products', 'value' => number_format($summary['active_products']), 'icon' => 'bi-check2-circle'],
        ['label' => 'Stock In', 'value' => number_format($summary['stock_in']), 'icon' => 'bi-arrow-down-circle'],
        ['label' => 'Stock Out', 'value' => number_format($summary['stock_out']), 'icon' => 'bi-arrow-up-circle', 'bg' => '#fee2e2', 'color' => '#dc2626'],
    ]])

    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php($quantity = $product->stock->quantity ?? 0)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category ?? '-' }}</td>
                        <td>{{ $product->unit->symbol ?? $product->unit->name ?? '-' }}</td>
                        <td>{{ number_format($quantity) }}</td>
                        <td>{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: var(--k-gray-500);">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $products->links() }}</div>
</div>
@endsection
