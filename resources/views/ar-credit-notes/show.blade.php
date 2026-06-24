@extends('layouts.sidebar')

@section('page-title', 'Detail Credit Note AR')
@section('breadcrumb', 'Finance / Credit Note AR / Detail')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $arCreditNote->note_number }}</h3>
            <p class="dms-section-subtitle">Koreksi untuk invoice {{ $arCreditNote->arInvoice?->invoice_number ?? '-' }}.</p>
        </div>
        <div class="dms-toolbar-actions">
            @can('create invoice')
                @if($arCreditNote->status !== \App\Models\ArCreditNote::STATUS_VOID)
                    <button type="button" class="dms-btn dms-btn-outline" onclick="document.getElementById('void-ar-credit-note-form').classList.toggle('d-none')">
                        <i class="bi bi-x-circle"></i>
                        Void
                    </button>
                @endif
            @endcan
            @if($arCreditNote->arInvoice)
                <a href="{{ route('ar-invoices.show', $arCreditNote->arInvoice) }}" class="dms-btn dms-btn-outline">
                    <i class="bi bi-receipt"></i>
                    Lihat Invoice
                </a>
            @endif
            <a href="{{ route('ar-credit-notes.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>

    @if($arCreditNote->status === \App\Models\ArCreditNote::STATUS_VOID)
        <div class="dms-alert dms-alert-warning">
            <strong>Credit note AR void.</strong> {{ $arCreditNote->void_reason ?: 'Tanpa alasan.' }}
        </div>
    @endif

    @can('create invoice')
        @if($arCreditNote->status !== \App\Models\ArCreditNote::STATUS_VOID)
            <form id="void-ar-credit-note-form" action="{{ route('ar-credit-notes.void', $arCreditNote) }}" method="POST" class="dms-form-section d-none" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
                @csrf
                <div class="dms-form-grid" style="align-items: end;">
                    <div class="form-group dms-form-span-2">
                        <label class="form-label">Alasan Void <span class="dms-required">*</span></label>
                        <input type="text" name="void_reason" class="form-control" maxlength="500" required placeholder="Contoh: Salah nominal koreksi">
                        @error('void_reason') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <button type="submit" class="dms-btn dms-btn-primary" onclick="return confirm('Void credit note ini dan buat jurnal reversal?')">
                            <i class="bi bi-check2-circle"></i> Proses Void
                        </button>
                    </div>
                </div>
            </form>
        @endif
    @endcan

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Customer</span>
            <strong>{{ $arCreditNote->customer?->name ?? $arCreditNote->customerUser?->name ?? '-' }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Tanggal</span>
            <strong>{{ $arCreditNote->note_date?->format('d M Y') ?? '-' }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Alasan</span>
            <strong>{{ $arCreditNote->reason_label }}</strong>
        </div>
        <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem;">
            <span style="display: block; color: var(--k-gray-500); font-size: 0.75rem; margin-bottom: 0.35rem;">Status</span>
            <span class="dms-badge dms-badge-{{ $arCreditNote->status_badge }}">{{ $arCreditNote->status_label }}</span>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end;">
        <div style="min-width: 320px;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Nominal Koreksi</span>
                <strong class="dms-money">Rp {{ number_format($arCreditNote->amount, 0, ',', '.') }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Referensi</span>
                <strong>{{ $arCreditNote->reference_number ?: '-' }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.35rem 0;">
                <span>Diposting Oleh</span>
                <strong>{{ $arCreditNote->postedBy?->name ?? '-' }}</strong>
            </div>
        </div>
    </div>

    @if($arCreditNote->notes)
        <div class="dms-alert dms-alert-info" style="margin-top: 1rem;">
            {{ $arCreditNote->notes }}
        </div>
    @endif
</div>
@endsection
