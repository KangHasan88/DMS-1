@extends('layouts.sidebar')

@section('page-title', 'Delivery Report')
@section('breadcrumb', 'Reports / Delivery')

@section('content')
<div class="dms-card">
    <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.35rem;">Delivery Report</h3>
    <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-bottom: 1.25rem;">Ringkasan pengiriman dan status kurir.</p>

    @include('reports._filters', ['exportType' => 'delivery'])
    @include('reports._summary', ['items' => [
        ['label' => 'Total Deliveries', 'value' => number_format($summary['total_deliveries']), 'icon' => 'bi-truck'],
        ['label' => 'Completed', 'value' => number_format($summary['completed']), 'icon' => 'bi-check-circle'],
        ['label' => 'In Progress', 'value' => number_format($summary['in_progress']), 'icon' => 'bi-hourglass-split', 'bg' => '#fef3c7', 'color' => '#f59e0b'],
        ['label' => 'Today', 'value' => number_format($summary['today']), 'icon' => 'bi-calendar-event'],
    ]])

    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Kurir</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $delivery)
                    <tr>
                        <td>{{ $delivery->order->order_number ?? '-' }}</td>
                        <td>{{ $delivery->order->user->name ?? '-' }}</td>
                        <td>{{ $delivery->kurir->name ?? '-' }}</td>
                        <td>{{ $delivery->status_label }}</td>
                        <td>{{ $delivery->created_at?->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: var(--k-gray-500);">Belum ada data pengiriman pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $deliveries->links() }}</div>
</div>
@endsection
