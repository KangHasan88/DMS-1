@extends('layouts.sidebar')

@section('page-title', 'Inventory Report')
@section('breadcrumb', 'Reports / Inventory')

@section('content')
<div class="dms-card">
    <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.35rem;">Inventory Report</h3>
    <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-bottom: 1.25rem;">Ringkasan produk, stok, pergerakan barang, dan indikator week-cover ala DMS distribusi.</p>

    @include('reports._filters', ['exportType' => 'inventory', 'principalOptions' => $principalOptions, 'selectedPrincipalId' => $selectedPrincipalId])
    @include('reports._summary', ['items' => [
        ['label' => 'Total Products', 'value' => number_format($summary['total_products']), 'icon' => 'bi-box-seam'],
        ['label' => 'Active Products', 'value' => number_format($summary['active_products']), 'icon' => 'bi-check2-circle'],
        ['label' => 'Stock In', 'value' => number_format($summary['stock_in']), 'icon' => 'bi-arrow-down-circle'],
        ['label' => 'Stock Out', 'value' => number_format($summary['stock_out']), 'icon' => 'bi-arrow-up-circle', 'bg' => '#fee2e2', 'color' => '#dc2626'],
        ['label' => 'Belum Bergerak 30 Hari', 'value' => number_format($summary['slow_moving']), 'icon' => 'bi-hourglass-split', 'bg' => 'var(--k-orange-light)', 'color' => 'var(--k-orange)'],
        ['label' => 'Stok Berlebih', 'value' => number_format($summary['overstock']), 'icon' => 'bi-boxes', 'bg' => 'var(--k-orange-light)', 'color' => 'var(--k-orange)'],
    ]])

    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Principal</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Stock</th>
                    <th>Terjual 30 Hari</th>
                    <th>Week Cover</th>
                    <th>Status</th>
                    <th>Insight</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php($quantity = $product->stock->quantity ?? 0)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->principal?->name ?? '-' }}</td>
                        <td>{{ $product->category ?? '-' }}</td>
                        <td>{{ $product->unit->symbol ?? $product->unit->name ?? '-' }}</td>
                        <td>{{ number_format($quantity) }}</td>
                        <td>{{ number_format($product->sold_last_30_days ?? 0) }}</td>
                        <td>
                            @if(is_null($product->week_cover))
                                -
                            @else
                                {{ number_format($product->week_cover, 1) }} minggu
                            @endif
                        </td>
                        <td>{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $product->inventory_signal['class'] ?? 'secondary' }}">
                                {{ $product->inventory_signal['label'] ?? '-' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align: center; color: var(--k-gray-500);">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $products->links() }}</div>
</div>
@endsection
