@extends('layouts.sidebar')

@section('page-title', 'Detail Sesi Rute')
@section('breadcrumb', 'Operasional / Pengiriman / Sesi Rute / Detail')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Detail Sesi Rute</h3>
            <p class="dms-section-subtitle">{{ $session->route_code }} · {{ $session->selling_mode_label }}</p>
        </div>
        <div class="dms-toolbar-actions">
            @can('edit deliveries')
                @if($session->canEdit())
                <a href="{{ route('delivery-route-sessions.edit', $session) }}" class="dms-btn dms-btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                @endif
            @endcan
            <a href="{{ route('delivery-route-sessions.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="route-detail-grid">
        <section class="route-detail-panel">
            <h4><i class="bi bi-diagram-3"></i> Informasi Rute</h4>
            <dl>
                <dt>Kode Rute</dt><dd>{{ $session->route_code }}</dd>
                <dt>Tanggal</dt><dd>{{ $session->route_date?->format('d M Y') }}</dd>
                <dt>Cabang</dt><dd>{{ $session->companyBranch?->name }} - {{ $session->companyBranch?->code }}</dd>
                <dt>Area Sales</dt><dd>{{ $session->salesTerritory?->code }} - {{ $session->salesTerritory?->name }}</dd>
                <dt>Mode</dt><dd><span class="dms-badge dms-badge-info">{{ $session->selling_mode_label }}</span></dd>
                <dt>Status</dt><dd><span class="dms-badge dms-badge-{{ $session->status_color }}">{{ $session->status_label }}</span></dd>
            </dl>
        </section>

        <section class="route-detail-panel">
            <h4><i class="bi bi-truck"></i> Penugasan</h4>
            <dl>
                <dt>Sales Owner</dt><dd>{{ $session->salesperson?->name ?? '-' }}</dd>
                <dt>Driver</dt><dd>{{ $session->driver?->name ?? '-' }}</dd>
                <dt>Armada</dt><dd>{{ $session->vehicle?->code ?? '-' }} - {{ $session->vehicle?->name ?? '-' }}</dd>
                <dt>No. Polisi</dt><dd>{{ $session->vehicle?->plate_number ?? '-' }}</dd>
                <dt>Mulai</dt><dd>{{ $session->started_at?->format('d M Y H:i') ?? '-' }}</dd>
                <dt>Closed</dt><dd>{{ $session->closed_at?->format('d M Y H:i') ?? '-' }}</dd>
            </dl>
        </section>
    </div>

    <div class="route-metrics">
        <div><span>Qty Awal</span><strong>{{ number_format($session->opening_qty) }}</strong></div>
        <div><span>Terjual</span><strong>{{ number_format($session->sold_qty) }}</strong></div>
        <div><span>Kembali</span><strong>{{ number_format($session->returned_qty) }}</strong></div>
        <div><span>Rusak</span><strong>{{ number_format($session->damaged_qty) }}</strong></div>
        <div><span>Sisa</span><strong>{{ number_format($session->remaining_qty) }}</strong></div>
    </div>

    <div class="route-note">
        <div class="dms-strong">Catatan</div>
        <div class="dms-muted">{{ $session->notes ?: 'Tidak ada catatan.' }}</div>
    </div>

    <div class="route-orders">
        <div class="route-subtitle">
            <i class="bi bi-receipt"></i>
            <span>Order Terkait</span>
        </div>
        <div class="dms-table-wrap">
            <table class="dms-table">
                <thead>
                    <tr>
                        <th>No. Order</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th style="width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($session->orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="dms-link-strong">{{ $order->order_number }}</a>
                        </td>
                        <td>{{ $order->customer?->name ?? '-' }}</td>
                        <td>{{ $order->created_at?->format('d M Y H:i') }}</td>
                        <td class="dms-strong">Rp {{ number_format($order->grand_total ?? $order->total ?? 0, 0, ',', '.') }}</td>
                        <td><span class="dms-badge dms-badge-{{ $order->status_color ?? 'secondary' }}">{{ $order->status_label ?? $order->status }}</span></td>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 2rem; text-align: center;">
                            <span class="dms-muted">Belum ada order yang terhubung ke sesi rute ini.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@once
<style>
.route-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}
.route-detail-panel {
    border: 1px solid var(--k-gray-200);
    border-radius: 6px;
    padding: 1rem;
    background: #fbfdff;
}
.route-detail-panel h4,
.route-subtitle {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: 0 0 .85rem;
    color: var(--k-navy);
    font-size: .95rem;
    font-weight: 700;
}
.route-detail-panel dl {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: .55rem .85rem;
    margin: 0;
}
.route-detail-panel dt {
    color: var(--k-gray-600);
    font-weight: 700;
}
.route-detail-panel dd {
    margin: 0;
    color: var(--k-navy);
}
.route-metrics {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: .75rem;
    margin-top: 1rem;
}
.route-metrics div {
    border: 1px solid var(--k-gray-200);
    border-radius: 6px;
    padding: .85rem;
    background: #fff;
}
.route-metrics span {
    display: block;
    color: var(--k-gray-600);
    font-size: .75rem;
    font-weight: 700;
}
.route-metrics strong {
    display: block;
    margin-top: .25rem;
    color: var(--k-navy);
    font-size: 1.1rem;
}
.route-note {
    margin-top: 1rem;
    padding: .85rem;
    border-radius: 6px;
    background: var(--k-gray-50);
}
.route-orders {
    margin-top: 1.25rem;
}
.dms-link-strong {
    color: var(--k-blue);
    font-weight: 700;
    text-decoration: none;
}
@media (max-width: 900px) {
    .route-detail-grid,
    .route-metrics {
        grid-template-columns: 1fr;
    }
}
</style>
@endonce
@endsection
