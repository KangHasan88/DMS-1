@extends('layouts.sidebar')

@section('page-title', 'Sesi Rute')
@section('breadcrumb', 'Operasional / Pengiriman / Sesi Rute')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Sesi Rute</h3>
            <p class="dms-section-subtitle">Kelola rute penjualan canvas, semi canvas, driver, armada, dan rekap muatan.</p>
        </div>
        @can('create deliveries')
        <a href="{{ route('delivery-route-sessions.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Sesi Rute
        </a>
        @endcan
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('delivery-route-sessions.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari kode rute, area, sales, driver..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>

        <div class="dms-toolbar-actions">
            @if($canFilterBranches)
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-route-sessions.index', request()->except('company_branch_id')) }}">Semua Cabang</option>
                @foreach($companyBranches as $branch)
                    <option value="{{ route('delivery-route-sessions.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" @selected((string) request('company_branch_id') === (string) $branch->id)>
                        {{ $branch->name }} - {{ $branch->code }}
                    </option>
                @endforeach
            </select>
            @endif
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-route-sessions.index', request()->except('selling_mode')) }}">Semua Mode</option>
                @foreach($modes as $value => $label)
                    <option value="{{ route('delivery-route-sessions.index', array_merge(request()->except('selling_mode'), ['selling_mode' => $value])) }}" @selected(request('selling_mode') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-route-sessions.index', request()->except('status')) }}">Semua Status</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ route('delivery-route-sessions.index', array_merge(request()->except('status'), ['status' => $value])) }}" @selected(request('status') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Kode Rute</th>
                    <th>Tanggal</th>
                    @if($canFilterBranches)
                    <th>Cabang</th>
                    @endif
                    <th>Area</th>
                    <th>Sales / Driver</th>
                    <th>Armada</th>
                    <th>Mode</th>
                    <th>Muatan</th>
                    <th>Status</th>
                    <th style="width: 104px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                <tr>
                    <td>
                        <a href="{{ route('delivery-route-sessions.show', $session) }}" class="dms-link-strong">
                            {{ $session->route_code }}
                        </a>
                        <div class="dms-muted">{{ $session->orders_count }} order terkait</div>
                    </td>
                    <td>
                        <div class="dms-strong">{{ $session->route_date?->format('d M Y') }}</div>
                        <div class="dms-muted">{{ $session->started_at ? 'Mulai ' . $session->started_at->format('H:i') : 'Belum mulai' }}</div>
                    </td>
                    @if($canFilterBranches)
                    <td>{{ $session->companyBranch?->code ?? '-' }}</td>
                    @endif
                    <td>
                        <div class="dms-strong">{{ $session->salesTerritory?->code ?? '-' }}</div>
                        <div class="dms-muted">{{ $session->salesTerritory?->name ?? 'Tanpa area' }}</div>
                    </td>
                    <td>
                        <div class="dms-strong">{{ $session->salesperson?->name ?? '-' }}</div>
                        <div class="dms-muted">{{ $session->driver?->name ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="dms-strong">{{ $session->vehicle?->code ?? '-' }}</div>
                        <div class="dms-muted">{{ $session->vehicle?->plate_number ?? '-' }}</div>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-info">{{ $session->selling_mode_label }}</span>
                    </td>
                    <td>
                        <div class="route-qty-line">
                            <span>Awal {{ number_format($session->opening_qty) }}</span>
                            <span>Sisa {{ number_format($session->remaining_qty) }}</span>
                        </div>
                        <div class="dms-muted">Jual {{ number_format($session->sold_qty) }} · Retur {{ number_format($session->returned_qty) }}</div>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $session->status_color }}">{{ $session->status_label }}</span>
                    </td>
                    <td>
                        <div class="dms-row-actions">
                            <a href="{{ route('delivery-route-sessions.show', $session) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit deliveries')
                                @if($session->canEdit())
                                <a href="{{ route('delivery-route-sessions.edit', $session) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canFilterBranches ? 10 : 9 }}" style="padding: 3rem; text-align: center;">
                        <i class="bi bi-signpost-split" style="font-size: 2.5rem; color: var(--k-gray-300);"></i>
                        <p class="dms-muted" style="margin-top: 0.75rem;">Belum ada sesi rute.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
    <div class="dms-pagination"><div></div><div>{{ $sessions->links() }}</div></div>
    @endif
</div>

@once
<style>
.dms-link-strong {
    color: var(--k-blue);
    font-weight: 700;
    text-decoration: none;
}
.dms-link-strong:hover {
    text-decoration: underline;
}
.route-qty-line {
    display: flex;
    gap: .75rem;
    font-size: .8rem;
    font-weight: 700;
    color: var(--k-navy);
}
.dms-row-actions {
    display: inline-flex;
    gap: .35rem;
    align-items: center;
}
</style>
@endonce
@endsection
