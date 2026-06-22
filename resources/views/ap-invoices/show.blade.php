@extends('layouts.sidebar')

@section('page-title', 'Detail Invoice AP')
@section('breadcrumb', 'Finance / Invoice AP / Detail')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $apInvoice->invoice_number }}</h3>
            <p class="dms-section-subtitle">Invoice dari PO {{ $apInvoice->purchaseOrder?->po_number ?? '-' }}.</p>
        </div>
        <div class="dms-toolbar-actions">
            <a href="{{ route('purchase-orders.show', $apInvoice->purchaseOrder) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-bag-check"></i>
                Lihat PO
            </a>
            <a href="{{ route('ap-invoices.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Pemasok</span>
            <strong>{{ $apInvoice->supplier?->name ?? '-' }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Status</span>
            <span class="dms-badge dms-badge-{{ $apInvoice->status_badge }}">{{ $apInvoice->status_label }}</span>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Tanggal Invoice</span>
            <strong>{{ $apInvoice->invoice_date?->format('d M Y') ?? '-' }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Jatuh Tempo</span>
            <strong>{{ $apInvoice->due_date?->format('d M Y') ?? '-' }}</strong>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($apInvoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td class="dms-money">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
        <div style="min-width: 320px;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Total Invoice</span>
                <strong>Rp {{ number_format($apInvoice->total_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Terbayar</span>
                <strong>Rp {{ number_format($apInvoice->paid_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Outstanding</span>
                <strong>Rp {{ number_format($apInvoice->outstanding_amount, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection
