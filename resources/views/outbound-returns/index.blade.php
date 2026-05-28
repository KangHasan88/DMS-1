@extends('layouts.sidebar')

@section('page-title', 'Return Out Management')
@section('breadcrumb', 'Outbound / Return Out')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Return Out (Retur / Ganti Rugi)</h3>
            <p class="dms-section-subtitle">
                Catatan pengeluaran barang untuk retur pelanggan, barang rusak, atau ganti rugi.
            </p>
        </div>
        @can('create outbound return')
        <a href="{{ route('outbound-returns.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Return
        </a>
        @endcan
    </div>

    <!-- Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('outbound-returns.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nomor retur, pelanggan..."
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <!-- Filter Return Type -->
            <select name="return_type" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('return_type'), ['return_type' => null])) }}">Semua Tipe</option>
                @foreach($types as $key => $label)
                    <option value="{{ route('outbound-returns.index', array_merge(request()->except('return_type'), ['return_type' => $key])) }}" {{ request('return_type') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Return</th>
                    <th>Pelanggan</th>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Tindakan</th>
                    <th>Total Item</th>
                    <th>Total Nilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $return)
                <tr>
                    <td><strong>{{ $return->return_number }}</strong></td>
                    <td>
                        <div>
                            <div>{{ $return->customer_name }}</div>
                            @if($return->customer_phone)
                            <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $return->customer_phone }}</div>
                            @endif
                        </div>
                    </td>
                    <td>{{ $return->return_date->format('d M Y') }}</td>
                    <td>
                        <span class="dms-badge dms-badge-warning">
                            {{ $return->type_label }}
                        </span>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-info">
                            {{ $return->action_label }}
                        </span>
                    </td>
                    <td>{{ number_format($return->items->sum('quantity')) }}</td>
                    <td style="font-weight: 600;">Rp {{ number_format($return->total, 0, ',', '.') }}</td>
                    <td>
                        <div class="dms-actions">
                            <a href="{{ route('outbound-returns.show', $return) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-arrow-return-left" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Belum ada data Return Out</p>
                        @can('create outbound return')
                        <a href="{{ route('outbound-returns.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Return
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="dms-pagination"><div></div><div>{{ $returns->links() }}</div></div>
</div>

@endsection
