@extends('layouts.sidebar')

@section('page-title', 'Riwayat Pesanan Pelanggan')
@section('breadcrumb', 'Pelanggan / Riwayat Pesanan')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-clock-history" style="color: var(--k-green);"></i>
                Riwayat Order
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Pelanggan: <strong>{{ $customer->name }}</strong> ({{ $customer->phone }})
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('customers.show', $customer) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali ke Detail
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="padding: 1rem; background: var(--k-green-light); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Total Order</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--k-green);">{{ number_format($orders->total()) }}</div>
        </div>
        <div style="padding: 1rem; background: var(--k-gray-100); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Total Belanja</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--k-green);">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</div>
        </div>
        <div style="padding: 1rem; background: var(--k-gray-100); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Terakhir Order</div>
            <div style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800);">
                @if($customer->last_order_at)
                    {{ $customer->last_order_at->format('d M Y') }}
                @else
                    -
                @endif
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Order</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Metode Bayar</th>
                    <th>Aksi</th>
                </thead>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td><strong>{{ $order->order_number }}</strong></td>
                    <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                    <td>Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                    <td>
                        @php
                            $statusColors = [
                                'pending' => 'warning',
                                'paid' => 'info',
                                'shopping' => 'info',
                                'repacking' => 'info',
                                'ready_for_delivery' => 'success',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $statusLabels = [
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'shopping' => 'Shopping',
                                'repacking' => 'Repacking',
                                'ready_for_delivery' => 'Ready',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled'
                            ];
                        @endphp
                        <span class="dms-badge dms-badge-{{ $statusColors[$order->status] ?? 'info' }}">
                            {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                        </span>
                    </td>
                    <td><span class="dms-badge dms-badge-info">Transfer</span></td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="dms-btn dms-btn-outline" style="padding: 0.25rem 0.75rem;">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Belum ada riwayat order</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 1.5rem;">
        {{ $orders->links() }}
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
