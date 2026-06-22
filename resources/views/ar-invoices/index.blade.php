@extends('layouts.sidebar')

@section('page-title', 'Invoice AR')
@section('breadcrumb', 'Finance / Invoice AR')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Invoice AR</h3>
            <p class="dms-section-subtitle">Pantau tagihan pelanggan dari order terkirim sampai menjadi piutang tertagih.</p>
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('ar-invoices.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari invoice, order, pelanggan..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('ar-invoices.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('ar-invoices.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @if($canFilterBranches)
                <select name="company_branch_id" onchange="window.location.href = this.value" class="form-control">
                    <option value="{{ route('ar-invoices.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => null])) }}">Semua Cabang</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ route('ar-invoices.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    @can('create invoice')
        @if($invoiceableOrders->isNotEmpty())
            <div style="margin-bottom: 1rem; padding: 1rem; border: 1px solid var(--k-border); border-radius: 8px; background: #f8fbff;">
                <div class="dms-section-header" style="margin-bottom: 0.75rem;">
                    <div>
                        <h3 class="dms-section-title" style="font-size: 0.95rem;">Order Siap Dibuat Invoice</h3>
                        <p class="dms-section-subtitle">Order terkirim yang belum memiliki invoice AR.</p>
                    </div>
                    <span class="dms-badge dms-badge-info">{{ $invoiceableOrders->count() }} order</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 0.75rem;">
                    @foreach($invoiceableOrders as $order)
                        <form action="{{ route('ar-invoices.store') }}" method="POST" style="margin: 0;">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <button type="submit" class="dms-btn dms-btn-outline" style="width: 100%; min-height: 56px; justify-content: space-between; text-align: left; gap: 0.75rem;">
                                <span style="display: flex; align-items: center; min-width: 0; gap: 0.75rem;">
                                    <i class="bi bi-receipt"></i>
                                    <span style="display: grid; min-width: 0;">
                                        <strong style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $order->order_number }}</strong>
                                        <small style="color: var(--k-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $order->user?->customer?->name ?? $order->user?->name ?? 'Pelanggan' }}
                                        </small>
                                    </span>
                                </span>
                                <span style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                                    <strong class="dms-money">Rp {{ number_format($order->grand_total ?: $order->total, 0, ',', '.') }}</strong>
                                    <span class="dms-badge dms-badge-info">Buat Invoice</span>
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
    @endcan

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Pelanggan</th>
                    <th>Order</th>
                    <th>Tanggal</th>
                    <th>Jatuh Tempo</th>
                    <th>Total</th>
                    <th>Outstanding</th>
                    <th>Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->customer?->name ?? $invoice->customerUser?->name ?? '-' }}</td>
                        <td>{{ $invoice->order?->order_number ?? '-' }}</td>
                        <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td>
                            <span style="{{ $invoice->is_overdue ? 'color: var(--k-danger); font-weight: 700;' : '' }}">
                                {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                            </span>
                        </td>
                        <td class="dms-money">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $invoice->status_badge }}">
                                {{ $invoice->status_label }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('ar-invoices.show', $invoice) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="dms-empty">
                            <i class="bi bi-receipt"></i>
                            <p>Belum ada AR Invoice</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination" style="margin-top: 1rem;">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
