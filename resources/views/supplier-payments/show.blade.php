@extends('layouts.sidebar')

@section('page-title', 'Detail Pembayaran Supplier')
@section('breadcrumb', 'Finance / Pembayaran Supplier / Detail')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $supplierPayment->payment_number }}</h3>
            <p class="dms-section-subtitle">Pembayaran untuk {{ $supplierPayment->supplier?->name ?? '-' }}.</p>
        </div>
        <div class="dms-toolbar-actions">
            <a href="{{ route('supplier-payments.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Pemasok</span>
            <strong>{{ $supplierPayment->supplier?->name ?? '-' }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Tanggal</span>
            <strong>{{ $supplierPayment->payment_date?->format('d M Y') ?? '-' }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Metode</span>
            <strong>{{ $supplierPayment->method_label }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Status</span>
            <span class="dms-badge dms-badge-{{ $supplierPayment->is_fully_allocated ? 'success' : 'warning' }}">
                {{ $supplierPayment->is_fully_allocated ? 'Teralokasi' : 'Sisa Saldo' }}
            </span>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>PO</th>
                    <th>Tanggal Alokasi</th>
                    <th>Catatan</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($supplierPayment->allocations as $allocation)
                    <tr>
                        <td>
                            <a href="{{ route('ap-invoices.show', $allocation->apInvoice) }}">
                                <strong>{{ $allocation->apInvoice?->invoice_number ?? '-' }}</strong>
                            </a>
                        </td>
                        <td>{{ $allocation->apInvoice?->purchaseOrder?->po_number ?? '-' }}</td>
                        <td>{{ $allocation->created_at?->format('d M Y H:i') }}</td>
                        <td>{{ $allocation->notes ?? '-' }}</td>
                        <td class="dms-money">Rp {{ number_format($allocation->amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="dms-empty">
                            <i class="bi bi-journal-text"></i>
                            <p>Belum ada alokasi invoice</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
        <div style="min-width: 320px;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Total Payment</span>
                <strong>Rp {{ number_format($supplierPayment->amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Belum Dialokasi</span>
                <strong>Rp {{ number_format($supplierPayment->unallocated_amount, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection
