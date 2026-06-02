@extends('layouts.sidebar')

@section('page-title', 'Konsinyasi')
@section('breadcrumb', 'Pembelian / Konsinyasi')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Konsinyasi</h3>
            <p class="dms-section-subtitle">
                Kelola barang titipan pemasok, pembayaran, dan retur konsinyasi.
            </p>
        </div>
        @can('create consignments')
        <a href="{{ route('consignments.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Consignment
        </a>
        @endcan
    </div>

    <!-- Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('consignments.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nomor CN, pemasok..."
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('consignments.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('consignments.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('consignments.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('consignments.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('consignments.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('consignments.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Consignments Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. CN</th>
                    <th>Pemasok</th>
                    <th>Tanggal</th>
                    <th>Total Item</th>
                    <th>Terjual</th>
                    <th>Total Nilai</th>
                    <th>Dibayar</th>
                    <th>Status</th>
                    <th style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($consignments as $cn)
                <tr>
                    <td><strong>{{ $cn->cn_number }}</strong></td>
                    <td>
                        <div>
                            <div>{{ $cn->supplier->name }}</div>
                            <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $cn->supplier->phone }}</div>
                        </div>
                    </td>
                    <td>{{ $cn->consignment_date->format('d M Y') }}</td>
                    <td>{{ number_format($cn->total_items) }}</td>
                    <td>
                        <span class="dms-badge dms-badge-success">
                            {{ number_format($cn->total_sold) }}
                        </span>
                    </td>
                    <td style="font-weight: 600;">Rp {{ number_format($cn->total_value, 0, ',', '.') }}</td>
                    <td style="font-weight: 600;">Rp {{ number_format($cn->total_paid, 0, ',', '.') }}</td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $cn->status_color }}">
                            {{ $cn->status_label }}
                        </span>
                    </td>
                    <td>
                        <div class="dms-actions">
                            <a href="{{ route('consignments.show', $cn) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit consignments')
                            @if(in_array($cn->status, ['active', 'partial']))
                            <a href="{{ route('consignments.return-form', $cn) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Return">
                                <i class="bi bi-arrow-return-left"></i>
                            </a>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-hand-thumbs-up" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Belum ada data consignment</p>
                        @can('create consignments')
                        <a href="{{ route('consignments.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Consignment
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="dms-pagination"><div></div><div>{{ $consignments->links() }}</div></div>
</div>

@endsection
