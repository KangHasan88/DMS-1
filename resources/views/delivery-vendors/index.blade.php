@extends('layouts.sidebar')

@section('page-title', 'Ekspedisi')
@section('breadcrumb', 'Operasional / Pengiriman / Ekspedisi')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Ekspedisi</h3>
            <p class="dms-section-subtitle">Kelola vendor pengiriman pihak ketiga dan termin tagihannya.</p>
        </div>
        @can('create deliveries')
        <a href="{{ route('delivery-vendors.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Ekspedisi
        </a>
        @endcan
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('delivery-vendors.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari ekspedisi, kode, kontak..."
                       value="{{ request('search') }}"
                       class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>

        <div class="dms-toolbar-actions">
            @if($canFilterBranches)
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('delivery-vendors.index', request()->except('company_branch_id')) }}">Semua Cabang</option>
                @foreach($companyBranches as $branch)
                    <option value="{{ route('delivery-vendors.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} - {{ $branch->code }}
                    </option>
                @endforeach
            </select>
            @endif
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                    <th style="padding: 0.75rem; text-align: left;">Ekspedisi</th>
                    @if($canFilterBranches)
                    <th style="padding: 0.75rem; text-align: left;">Cabang</th>
                    @endif
                    <th style="padding: 0.75rem; text-align: left;">Kontak</th>
                    <th style="padding: 0.75rem; text-align: left;">Termin</th>
                    <th style="padding: 0.75rem; text-align: left;">Status</th>
                    <th style="padding: 0.75rem; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $vendor)
                <tr style="border-bottom: 1px solid var(--k-gray-200);">
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-weight: 700;">{{ $vendor->name }}</span>
                            <span class="dms-muted">{{ $vendor->code ?: '-' }} · {{ ucfirst($vendor->vendor_type) }}</span>
                        </div>
                    </td>
                    @if($canFilterBranches)
                    <td style="padding: 0.75rem;">{{ $vendor->companyBranch?->code ?? 'Global' }}</td>
                    @endif
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span>{{ $vendor->contact_person ?: '-' }}</span>
                            <span class="dms-muted">{{ $vendor->phone ?: '-' }}</span>
                        </div>
                    </td>
                    <td style="padding: 0.75rem;">{{ ucfirst($vendor->payment_term) }}</td>
                    <td style="padding: 0.75rem;">
                        <span class="dms-badge dms-badge-{{ $vendor->is_active ? 'success' : 'secondary' }}">
                            {{ $vendor->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        @can('edit deliveries')
                        <a href="{{ route('delivery-vendors.edit', $vendor) }}" class="dms-btn dms-btn-outline dms-btn-sm" style="text-decoration: none;">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canFilterBranches ? 6 : 5 }}" style="padding: 3rem; text-align: center;">
                        <i class="bi bi-truck" style="font-size: 2.5rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 0.75rem; color: var(--k-gray-500);">Belum ada data ekspedisi.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($vendors->hasPages())
    <div class="dms-pagination"><div></div><div>{{ $vendors->links() }}</div></div>
    @endif
</div>
@endsection
