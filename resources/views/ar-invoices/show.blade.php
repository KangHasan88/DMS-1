@extends('layouts.sidebar')

@section('page-title', 'Detail Invoice AR')
@section('breadcrumb', 'Finance / Invoice AR / Detail')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $arInvoice->invoice_number }}</h3>
            <p class="dms-section-subtitle">Invoice dari order {{ $arInvoice->order?->order_number ?? '-' }}.</p>
        </div>
        <div class="dms-toolbar-actions">
            <a href="{{ route('orders.invoice', $arInvoice->order) }}" class="dms-btn dms-btn-outline" target="_blank">
                <i class="bi bi-printer"></i>
                Cetak Dokumen
            </a>
            <a href="{{ route('ar-invoices.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Pelanggan</span>
            <strong>{{ $arInvoice->customer?->name ?? $arInvoice->customerUser?->name ?? '-' }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Cabang</span>
            <strong>{{ $arInvoice->companyBranch?->name ?? '-' }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Status</span>
            <span class="dms-badge dms-badge-{{ $arInvoice->status_badge }}">{{ $arInvoice->status_label }}</span>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Jatuh Tempo</span>
            <strong>{{ $arInvoice->due_date?->format('d M Y') ?? '-' }}</strong>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Diskon</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($arInvoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td class="dms-money">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
        <div style="min-width: 320px;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Subtotal</span>
                <strong>Rp {{ number_format($arInvoice->subtotal, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Diskon</span>
                <strong>Rp {{ number_format($arInvoice->discount_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Ongkir</span>
                <strong>Rp {{ number_format($arInvoice->shipping_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Packing</span>
                <strong>Rp {{ number_format($arInvoice->packing_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>PPN</span>
                <strong>Rp {{ number_format($arInvoice->ppn_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem 0 0.35rem; border-top: 1px solid var(--k-border);">
                <span>Total Invoice</span>
                <strong>Rp {{ number_format($arInvoice->total_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Terbayar</span>
                <strong>Rp {{ number_format($arInvoice->paid_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Outstanding</span>
                <strong>Rp {{ number_format($arInvoice->outstanding_amount, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection
