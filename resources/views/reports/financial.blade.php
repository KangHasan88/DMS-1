@extends('layouts.sidebar')

@section('page-title', 'Financial Report')
@section('breadcrumb', 'Reports / Financial')

@section('content')
<div class="dms-card">
    <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.35rem;">Financial Report</h3>
    <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-bottom: 1.25rem;">Ringkasan pendapatan, biaya, pajak, dan metode pembayaran.</p>

    @include('reports._filters', ['exportType' => 'financial'])
    @include('reports._summary', ['items' => [
        ['label' => 'Revenue', 'value' => 'Rp ' . number_format($summary['revenue'] ?? 0, 0, ',', '.'), 'icon' => 'bi-cash-stack'],
        ['label' => 'Discount', 'value' => 'Rp ' . number_format($summary['discount'] ?? 0, 0, ',', '.'), 'icon' => 'bi-percent', 'bg' => '#fee2e2', 'color' => '#dc2626'],
        ['label' => 'Tax', 'value' => 'Rp ' . number_format($summary['tax'] ?? 0, 0, ',', '.'), 'icon' => 'bi-receipt'],
        ['label' => 'Unpaid Orders', 'value' => number_format($summary['unpaid_orders']), 'icon' => 'bi-clock', 'bg' => '#fef3c7', 'color' => '#f59e0b'],
    ]])

    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th>Orders</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byPaymentMethod as $row)
                    <tr>
                        <td>{{ $row->payment_method ? ucfirst($row->payment_method) : 'Unspecified' }}</td>
                        <td>{{ number_format($row->total_orders) }}</td>
                        <td>Rp {{ number_format($row->total_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align: center; color: var(--k-gray-500);">Belum ada transaksi delivered pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
