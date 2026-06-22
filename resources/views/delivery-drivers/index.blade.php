@extends('layouts.sidebar')

@section('page-title', 'Driver')
@section('breadcrumb', 'Operasional / Pengiriman / Driver')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Driver</h3>
            <p class="dms-section-subtitle">Kelola driver internal dan lihat armada utama untuk penugasan pengiriman.</p>
        </div>
        @can('create users')
        <a href="{{ route('admin.users.create', ['role' => 'kurir']) }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Driver
        </a>
        @endcan
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('delivery-drivers.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari nama, telepon, atau email driver..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>

        <div class="dms-toolbar-actions">
            @if($canFilterBranches)
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-drivers.index', request()->except('company_branch_id')) }}">Semua Cabang</option>
                @foreach($companyBranches as $branch)
                    <option value="{{ route('delivery-drivers.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}"
                            {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} - {{ $branch->code }}
                    </option>
                @endforeach
            </select>
            @endif

            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-drivers.index', request()->except('status')) }}">Semua Status</option>
                <option value="{{ route('delivery-drivers.index', array_merge(request()->except('status'), ['status' => 'active'])) }}"
                        {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('delivery-drivers.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}"
                        {{ request('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>

            <select onchange="window.location.href = this.value" class="form-control">
                @foreach([10, 20, 50] as $size)
                    <option value="{{ route('delivery-drivers.index', array_merge(request()->except('per_page'), ['per_page' => $size])) }}"
                            {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                        {{ $size }} per halaman
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Driver</th>
                    @if($canFilterBranches)
                    <th>Cabang</th>
                    @endif
                    <th>Armada Utama</th>
                    <th>Status Armada</th>
                    <th>Status Akun</th>
                    <th style="width: 90px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                    @php($vehicle = $driver->activeDriverVehicleAssignment?->vehicle)
                    <tr>
                        <td>
                            <div class="dms-strong">{{ $driver->name }}</div>
                            <div class="dms-muted">{{ $driver->phone ?: $driver->email }}</div>
                        </td>
                        @if($canFilterBranches)
                        <td>{{ $driver->companyBranch?->code ?? '-' }}</td>
                        @endif
                        <td>
                            @if($vehicle)
                                <div class="dms-strong">{{ $vehicle->code }} - {{ $vehicle->name }}</div>
                                <div class="dms-muted">{{ $vehicle->plate_number ?: '-' }}</div>
                            @else
                                <span class="dms-muted">Belum ditetapkan</span>
                            @endif
                        </td>
                        <td>
                            @if($vehicle)
                                <span class="dms-badge dms-badge-{{ $vehicle->status_color }}">
                                    {{ $vehicle->status_label }}
                                </span>
                            @else
                                <span class="dms-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $driver->is_active ? 'success' : 'secondary' }}">
                                {{ $driver->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </td>
                        <td>
                            @can('edit users')
                            <a href="{{ route('admin.users.edit', $driver) }}"
                               class="dms-btn dms-btn-outline dms-btn-sm"
                               title="Edit akun driver">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canFilterBranches ? 6 : 5 }}" style="padding: 3rem; text-align: center;">
                            <i class="bi bi-person-badge" style="font-size: 2.5rem; color: var(--k-gray-300);"></i>
                            <p style="margin-top: 0.75rem; color: var(--k-gray-500);">Belum ada data driver.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($drivers->hasPages())
    <div class="dms-pagination"><div></div><div>{{ $drivers->links() }}</div></div>
    @endif
</div>
@endsection
