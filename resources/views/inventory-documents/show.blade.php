@extends('layouts.sidebar')

@section('page-title', 'Detail Dokumen Stok')
@section('breadcrumb', 'Inventori / Dokumen Stok / Detail')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $document->document_number }}</h3>
            <p class="dms-section-subtitle">{{ $document->type_label }}</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
            <span class="dms-badge {{ $document->status_badge }}" style="align-self: center;">{{ $document->status_label }}</span>
            <a href="{{ route('inventory-documents.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            @can('manage warehouse')
                @if($document->status === \App\Models\InventoryDocument::STATUS_DRAFT)
                    <form action="{{ route('inventory-documents.post', $document) }}" method="POST">
                        @csrf
                        <button type="submit" class="dms-btn dms-btn-primary">
                            <i class="bi bi-check-circle"></i> Posting
                        </button>
                    </form>
                @elseif($document->status === \App\Models\InventoryDocument::STATUS_POSTED)
                    <form action="{{ route('inventory-documents.void', $document) }}" method="POST" style="display: flex; gap: 0.45rem;">
                        @csrf
                        <input type="text" name="void_reason" class="form-control" placeholder="Alasan void" required style="min-width: 220px;">
                        <button type="submit" class="dms-btn dms-btn-outline">
                            <i class="bi bi-x-circle"></i> Void
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
        <div class="dms-stat-card">
            <div class="dms-stat-label">Tanggal</div>
            <div class="dms-stat-value" style="font-size: 1rem;">{{ optional($document->document_date)->format('d M Y') }}</div>
        </div>
        <div class="dms-stat-card">
            <div class="dms-stat-label">{{ $document->type === \App\Models\InventoryDocument::TYPE_TRANSFER ? 'Gudang Asal' : 'Gudang' }}</div>
            <div class="dms-stat-value" style="font-size: 1rem;">{{ $document->warehouse?->name ?? '-' }}</div>
        </div>
        @if($document->type === \App\Models\InventoryDocument::TYPE_TRANSFER)
            <div class="dms-stat-card">
                <div class="dms-stat-label">Gudang Tujuan</div>
                <div class="dms-stat-value" style="font-size: 1rem;">{{ $document->transferToWarehouse?->name ?? '-' }}</div>
            </div>
        @endif
        <div class="dms-stat-card">
            <div class="dms-stat-label">Cabang</div>
            <div class="dms-stat-value" style="font-size: 1rem;">{{ $document->companyBranch?->name ?? 'Global' }}</div>
        </div>
        <div class="dms-stat-card">
            <div class="dms-stat-label">Referensi</div>
            <div class="dms-stat-value" style="font-size: 1rem;">{{ $document->reference_number ?: '-' }}</div>
        </div>
    </div>

    @if($document->notes)
        <div style="margin-bottom: 1rem; padding: 0.75rem; background: var(--k-gray-50); border: 1px solid var(--k-gray-200); border-radius: 8px; color: var(--k-gray-700);">
            {{ $document->notes }}
        </div>
    @endif

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th style="text-align: right;">Qty</th>
                    <th style="text-align: right;">Nilai / Unit</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($document->items as $item)
                    <tr>
                        <td>
                            <div class="dms-strong">{{ $item->product?->name ?? '-' }}</div>
                            <small style="color: var(--k-gray-500);">{{ $item->product?->formatted_unit ?? '-' }}</small>
                        </td>
                        <td style="text-align: right;" class="dms-strong">{{ number_format($item->quantity) }}</td>
                        <td style="text-align: right;">{{ $item->unit_cost ? 'Rp '.number_format($item->unit_cost, 0, ',', '.') : '-' }}</td>
                        <td>{{ $item->notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem; color: var(--k-gray-500); font-size: var(--k-font-sm);">
        Dibuat oleh {{ $document->creator?->name ?? '-' }}.
        @if($document->posted_at)
            Diposting {{ $document->posted_at->format('d M Y H:i') }} oleh {{ $document->postedBy?->name ?? '-' }}.
        @endif
        @if($document->voided_at)
            Divoid {{ $document->voided_at->format('d M Y H:i') }} oleh {{ $document->voidedBy?->name ?? '-' }}. Alasan: {{ $document->void_reason }}.
        @endif
    </div>
</div>
@endsection
