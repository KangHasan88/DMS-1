@extends('layouts.sidebar')

@section('page-title', 'Delivery Coverage')
@section('breadcrumb', 'Operasional / Pengiriman / Delivery Coverage')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Delivery Coverage Setting</h3>
            <p class="dms-section-subtitle">Atur zona pengiriman, prioritas depo, alamat customer, driver, dan armada.</p>
        </div>
        @can('edit deliveries')
        <a href="{{ route('delivery-coverage.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Zona
        </a>
        @endcan
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('delivery-coverage.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari kode atau nama zona...">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select class="form-control" onchange="window.location.href=this.value">
                <option value="{{ route('delivery-coverage.index', request()->except('status')) }}">Semua Status</option>
                <option value="{{ route('delivery-coverage.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" @selected(request('status') === 'active')>Aktif</option>
                <option value="{{ route('delivery-coverage.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" @selected(request('status') === 'inactive')>Tidak Aktif</option>
            </select>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Zona Pengiriman</th>
                    <th>Depo Pelayanan</th>
                    <th>Alamat Customer</th>
                    <th>Driver</th>
                    <th>Armada</th>
                    <th>Status</th>
                    <th style="width: 80px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($zones as $zone)
                <tr>
                    <td>
                        <div class="dms-strong">{{ $zone->code }} - {{ $zone->name }}</div>
                        <div class="dms-muted">{{ $zone->description ?: 'Tanpa deskripsi' }}</div>
                    </td>
                    <td>
                        @forelse($zone->activeDepots as $depot)
                            <div class="{{ $loop->first ? 'dms-strong' : 'dms-muted' }}">
                                {{ $depot->code }} &middot; Prioritas {{ $depot->pivot->priority }}
                                @if($loop->first)
                                    <span class="dms-badge dms-badge-info">Utama</span>
                                @endif
                            </div>
                        @empty
                            <span class="dms-muted">Belum diatur</span>
                        @endforelse
                    </td>
                    <td>{{ $zone->customer_addresses_count }} alamat</td>
                    <td>{{ $zone->drivers->count() }} driver</td>
                    <td>{{ $zone->vehicles->count() }} armada</td>
                    <td>
                        <span class="dms-badge {{ $zone->is_active ? 'dms-badge-success' : 'dms-badge-secondary' }}">
                            {{ $zone->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td>
                        @can('edit deliveries')
                        <a href="{{ route('delivery-coverage.edit', $zone) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Kelola coverage">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 3rem; text-align: center;">
                        <i class="bi bi-geo-alt" style="font-size: 2.4rem; color: var(--k-gray-300);"></i>
                        <p class="dms-muted" style="margin-top: 0.75rem;">Belum ada zona pengiriman.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($zones->hasPages())
    <div class="dms-pagination"><div></div><div>{{ $zones->links() }}</div></div>
    @endif
</div>
@endsection
