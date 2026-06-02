@extends('layouts.sidebar')

@section('page-title', 'Barang Bonus')
@section('breadcrumb', 'Operasional / Barang Bonus')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Barang Bonus</h3>
            <p class="dms-section-subtitle">
                Catatan pengeluaran barang untuk hadiah, sampel, dukungan pelanggan, atau kompensasi.
            </p>
        </div>
        @can('create outbound foc')
        <a href="{{ route('outbound-focs.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah FOC
        </a>
        @endcan
    </div>

    <!-- Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('outbound-focs.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nomor FOC, pelanggan..."
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <!-- Filter Reason -->
            <select name="reason" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('reason'), ['reason' => null])) }}">Semua Alasan</option>
                @foreach($reasons as $key => $label)
                    <option value="{{ route('outbound-focs.index', array_merge(request()->except('reason'), ['reason' => $key])) }}" {{ request('reason') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- FOC Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. FOC</th>
                    <th>Pelanggan</th>
                    <th>Tanggal</th>
                    <th>Alasan</th>
                    <th>Total Item</th>
                    <th>Total Nilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($focs as $foc)
                <tr>
                    <td><strong>{{ $foc->foc_number }}</strong></td>
                    <td>
                        <div>
                            <div>{{ $foc->customer_name }}</div>
                            @if($foc->customer_phone)
                            <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $foc->customer_phone }}</div>
                            @endif
                        </div>
                    </td>
                    <td>{{ $foc->foc_date->format('d M Y') }}</td>
                    <td>
                        <span class="dms-badge dms-badge-info">
                            {{ $foc->reason_label }}
                        </span>
                    </td>
                    <td>{{ number_format($foc->items->sum('quantity')) }}</td>
                    <td style="font-weight: 600;">Rp {{ number_format($foc->total, 0, ',', '.') }}</td>
                    <td>
                        <div class="dms-actions">
                            <a href="{{ route('outbound-focs.show', $foc) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-gift" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Belum ada data FOC Out</p>
                        @can('create outbound foc')
                        <a href="{{ route('outbound-focs.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah FOC
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="dms-pagination"><div></div><div>{{ $focs->links() }}</div></div>
</div>

@endsection
