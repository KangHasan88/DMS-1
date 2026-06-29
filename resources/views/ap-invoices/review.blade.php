@extends('layouts.sidebar')

@section('page-title', 'Review AP Invoice')
@section('breadcrumb', 'Finance / Invoice AP / Review')

@section('content')
<div class="dms-card">
    <div class="dms-section-header" style="align-items: flex-start; gap: 1rem;">
        <div>
            <h3 class="dms-section-title">Review AP Invoice</h3>
            <p class="dms-section-subtitle">
                Cocokkan PO, penerimaan barang, dan nilai invoice sebelum hutang supplier diposting.
            </p>
        </div>
        <span class="dms-badge dms-badge-{{ $isMatched ? 'success' : 'warning' }}">
            {{ $isMatched ? 'Matched' : 'Ada Selisih Qty' }}
        </span>
    </div>

    <div class="dms-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 1rem;">
        <div class="dms-stat-card">
            <span class="dms-stat-label">Purchase Order</span>
            <strong class="dms-stat-value" style="font-size: 1.05rem;">{{ $purchaseOrder->po_number }}</strong>
            <span class="dms-stat-subtitle">{{ $purchaseOrder->supplier?->name ?? '-' }}</span>
        </div>
        <div class="dms-stat-card">
            <span class="dms-stat-label">Tanggal Terima</span>
            <strong class="dms-stat-value" style="font-size: 1.05rem;">{{ $purchaseOrder->received_date?->format('d M Y') ?? '-' }}</strong>
            <span class="dms-stat-subtitle">{{ $purchaseOrder->companyBranch?->name ?? 'Global / tanpa cabang' }}</span>
        </div>
        <div class="dms-stat-card">
            <span class="dms-stat-label">Nilai PO</span>
            <strong class="dms-stat-value dms-money" style="font-size: 1.05rem;">Rp {{ number_format($orderedSubtotal, 0, ',', '.') }}</strong>
            <span class="dms-stat-subtitle">Nilai order awal</span>
        </div>
        <div class="dms-stat-card">
            <span class="dms-stat-label">Nilai Diterima</span>
            <strong class="dms-stat-value dms-money" style="font-size: 1.05rem;">Rp {{ number_format($receivedSubtotal, 0, ',', '.') }}</strong>
            <span class="dms-stat-subtitle">Dasar AP invoice</span>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Qty PO</th>
                    <th>Qty Diterima</th>
                    <th>Satuan</th>
                    <th>Harga Beli</th>
                    <th>Line Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                    @php
                        $lineTotal = (int) $item->received_quantity * (int) $item->price;
                        $lineMatched = (int) $item->received_quantity === (int) $item->quantity;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $item->product?->name ?? 'Item PO' }}</strong>
                        </td>
                        <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                        <td>{{ number_format($item->received_quantity, 0, ',', '.') }}</td>
                        <td>{{ $item->product?->unit?->name ?? '-' }}</td>
                        <td class="dms-money">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($lineTotal, 0, ',', '.') }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $lineMatched ? 'success' : 'warning' }}">
                                {{ $lineMatched ? 'Match' : 'Selisih' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="dms-form-actions" style="justify-content: space-between; margin-top: 1rem;">
        <a href="{{ route('ap-invoices.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <form action="{{ route('ap-invoices.store') }}" method="POST" style="margin: 0;">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-journal-check"></i> Terbitkan AP Invoice
            </button>
        </form>
    </div>
</div>
@endsection
