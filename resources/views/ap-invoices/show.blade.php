@extends('layouts.sidebar')

@section('page-title', 'Detail Invoice AP')
@section('breadcrumb', 'Finance / Invoice AP / Detail')

@section('content')
@php
    $hasActivePayment = $apInvoice->paymentAllocations->contains(fn ($allocation) => $allocation->supplierPayment?->status !== \App\Models\SupplierPayment::STATUS_VOID);
    $hasActiveDebitNote = $apInvoice->debitNotes->contains(fn ($debitNote) => $debitNote->status !== \App\Models\ApDebitNote::STATUS_VOID);
@endphp
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $apInvoice->invoice_number }}</h3>
            <p class="dms-section-subtitle">Invoice dari PO {{ $apInvoice->purchaseOrder?->po_number ?? '-' }}.</p>
        </div>
        <div class="dms-toolbar-actions">
            @can('create invoice')
                @if($apInvoice->status !== \App\Models\ApInvoice::STATUS_VOID && !$hasActivePayment && !$hasActiveDebitNote)
                    <button type="button" class="dms-btn dms-btn-outline" onclick="document.getElementById('void-ap-invoice-form').classList.toggle('d-none')">
                        <i class="bi bi-x-circle"></i>
                        Void
                    </button>
                @endif
            @endcan
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

    @if($apInvoice->status === \App\Models\ApInvoice::STATUS_VOID)
        <div class="dms-alert dms-alert-warning">
            <strong>Invoice AP void.</strong> {{ $apInvoice->void_reason ?: 'Tanpa alasan.' }}
        </div>
    @endif

    @can('create invoice')
        @if($apInvoice->status !== \App\Models\ApInvoice::STATUS_VOID && !$hasActivePayment && !$hasActiveDebitNote)
            <form id="void-ap-invoice-form" action="{{ route('ap-invoices.void', $apInvoice) }}" method="POST" class="dms-form-section d-none" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
                @csrf
                <div class="dms-form-grid" style="align-items: end;">
                    <div class="form-group dms-form-span-2">
                        <label class="form-label">Alasan Void <span class="dms-required">*</span></label>
                        <input type="text" name="void_reason" class="form-control" maxlength="500" required placeholder="Contoh: Salah terbit AP invoice">
                        @error('void_reason') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <button type="submit" class="dms-btn dms-btn-primary" onclick="return confirm('Void AP invoice ini dan buat jurnal reversal?')">
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
                <span>Debit Note</span>
                <strong>Rp {{ number_format($apInvoice->debit_note_amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Outstanding</span>
                <strong>Rp {{ number_format($apInvoice->outstanding_amount, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>
</div>

@can('create invoice')
    @if($apInvoice->outstanding_amount > 0 && $apInvoice->status !== \App\Models\ApInvoice::STATUS_VOID)
        <div class="dms-card" style="margin-top: 1rem;">
            <div class="dms-section-header">
                <div>
                    <h3 class="dms-section-title">Catat Debit Note AP</h3>
                    <p class="dms-section-subtitle">Gunakan untuk retur pembelian, koreksi harga, diskon supplier, atau barang rusak.</p>
                </div>
            </div>

            <form action="{{ route('ap-debit-notes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="ap_invoice_id" value="{{ $apInvoice->id }}">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                    <div>
                        <label class="form-label">Tanggal Debit Note <span class="dms-required">*</span></label>
                        <input type="date" name="note_date" value="{{ old('note_date', now()->toDateString()) }}" class="form-control" required>
                        @error('note_date') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="form-label">Alasan <span class="dms-required">*</span></label>
                        <select name="reason_type" class="form-control" required>
                            @foreach(\App\Models\ApDebitNote::REASON_LIST as $key => $label)
                                <option value="{{ $key }}" {{ old('reason_type', \App\Models\ApDebitNote::REASON_PURCHASE_RETURN) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason_type') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="form-label">Nominal <span class="dms-required">*</span></label>
                        <input type="number" name="amount" min="1" max="{{ $apInvoice->outstanding_amount }}" value="{{ old('amount') }}" class="form-control" required placeholder="Maks. {{ number_format($apInvoice->outstanding_amount, 0, ',', '.') }}">
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
                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Contoh: Retur 2 karton karena rusak">
                        @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-receipt-cutoff"></i>
                        Posting Debit Note
                    </button>
                </div>
            </form>
        </div>
    @endif
@endcan

@can('process payment')
    @if($apInvoice->outstanding_amount > 0 && $apInvoice->status !== \App\Models\ApInvoice::STATUS_VOID)
        <div class="dms-card" style="margin-top: 1rem;">
            <div class="dms-section-header">
                <div>
                    <h3 class="dms-section-title">Catat Pembayaran Supplier</h3>
                    <p class="dms-section-subtitle">Input pembayaran supplier dan alokasikan langsung ke invoice ini.</p>
                </div>
            </div>

            <form action="{{ route('supplier-payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="ap_invoice_id" value="{{ $apInvoice->id }}">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                    <div>
                        <label class="form-label">Tanggal Bayar</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Metode</label>
                        <select name="payment_method" class="form-control" required>
                            @foreach(\App\Models\SupplierPayment::METHOD_LIST as $key => $label)
                                <option value="{{ $key }}" {{ old('payment_method', \App\Models\SupplierPayment::METHOD_TRANSFER) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nominal</label>
                        <input type="number" name="amount" min="1" max="{{ $apInvoice->outstanding_amount }}" value="{{ old('amount', $apInvoice->outstanding_amount) }}" class="form-control" required>
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
                        <i class="bi bi-bank"></i>
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
            <h3 class="dms-section-title">Riwayat Pembayaran Supplier</h3>
            <p class="dms-section-subtitle">Daftar payment yang sudah dialokasikan ke invoice AP ini.</p>
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
                @forelse($apInvoice->paymentAllocations as $allocation)
                    <tr>
                        <td>
                            <a href="{{ route('supplier-payments.show', $allocation->supplierPayment) }}">
                                <strong>{{ $allocation->supplierPayment?->payment_number ?? '-' }}</strong>
                            </a>
                        </td>
                        <td>{{ $allocation->supplierPayment?->payment_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $allocation->supplierPayment?->method_label ?? '-' }}</td>
                        <td>{{ $allocation->supplierPayment?->reference_number ?? '-' }}</td>
                        <td>{{ $allocation->supplierPayment?->paidBy?->name ?? '-' }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $allocation->supplierPayment?->status_badge ?? 'secondary' }}">
                                {{ $allocation->supplierPayment?->status_label ?? '-' }}
                            </span>
                        </td>
                        <td class="dms-money">Rp {{ number_format($allocation->amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="dms-empty">
                            <i class="bi bi-bank"></i>
                            <p>Belum ada pembayaran supplier</p>
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
            <h3 class="dms-section-title">Riwayat Debit Note AP</h3>
            <p class="dms-section-subtitle">Koreksi pengurang hutang yang sudah diposting untuk invoice ini.</p>
        </div>
        <a href="{{ route('ap-debit-notes.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-list-ul"></i>
            Lihat Semua
        </a>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Debit Note</th>
                    <th>Tanggal</th>
                    <th>Alasan</th>
                    <th>Referensi</th>
                    <th>Status</th>
                    <th>Nominal</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($apInvoice->debitNotes as $debitNote)
                    <tr>
                        <td><strong>{{ $debitNote->note_number }}</strong></td>
                        <td>{{ $debitNote->note_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $debitNote->reason_label }}</td>
                        <td>{{ $debitNote->reference_number ?: '-' }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $debitNote->status_badge }}">
                                {{ $debitNote->status_label }}
                            </span>
                        </td>
                        <td class="dms-money">Rp {{ number_format($debitNote->amount, 0, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('ap-debit-notes.show', $debitNote) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="dms-empty">
                            <i class="bi bi-receipt-cutoff"></i>
                            <p>Belum ada debit note AP</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
