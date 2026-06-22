@extends('layouts.sidebar')

@section('page-title', 'Pengiriman Hari Ini')
@section('breadcrumb', 'Operasional / Pengiriman / Hari Ini')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Pengiriman Hari Ini</h3>
            <p class="dms-section-subtitle">Daftar pengiriman yang ditugaskan ke kurir hari ini.</p>
        </div>
        <a href="{{ route('deliveries.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Semua Pengiriman
        </a>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                    <th style="padding: 0.75rem; text-align: left;">No. Order</th>
                    <th style="padding: 0.75rem; text-align: left;">Pelanggan</th>
                    <th style="padding: 0.75rem; text-align: left;">Alamat</th>
                    <th style="padding: 0.75rem; text-align: left;">Status</th>
                    <th style="padding: 0.75rem; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $delivery)
                <tr style="border-bottom: 1px solid var(--k-gray-200);">
                    <td style="padding: 0.75rem;">
                        <a href="{{ route('deliveries.show', $delivery) }}" class="order-number">
                            {{ $delivery->order->order_number ?? '-' }}
                        </a>
                    </td>
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-weight: 600;">{{ $delivery->order->user->name ?? '-' }}</span>
                            <span class="dms-muted">{{ $delivery->order->user->phone ?? '-' }}</span>
                        </div>
                    </td>
                    <td style="padding: 0.75rem;">{{ $delivery->order->address ?? '-' }}</td>
                    <td style="padding: 0.75rem;">
                        <span class="dms-badge dms-badge-{{ $delivery->status_color }}">
                            {{ $delivery->status_label }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <a href="{{ route('deliveries.show', $delivery) }}" class="dms-btn dms-btn-outline dms-btn-sm" style="text-decoration: none;">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding: 3rem; text-align: center;">
                        <i class="bi bi-truck" style="font-size: 2.5rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 0.75rem; color: var(--k-gray-500);">Belum ada pengiriman hari ini.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
