@extends('layouts.sidebar')

@section('page-title', 'Sales Report')
@section('breadcrumb', 'Reports / Sales')

@section('content')
<div class="dms-card">
    <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.35rem;">Sales Report</h3>
    <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-bottom: 1.25rem;">Ringkasan order dan penjualan berdasarkan periode.</p>

    @include('reports._filters', [
        'exportType' => 'sales',
        'principalOptions' => $principalOptions,
        'selectedPrincipalId' => $selectedPrincipalId,
        'filters' => $filters,
        'statusOptions' => $statusOptions,
        'searchLabel' => 'Cari Order',
        'searchPlaceholder' => 'No order, customer, email...',
    ])
    @include('reports._summary', ['items' => [
        ['label' => 'Total Orders', 'value' => number_format($summary['total_orders']), 'icon' => 'bi-receipt'],
        ['label' => 'Delivered', 'value' => number_format($summary['delivered_orders']), 'icon' => 'bi-check-circle'],
        ['label' => 'Gross Sales', 'value' => 'Rp ' . number_format($summary['gross_sales'] ?? 0, 0, ',', '.'), 'icon' => 'bi-cash-stack'],
        ['label' => 'Pending Payment', 'value' => number_format($summary['pending_orders']), 'icon' => 'bi-clock', 'bg' => '#fef3c7', 'color' => '#f59e0b'],
    ]])

    <div class="dms-table-wrap" style="box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);">
        <table class="dms-table" style="min-width: 860px;">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: right;">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <div class="dms-strong">{{ $order->order_number }}</div>
                            <small style="color: var(--k-gray-500);">Order penjualan</small>
                        </td>
                        <td>{{ $order->user->name ?? '-' }}</td>
                        <td style="text-align: center;">
                            <span class="dms-badge dms-badge-secondary">{{ $order->status_label ?? ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                        </td>
                        <td class="dms-money" style="text-align: right;">Rp {{ number_format($order->grand_total ?? $order->total ?? 0, 0, ',', '.') }}</td>
                        <td style="text-align: right; white-space: nowrap;">{{ $order->created_at?->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="dms-empty-state" style="padding: 2.5rem 1rem;">
                                <i class="bi bi-receipt"></i>
                                <p>Belum ada data pada filter ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} order
        </div>
        {{ $orders->links() }}
    </div>
</div>
@endsection
