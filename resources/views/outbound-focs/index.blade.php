@extends('layouts.sidebar')

@section('page-title', 'FOC Out Management')
@section('breadcrumb', 'Outbound / FOC Out')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">FOC Out (Hadiah / Sample)</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">
                Catatan pengeluaran barang untuk hadiah, sample, support customer, atau kompensasi.
            </p>
        </div>
        <a href="{{ route('outbound-focs.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah FOC
        </a>
    </div>

    <!-- Filter -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <form action="{{ route('outbound-focs.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari nomor FOC, customer..." 
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter Reason -->
            <select name="reason" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('reason'), ['reason' => null])) }}">Semua Alasan</option>
                @foreach($reasons as $key => $label)
                    <option value="{{ route('outbound-focs.index', array_merge(request()->except('reason'), ['reason' => $key])) }}" {{ request('reason') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('outbound-focs.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- FOC Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                ??
                    <th>No. FOC</th>
                    <th>Customer</th>
                    <th>Tanggal</th>
                    <th>Alasan</th>
                    <th>Total Item</th>
                    <th>Total Nilai</th>
                    <th>Aksi</th>
                </thead>
            </thead>
            <tbody>
                @forelse($focs as $foc)
                ??
                    ??<strong>{{ $foc->foc_number }}</strong>??
                    ??
                        <div>
                            <div>{{ $foc->customer_name }}</div>
                            @if($foc->customer_phone)
                            <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $foc->customer_phone }}</div>
                            @endif
                        </div>
                    ??
                    ??{{ $foc->foc_date->format('d M Y') }}??
                    ??
                        <span class="dms-badge dms-badge-info">
                            {{ $foc->reason_label }}
                        </span>
                    ??
                    <td>{{ number_format($foc->items->sum('quantity')) }}??
                    <td style="font-weight: 600;">Rp {{ number_format($foc->total, 0, ',', '.') }}??
                    ??
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('outbound-focs.show', $foc) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    ??
                </thead>
                @empty
                ??
                    <td colspan="7" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-gift" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Belum ada data FOC Out</p>
                        <a href="{{ route('outbound-focs.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah FOC
                        </a>
                    </thead>
                </thead>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 1rem;">
        {{ $focs->links() }}
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