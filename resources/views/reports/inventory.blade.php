@extends('layouts.sidebar')

@section('page-title', 'Inventory Report')
@section('breadcrumb', 'Reports / Inventory')

@section('content')
<div class="dms-card">
    <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.35rem;">Inventory Report</h3>
    <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-bottom: 1.25rem;">Ringkasan produk, stok, pergerakan barang, dan indikator week-cover ala DMS distribusi.</p>

    @include('reports._filters', [
        'exportType' => 'inventory',
        'principalOptions' => $principalOptions,
        'selectedPrincipalId' => $selectedPrincipalId,
        'warehouseOptions' => $warehouseOptions,
        'selectedWarehouseId' => $selectedWarehouseId,
        'categoryOptions' => $categoryOptions,
        'insightOptions' => $insightOptions,
        'filters' => $filters,
    ])
    @include('reports._summary', ['items' => [
        ['label' => 'Total Products', 'value' => number_format($summary['total_products']), 'icon' => 'bi-box-seam'],
        ['label' => 'Active Products', 'value' => number_format($summary['active_products']), 'icon' => 'bi-check2-circle'],
        ['label' => 'Stock In', 'value' => number_format($summary['stock_in']), 'icon' => 'bi-arrow-down-circle'],
        ['label' => 'Stock Out', 'value' => number_format($summary['stock_out']), 'icon' => 'bi-arrow-up-circle', 'bg' => '#fee2e2', 'color' => '#dc2626'],
        ['label' => 'Belum Bergerak 30 Hari', 'value' => number_format($summary['slow_moving']), 'icon' => 'bi-hourglass-split', 'bg' => 'var(--k-orange-light)', 'color' => 'var(--k-orange)'],
        ['label' => 'Stok Berlebih', 'value' => number_format($summary['overstock']), 'icon' => 'bi-boxes', 'bg' => 'var(--k-orange-light)', 'color' => 'var(--k-orange)'],
    ]])

    <div class="dms-table-wrap" style="box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);">
        <table class="dms-table" style="min-width: 1040px;">
            <thead>
                <tr>
                    <th style="width: 23%;">Product</th>
                    <th style="width: 13%;">Principal</th>
                    <th style="width: 15%;">Category</th>
                    <th style="width: 8%;">Unit</th>
                    <th style="width: 8%; text-align: right;">Stock</th>
                    <th style="width: 11%; text-align: right;">Terjual 30 Hari</th>
                    <th style="width: 10%; text-align: right;">Week Cover</th>
                    <th style="width: 7%; text-align: center;">Status</th>
                    <th style="width: 12%;">Insight</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php($quantity = $product->report_stock_quantity ?? 0)
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--k-gray-800);">{{ $product->name }}</div>
                            <div style="font-size: var(--k-font-xs); color: var(--k-gray-500);">SKU: {{ $product->sku ?: '-' }}</div>
                        </td>
                        <td style="font-weight: 600; color: var(--k-gray-700);">{{ $product->principal?->name ?? '-' }}</td>
                        <td>{{ $product->category ?? '-' }}</td>
                        <td style="text-transform: lowercase;">{{ $product->unit->symbol ?? $product->unit->name ?? '-' }}</td>
                        <td style="text-align: right; font-weight: 700; color: var(--k-gray-900);">{{ number_format($quantity) }}</td>
                        <td style="text-align: right;">{{ number_format($product->sold_last_30_days ?? 0) }}</td>
                        <td style="text-align: right; white-space: nowrap;">
                            @if(is_null($product->week_cover))
                                -
                            @else
                                {{ number_format($product->week_cover, 1) }} minggu
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span class="dms-badge dms-badge-{{ $product->is_active ? 'success' : 'secondary' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $product->inventory_signal['class'] ?? 'secondary' }}">
                                {{ $product->inventory_signal['label'] ?? '-' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="dms-empty-state" style="padding: 2.5rem 1rem;">
                                <i class="bi bi-box-seam"></i>
                                <p>Belum ada produk pada filter ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} produk
        </div>
        {{ $products->links() }}
    </div>
</div>
@endsection
