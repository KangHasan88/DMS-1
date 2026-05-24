@extends('layouts.sidebar')

@section('page-title', 'Consignment Management')
@section('breadcrumb', 'Consignments')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Consignment (Titip Jual)</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">
                Kelola barang titipan dari supplier. Barang baru dibayar setelah terjual.
            </p>
        </div>
        @can('create consignments')
        <a href="{{ route('consignments.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Consignment
        </a>
        @endcan
    </div>

    <!-- Filter -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <form action="{{ route('consignments.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari nomor CN, supplier..." 
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('consignments.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('consignments.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('consignments.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('consignments.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('consignments.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('consignments.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Consignments Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. CN</th>
                    <th>Supplier</th>
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
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('consignments.show', $cn) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit consignments')
                            @if(in_array($cn->status, ['active', 'partial']))
                            <a href="{{ route('consignments.return-form', $cn) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Return">
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
    
    <div style="margin-top: 1rem;">
        {{ $consignments->links() }}
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
