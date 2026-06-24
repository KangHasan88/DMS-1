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
            @can('process payment')
                @if($supplierPayment->status !== \App\Models\SupplierPayment::STATUS_VOID)
                    <button type="button" class="dms-btn dms-btn-outline" onclick="document.getElementById('void-supplier-payment-form').classList.toggle('d-none')">
                        <i class="bi bi-x-circle"></i>
                        Void
                    </button>
                @endif
            @endcan
            <a href="{{ route('supplier-payments.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>

    @if($supplierPayment->status === \App\Models\SupplierPayment::STATUS_VOID)
        <div class="dms-alert dms-alert-warning">
            <strong>Payment supplier void.</strong> {{ $supplierPayment->void_reason ?: 'Tanpa alasan.' }}
        </div>
    @endif

    @can('process payment')
        @if($supplierPayment->status !== \App\Models\SupplierPayment::STATUS_VOID)
            <form id="void-supplier-payment-form" action="{{ route('supplier-payments.void', $supplierPayment) }}" method="POST" class="dms-form-section d-none" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
                @csrf
                <div class="dms-form-grid" style="align-items: end;">
                    <div class="form-group dms-form-span-2">
                        <label class="form-label">Alasan Void <span class="dms-required">*</span></label>
                        <input type="text" name="void_reason" class="form-control" maxlength="500" required placeholder="Contoh: Salah input nominal atau referensi">
                        @error('void_reason') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <button type="submit" class="dms-btn dms-btn-primary" onclick="return confirm('Void payment ini dan buat jurnal reversal?')">
                            <i class="bi bi-check2-circle"></i> Proses Void
                        </button>
                    </div>
                </div>
            </form>
        @endif
    @endcan

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
            <span class="dms-badge dms-badge-{{ $supplierPayment->status_badge }}">
                {{ $supplierPayment->status_label }}
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
