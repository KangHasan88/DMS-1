@extends('layouts.sidebar')

@section('page-title', 'Return Out Management')
@section('breadcrumb', 'Outbound / Return Out')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Return Out (Retur / Ganti Rugi)</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">
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
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <form action="{{ route('outbound-returns.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari nomor retur, pelanggan..."
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter Return Type -->
            <select name="return_type" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('return_type'), ['return_type' => null])) }}">Semua Tipe</option>
                @foreach($types as $key => $label)
                    <option value="{{ route('outbound-returns.index', array_merge(request()->except('return_type'), ['return_type' => $key])) }}" {{ request('return_type') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('outbound-returns.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Returns Table -->
    <div style="overflow-x: auto;">
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
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('outbound-returns.show', $return) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Detail">
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
    
    <div style="margin-top: 1rem;">
        {{ $returns->links() }}
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
