@extends('layouts.sidebar')

@section('page-title', 'Invoice AP')
@section('breadcrumb', 'Finance / Invoice AP')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Invoice AP</h3>
            <p class="dms-section-subtitle">Pantau tagihan pemasok dari PO diterima sampai hutang supplier terbayar.</p>
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('ap-invoices.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari invoice, PO, pemasok..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('ap-invoices.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('ap-invoices.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <select name="supplier_id" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('ap-invoices.index', array_merge(request()->except('supplier_id'), ['supplier_id' => null])) }}">Semua Pemasok</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ route('ap-invoices.index', array_merge(request()->except('supplier_id'), ['supplier_id' => $supplier->id])) }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @can('create invoice')
        @if($invoiceablePurchaseOrders->isNotEmpty())
            <div style="margin-bottom: 1rem; padding: 1rem; border: 1px solid var(--k-border); border-radius: 8px; background: #f8fbff;">
                <div class="dms-section-header" style="margin-bottom: 0.75rem;">
                    <div>
                        <h3 class="dms-section-title" style="font-size: 0.95rem;">PO Siap Dibuat Invoice</h3>
                        <p class="dms-section-subtitle">PO received yang belum memiliki invoice AP.</p>
                    </div>
                    <span class="dms-badge dms-badge-info">{{ $invoiceablePurchaseOrders->count() }} PO</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 0.75rem;">
                    @foreach($invoiceablePurchaseOrders as $purchaseOrder)
                        <form action="{{ route('ap-invoices.store') }}" method="POST" style="margin: 0;">
                            @csrf
                            <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
                            <button type="submit" class="dms-btn dms-btn-outline" style="width: 100%; min-height: 56px; justify-content: space-between; text-align: left; gap: 0.75rem;">
                                <span style="display: flex; align-items: center; min-width: 0; gap: 0.75rem;">
                                    <i class="bi bi-journal-text"></i>
                                    <span style="display: grid; min-width: 0;">
                                        <strong style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $purchaseOrder->po_number }}</strong>
                                        <small style="color: var(--k-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $purchaseOrder->supplier?->name ?? 'Pemasok' }}
                                        </small>
                                    </span>
                                </span>
                                <span style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                                    <strong class="dms-money">Rp {{ number_format($purchaseOrder->total, 0, ',', '.') }}</strong>
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
                    <th>Pemasok</th>
                    <th>PO</th>
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
                        <td>{{ $invoice->supplier?->name ?? '-' }}</td>
                        <td>{{ $invoice->purchaseOrder?->po_number ?? '-' }}</td>
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
                            <a href="{{ route('ap-invoices.show', $invoice) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="dms-empty">
                            <i class="bi bi-journal-text"></i>
                            <p>Belum ada AP Invoice</p>
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
