@extends('layouts.sidebar')

@section('page-title', 'Pembelian Langsung')
@section('breadcrumb', 'Pembelian / Pembelian Langsung')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Pembelian Langsung</h3>
            <p class="dms-section-subtitle">
                Catat pembelian tunai atau barang bonus yang langsung menambah stok.
            </p>
        </div>
        @can('create direct purchase')
        <a href="{{ route('direct-purchases.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Pembelian
        </a>
        @endcan
    </div>

    <!-- Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('direct-purchases.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nomor invoice, pemasok..."
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <!-- Filter Type -->
            <select name="purchase_type" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('direct-purchases.index', array_merge(request()->except('purchase_type'), ['purchase_type' => null])) }}">Semua Tipe</option>
                <option value="{{ route('direct-purchases.index', array_merge(request()->except('purchase_type'), ['purchase_type' => 'cash'])) }}" {{ request('purchase_type') == 'cash' ? 'selected' : '' }}>
                    Cash
                </option>
                <option value="{{ route('direct-purchases.index', array_merge(request()->except('purchase_type'), ['purchase_type' => 'foc'])) }}" {{ request('purchase_type') == 'foc' ? 'selected' : '' }}>
                    FOC (Bonus)
                </option>
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('direct-purchases.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('direct-purchases.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('direct-purchases.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('direct-purchases.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Data Table -->
    <div class="dms-table-wrap">
        <table class="dms-table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="padding: 0.75rem; text-align: left;">No. Invoice</th>
                    <th style="padding: 0.75rem; text-align: left;">Tipe</th>
                    <th style="padding: 0.75rem; text-align: left;">Pemasok</th>
                    <th style="padding: 0.75rem; text-align: left;">Tanggal</th>
                    <th style="padding: 0.75rem; text-align: left;">Total</th>
                    <th style="padding: 0.75rem; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr style="border-bottom: 1px solid var(--k-gray-200);">
                    <td style="padding: 0.75rem;">
                        <strong>{{ $purchase->invoice_number }}</strong>
                    </td>
                    <td style="padding: 0.75rem;">
                        @if($purchase->purchase_type == 'foc')
                            <span class="dms-badge dms-badge-success">
                                <i class="bi bi-gift"></i> FOC
                            </span>
                        @else
                            <span class="dms-badge dms-badge-info">
                                <i class="bi bi-cash"></i> Cash
                            </span>
                        @endif
                    </td>
                    <td style="padding: 0.75rem;">
                        <div>
                            <div style="font-weight: 500;">{{ $purchase->supplier_name }}</div>
                            @if($purchase->supplier_phone)
                            <div style="font-size: 0.7rem; color: var(--k-gray-500);">{{ $purchase->supplier_phone }}</div>
                            @endif
                        </div>
                    </td>
                    <td style="padding: 0.75rem;">
                        {{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}
                    </td>
                    <td style="padding: 0.75rem; font-weight: 600; color: var(--k-green);">
                        Rp {{ number_format($purchase->total, 0, ',', '.') }}
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <a href="{{ route('direct-purchases.show', $purchase) }}" class="dms-btn dms-btn-outline" style="padding: 0.3rem 0.8rem;">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 3rem; text-align: center;">
                        <i class="bi bi-receipt" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Belum ada data pembelian langsung</p>
                        @can('create direct purchase')
                        <a href="{{ route('direct-purchases.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Pembelian
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    @if($purchases->hasPages())
    <div class="dms-pagination"><div></div><div>{{ $purchases->links() }}</div></div>
    @endif
</div>

@endsection
