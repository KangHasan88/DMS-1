@extends('layouts.sidebar')

@section('page-title', 'Sales Report')
@section('breadcrumb', 'Reports / Sales')

@section('content')
<div class="dms-card">
    <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.35rem;">Sales Report</h3>
    <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-bottom: 1.25rem;">Ringkasan order dan penjualan berdasarkan periode.</p>

    @include('reports._filters', ['exportType' => 'sales'])
    @include('reports._summary', ['items' => [
        ['label' => 'Total Orders', 'value' => number_format($summary['total_orders']), 'icon' => 'bi-receipt'],
        ['label' => 'Delivered', 'value' => number_format($summary['delivered_orders']), 'icon' => 'bi-check-circle'],
        ['label' => 'Gross Sales', 'value' => 'Rp ' . number_format($summary['gross_sales'] ?? 0, 0, ',', '.'), 'icon' => 'bi-cash-stack'],
        ['label' => 'Pending Payment', 'value' => number_format($summary['pending_orders']), 'icon' => 'bi-clock', 'bg' => '#fef3c7', 'color' => '#f59e0b'],
    ]])

    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->user->name ?? '-' }}</td>
                        <td>{{ $order->status_label ?? ucfirst(str_replace('_', ' ', $order->status)) }}</td>
                        <td>Rp {{ number_format($order->grand_total ?? $order->total ?? 0, 0, ',', '.') }}</td>
                        <td>{{ $order->created_at?->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: var(--k-gray-500);">Belum ada data pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $orders->links() }}</div>
</div>
@endsection
