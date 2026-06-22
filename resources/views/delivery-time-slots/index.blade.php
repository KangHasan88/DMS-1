@extends('layouts.sidebar')

@section('page-title', 'Slot Waktu Pengiriman')
@section('breadcrumb', 'Operasional / Pengiriman / Slot Waktu')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Slot Waktu Pengiriman</h3>
            <p class="dms-section-subtitle">Kelola pilihan jam kirim yang dipakai saat input order.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('deliveries.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            @can('create deliveries')
            <a href="{{ route('delivery-time-slots.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Slot
            </a>
            @endcan
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('delivery-time-slots.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari nama slot atau jam..."
                       value="{{ request('search') }}"
                       class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>

        <div class="dms-toolbar-actions">
            @if($canFilterBranches)
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-time-slots.index', request()->except('company_branch_id')) }}">Semua Cabang</option>
                @foreach($companyBranches as $branch)
                    <option value="{{ route('delivery-time-slots.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} - {{ $branch->code }}
                    </option>
                @endforeach
            </select>
            @endif

            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-time-slots.index', request()->except('status')) }}">Semua Status</option>
                <option value="{{ route('delivery-time-slots.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('delivery-time-slots.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Slot</th>
                    <th>Jam</th>
                    @if($canFilterBranches)
                    <th>Cabang</th>
                    @endif
                    <th>Urutan</th>
                    <th>Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($timeSlots as $slot)
                <tr>
                    <td>
                        <div class="dms-strong">{{ $slot->name }}</div>
                        <div class="dms-muted">{{ $slot->period_label ?: '-' }}</div>
                    </td>
                    <td>{{ $slot->display_label }}</td>
                    @if($canFilterBranches)
                    <td>{{ $slot->companyBranch?->code ?? 'Global' }}</td>
                    @endif
                    <td>{{ $slot->sort_order }}</td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $slot->is_active ? 'success' : 'secondary' }}">
                            {{ $slot->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td>
                        @can('edit deliveries')
                        <a href="{{ route('delivery-time-slots.edit', $slot) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canFilterBranches ? 6 : 5 }}" style="padding: 3rem; text-align: center;">
                        <i class="bi bi-clock" style="font-size: 2.5rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 0.75rem; color: var(--k-gray-500);">Belum ada slot waktu pengiriman.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($timeSlots->hasPages())
    <div class="dms-pagination"><div></div><div>{{ $timeSlots->links() }}</div></div>
    @endif
</div>
@endsection
