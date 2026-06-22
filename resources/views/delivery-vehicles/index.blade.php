@extends('layouts.sidebar')

@section('page-title', 'Armada')
@section('breadcrumb', 'Operasional / Pengiriman / Armada')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Armada</h3>
            <p class="dms-section-subtitle">Kelola kendaraan internal untuk penugasan pengiriman.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            @can('create deliveries')
            <a href="{{ route('delivery-vehicles.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Armada
            </a>
            @endcan
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('delivery-vehicles.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari kode, plat, nama armada..."
                       value="{{ request('search') }}"
                       class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>

        <div class="dms-toolbar-actions">
            @if($canFilterBranches)
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-vehicles.index', request()->except('company_branch_id')) }}">Semua Cabang</option>
                @foreach($companyBranches as $branch)
                    <option value="{{ route('delivery-vehicles.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} - {{ $branch->code }}
                    </option>
                @endforeach
            </select>
            @endif

            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-vehicles.index', request()->except('status')) }}">Semua Status</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ route('delivery-vehicles.index', array_merge(request()->except('status'), ['status' => $value])) }}" {{ request('status') === $value ? 'selected' : '' }}>
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
                    <th>Armada</th>
                    @if($canFilterBranches)
                    <th>Cabang</th>
                    @endif
                    <th>Jenis</th>
                    <th>Driver Utama</th>
                    <th>Kapasitas</th>
                    <th>Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehicles as $vehicle)
                <tr>
                    <td>
                        <div class="dms-strong">{{ $vehicle->code }} - {{ $vehicle->name }}</div>
                        <div class="dms-muted">{{ $vehicle->plate_number ?: '-' }}</div>
                    </td>
                    @if($canFilterBranches)
                    <td>{{ $vehicle->companyBranch?->code ?? 'Global' }}</td>
                    @endif
                    <td>{{ $vehicle->type_label }}</td>
                    <td>
                        <div class="dms-strong">{{ $vehicle->activeDriverAssignment?->driver?->name ?? '-' }}</div>
                        @if($vehicle->activeDriverAssignment?->driver?->phone)
                            <div class="dms-muted">{{ $vehicle->activeDriverAssignment->driver->phone }}</div>
                        @endif
                    </td>
                    <td>{{ $vehicle->capacity ?: '-' }}</td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $vehicle->status_color }}">
                            {{ $vehicle->status_label }}
                        </span>
                    </td>
                    <td>
                        @can('edit deliveries')
                        <a href="{{ route('delivery-vehicles.edit', $vehicle) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canFilterBranches ? 7 : 6 }}" style="padding: 3rem; text-align: center;">
                        <i class="bi bi-truck-front" style="font-size: 2.5rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 0.75rem; color: var(--k-gray-500);">Belum ada data armada.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($vehicles->hasPages())
    <div class="dms-pagination"><div></div><div>{{ $vehicles->links() }}</div></div>
    @endif
</div>
@endsection
