@extends('layouts.sidebar')

@section('page-title', 'Delivery Report')
@section('breadcrumb', 'Reports / Delivery')

@section('content')
<div class="dms-card">
    <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.35rem;">Delivery Report</h3>
    <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-bottom: 1.25rem;">Ringkasan pengiriman dan status kurir.</p>

    @include('reports._filters', [
        'exportType' => 'delivery',
        'filters' => $filters,
        'statusOptions' => $statusOptions,
        'searchLabel' => 'Cari Pengiriman',
        'searchPlaceholder' => 'No order, customer, kurir...',
    ])
    @include('reports._summary', ['items' => [
        ['label' => 'Total Deliveries', 'value' => number_format($summary['total_deliveries']), 'icon' => 'bi-truck'],
        ['label' => 'Completed', 'value' => number_format($summary['completed']), 'icon' => 'bi-check-circle'],
        ['label' => 'In Progress', 'value' => number_format($summary['in_progress']), 'icon' => 'bi-hourglass-split', 'bg' => '#fef3c7', 'color' => '#f59e0b'],
        ['label' => 'Today', 'value' => number_format($summary['today']), 'icon' => 'bi-calendar-event'],
    ]])

    <div class="dms-table-wrap" style="box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);">
        <table class="dms-table" style="min-width: 860px;">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Kurir</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: right;">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $delivery)
                    <tr>
                        <td>
                            <div class="dms-strong">{{ $delivery->order->order_number ?? '-' }}</div>
                            <small style="color: var(--k-gray-500);">Pengiriman</small>
                        </td>
                        <td>{{ $delivery->order->user->name ?? '-' }}</td>
                        <td>{{ $delivery->kurir->name ?? '-' }}</td>
                        <td style="text-align: center;">
                            <span class="dms-badge dms-badge-secondary">{{ $delivery->status_label }}</span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">{{ $delivery->created_at?->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="dms-empty-state" style="padding: 2.5rem 1rem;">
                                <i class="bi bi-truck"></i>
                                <p>Belum ada data pengiriman pada filter ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $deliveries->firstItem() ?? 0 }} - {{ $deliveries->lastItem() ?? 0 }} dari {{ $deliveries->total() }} pengiriman
        </div>
        {{ $deliveries->links() }}
    </div>
</div>
@endsection
