@extends('layouts.sidebar')

@section('page-title', 'Detail Invoice AR')
@section('breadcrumb', 'Finance / Invoice AR / Detail')

@section('content')
@php
    $hasActivePayment = $arInvoice->paymentAllocations->contains(fn ($allocation) => $allocation->customerPayment?->status !== \App\Models\CustomerPayment::STATUS_VOID);
    $hasActiveCreditNote = $arInvoice->creditNotes->contains(fn ($creditNote) => $creditNote->status !== \App\Models\ArCreditNote::STATUS_VOID);
@endphp
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $arInvoice->invoice_number }}</h3>
            <p class="dms-section-subtitle">Invoice dari order {{ $arInvoice->order?->order_number ?? '-' }}.</p>
        </div>
        <div class="dms-toolbar-actions">
            @can('create invoice')
                @if($arInvoice->status !== \App\Models\ArInvoice::STATUS_VOID && !$hasActivePayment && !$hasActiveCreditNote)
                    <button type="button" class="dms-btn dms-btn-outline" onclick="document.getElementById('void-ar-invoice-form').classList.toggle('d-none')">
                        <i class="bi bi-x-circle"></i>
                        Void
                    </button>
                @endif
            @endcan
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

    @if($arInvoice->status === \App\Models\ArInvoice::STATUS_VOID)
        <div class="dms-alert dms-alert-warning">
            <strong>Invoice void.</strong> {{ $arInvoice->void_reason ?: 'Tanpa alasan.' }}
        </div>
    @endif

    @can('create invoice')
        @if($arInvoice->status !== \App\Models\ArInvoice::STATUS_VOID && !$hasActivePayment && !$hasActiveCreditNote)
            <form id="void-ar-invoice-form" action="{{ route('ar-invoices.void', $arInvoice) }}" method="POST" class="dms-form-section d-none" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
                @csrf
                <div class="dms-form-grid" style="align-items: end;">
                    <div class="form-group dms-form-span-2">
                        <label class="form-label">Alasan Void <span class="dms-required">*</span></label>
                        <input type="text" name="void_reason" class="form-control" maxlength="500" required placeholder="Contoh: Salah terbit invoice">
                        @error('void_reason') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <button type="submit" class="dms-btn dms-btn-primary" onclick="return confirm('Void invoice ini dan buat jurnal reversal?')">
                            <i class="bi bi-check2-circle"></i> Proses Void
                        </button>
                    </div>
                </div>
            </form>
        @endif
    @endcan

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
                <span>Credit Note</span>
                <strong>Rp {{ number_format($arInvoice->credit_note_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Outstanding</span>
                <strong>Rp {{ number_format($arInvoice->outstanding_amount, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>
</div>

@can('create invoice')
    @if($arInvoice->outstanding_amount > 0 && $arInvoice->status !== \App\Models\ArInvoice::STATUS_VOID)
        <div class="dms-card" style="margin-top: 1rem;">
            <div class="dms-section-header">
                <div>
                    <h3 class="dms-section-title">Catat Credit Note AR</h3>
                    <p class="dms-section-subtitle">Gunakan untuk retur penjualan, koreksi harga, diskon customer, atau klaim barang rusak.</p>
                </div>
            </div>

            <form action="{{ route('ar-credit-notes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="ar_invoice_id" value="{{ $arInvoice->id }}">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                    <div>
                        <label class="form-label">Tanggal Credit Note <span class="dms-required">*</span></label>
                        <input type="date" name="note_date" value="{{ old('note_date', now()->toDateString()) }}" class="form-control" required>
                        @error('note_date') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="form-label">Alasan <span class="dms-required">*</span></label>
                        <select name="reason_type" class="form-control" required>
                            @foreach(\App\Models\ArCreditNote::REASON_LIST as $key => $label)
                                <option value="{{ $key }}" {{ old('reason_type', \App\Models\ArCreditNote::REASON_SALES_RETURN) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason_type') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="form-label">Nominal <span class="dms-required">*</span></label>
                        <input type="number" name="amount" min="1" max="{{ $arInvoice->outstanding_amount }}" value="{{ old('amount') }}" class="form-control" required placeholder="Maks. {{ number_format($arInvoice->outstanding_amount, 0, ',', '.') }}">
                        @error('amount') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="form-label">No. Referensi</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control" placeholder="Opsional">
                        @error('reference_number') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: end; margin-top: 0.75rem;">
                    <div style="flex: 1;">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Contoh: Retur 1 karton karena rusak">
                        @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-receipt-cutoff"></i>
                        Posting Credit Note
                    </button>
                </div>
            </form>
        </div>
    @endif
@endcan

@can('process payment')
    @if($arInvoice->outstanding_amount > 0 && $arInvoice->status !== \App\Models\ArInvoice::STATUS_VOID)
        <div class="dms-card" style="margin-top: 1rem;">
            <div class="dms-section-header">
                <div>
                    <h3 class="dms-section-title">Catat Pembayaran</h3>
                    <p class="dms-section-subtitle">Input pembayaran customer dan alokasikan langsung ke invoice ini.</p>
                </div>
            </div>

            <form action="{{ route('customer-payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="ar_invoice_id" value="{{ $arInvoice->id }}">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                    <div>
                        <label class="form-label">Tanggal Bayar</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Metode</label>
                        <select name="payment_method" class="form-control" required>
                            @foreach(\App\Models\CustomerPayment::METHOD_LIST as $key => $label)
                                <option value="{{ $key }}" {{ old('payment_method', \App\Models\CustomerPayment::METHOD_TRANSFER) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nominal</label>
                        <input type="number" name="amount" min="1" max="{{ $arInvoice->outstanding_amount }}" value="{{ old('amount', $arInvoice->outstanding_amount) }}" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">No. Referensi</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control" placeholder="Opsional">
                    </div>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: end; margin-top: 0.75rem;">
                    <div style="flex: 1;">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Opsional">
                    </div>
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-cash-coin"></i>
                        Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    @endif
@endcan

<div class="dms-card" style="margin-top: 1rem;">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Riwayat Pembayaran</h3>
            <p class="dms-section-subtitle">Daftar payment yang sudah dialokasikan ke invoice ini.</p>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Payment</th>
                    <th>Tanggal</th>
                    <th>Metode</th>
                    <th>Referensi</th>
                    <th>Dicatat Oleh</th>
                    <th>Status</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($arInvoice->paymentAllocations as $allocation)
                    <tr>
                        <td>
                            <a href="{{ route('customer-payments.show', $allocation->customerPayment) }}">
                                <strong>{{ $allocation->customerPayment?->payment_number ?? '-' }}</strong>
                            </a>
                        </td>
                        <td>{{ $allocation->customerPayment?->payment_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $allocation->customerPayment?->method_label ?? '-' }}</td>
                        <td>{{ $allocation->customerPayment?->reference_number ?? '-' }}</td>
                        <td>{{ $allocation->customerPayment?->receivedBy?->name ?? '-' }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $allocation->customerPayment?->status_badge ?? 'secondary' }}">
                                {{ $allocation->customerPayment?->status_label ?? '-' }}
                            </span>
                        </td>
                        <td class="dms-money">Rp {{ number_format($allocation->amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="dms-empty">
                            <i class="bi bi-cash-coin"></i>
                            <p>Belum ada pembayaran</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="dms-card" style="margin-top: 1rem;">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Riwayat Credit Note AR</h3>
            <p class="dms-section-subtitle">Koreksi pengurang piutang yang sudah diposting untuk invoice ini.</p>
        </div>
        <a href="{{ route('ar-credit-notes.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-list-ul"></i>
            Lihat Semua
        </a>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Credit Note</th>
                    <th>Tanggal</th>
                    <th>Alasan</th>
                    <th>Referensi</th>
                    <th>Status</th>
                    <th>Nominal</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($arInvoice->creditNotes as $creditNote)
                    <tr>
                        <td><strong>{{ $creditNote->note_number }}</strong></td>
                        <td>{{ $creditNote->note_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $creditNote->reason_label }}</td>
                        <td>{{ $creditNote->reference_number ?: '-' }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $creditNote->status_badge }}">
                                {{ $creditNote->status_label }}
                            </span>
                        </td>
                        <td class="dms-money">Rp {{ number_format($creditNote->amount, 0, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('ar-credit-notes.show', $creditNote) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="dms-empty">
                            <i class="bi bi-receipt-cutoff"></i>
                            <p>Belum ada credit note AR</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
