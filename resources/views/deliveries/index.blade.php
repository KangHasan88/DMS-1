@extends('layouts.sidebar')

@section('page-title', 'Pengiriman')
@section('breadcrumb', 'Operasional / Pengiriman')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Pengiriman</h3>
            <p class="dms-section-subtitle">Pantau pengiriman internal, ekspedisi, resi, dan penyelesaian kirim.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            @can('create deliveries')
            <a href="{{ route('deliveries.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i> Tugaskan Pengiriman
            </a>
            @endcan
        </div>
    </div>

    <!-- Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('deliveries.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nomor order, kurir, ekspedisi, resi..."
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            @if($canFilterBranches)
            <select name="company_branch_id" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('deliveries.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => null])) }}">Semua Cabang</option>
                @foreach($companyBranches as $branch)
                    <option value="{{ route('deliveries.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} - {{ $branch->code }}
                    </option>
                @endforeach
            </select>
            @endif

            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('deliveries.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('deliveries.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <select name="delivery_method" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('deliveries.index', array_merge(request()->except('delivery_method'), ['delivery_method' => null])) }}">Semua Metode</option>
                @foreach($deliveryMethods as $key => $label)
                    <option value="{{ route('deliveries.index', array_merge(request()->except('delivery_method'), ['delivery_method' => $key])) }}" {{ request('delivery_method') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('deliveries.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('deliveries.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('deliveries.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('deliveries.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Deliveries Table -->
    <div class="dms-table-wrap">
        <table class="dms-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">ID</th>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">No. Order</th>
                    @if($canFilterBranches)
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Cabang</th>
                    @endif
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Petugas / Vendor</th>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Metode</th>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Status</th>
                    <th style="padding: 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Ditugaskan</th>
                    <th style="padding: 0.75rem; text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600);">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $delivery)
                <tr style="border-bottom: 1px solid var(--k-gray-200);">
                    <td style="padding: 0.75rem;">
                        <strong style="font-size: 0.75rem;">{{ $delivery->id }}</strong>
                    </td>
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-weight: 600; font-size: 0.75rem;">{{ $delivery->order->order_number ?? '-' }}</span>
                            <span style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $delivery->order->user->name ?? '-' }}</span>
                        </div>
                    </td>
                    @if($canFilterBranches)
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem;">{{ $delivery->order->companyBranch->name ?? '-' }}</span>
                            <span style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $delivery->order->companyBranch->code ?? '' }}</span>
                        </div>
                    </td>
                    @endif
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem;">{{ $delivery->kurir->name ?? $delivery->vendor?->name ?? '-' }}</span>
                            <span style="font-size: 0.65rem; color: var(--k-gray-500);">
                                @if($delivery->usesInternalDelivery())
                                    {{ $delivery->vehicle ? $delivery->vehicle->code . ($delivery->vehicle->plate_number ? ' / ' . $delivery->vehicle->plate_number : '') : '-' }}
                                @else
                                    {{ $delivery->tracking_code ?? '-' }}
                                @endif
                            </span>
                        </div>
                    </td>
                    <td style="padding: 0.75rem;">
                        <span class="dms-badge dms-badge-{{ $delivery->usesExpedition() ? 'warning' : 'info' }}" style="font-size: 0.6rem; padding: 0.2rem 0.6rem;">
                            {{ $delivery->delivery_method_label }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem;">
                        <span class="dms-badge dms-badge-{{ $delivery->status_color }}" style="font-size: 0.6rem; padding: 0.2rem 0.6rem;">
                            {{ $delivery->status_label }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.7rem;">{{ $delivery->created_at->format('d M Y') }}</span>
                            <span style="font-size: 0.6rem; color: var(--k-gray-500);">{{ $delivery->created_at->format('H:i') }}</span>
                        </div>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <a href="{{ route('deliveries.show', $delivery) }}" class="dms-btn dms-btn-outline" style="padding: 0.25rem 0.7rem; font-size: 0.65rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                            <i class="bi bi-eye" style="font-size: 0.7rem;"></i> Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canFilterBranches ? 8 : 7 }}" style="padding: 3rem; text-align: center;">
                        <i class="bi bi-truck" style="font-size: 2.5rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--k-gray-500);">Belum ada data pengiriman</p>
                        @can('create deliveries')
                        <a href="{{ route('deliveries.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-plus-circle"></i> Tugaskan Pengiriman
                        </a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($deliveries->hasPages())
    <div class="dms-pagination"><div></div><div>{{ $deliveries->links() }}</div></div>
    @endif
</div>

@endsection
