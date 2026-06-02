@extends('layouts.sidebar')

@section('page-title', 'Usulan Pembelian')
@section('breadcrumb', 'Purchase Orders / Usulan')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Rekomendasi Pembelian</h3>
            <p class="dms-section-subtitle">Rekomendasi reorder berdasarkan stok, penjualan 30 hari terakhir, dan target week-cover.</p>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i>
            Kembali
        </a>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('purchase-orders.proposed') }}" method="GET" class="dms-search-form">
            <div class="form-group" style="margin: 0; min-width: 220px;">
                <label class="form-label">Target Week Cover</label>
                <input type="number" name="target_weeks" class="form-control" value="{{ $targetWeeks }}" min="1" max="12">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary" style="align-self: flex-end;">
                <i class="bi bi-arrow-clockwise"></i>
                Hitung Ulang
            </button>
        </form>
    </div>

    @include('reports._summary', ['items' => [
        ['label' => 'Produk Aktif', 'value' => number_format($proposalSummary['active_products']), 'icon' => 'bi-box-seam'],
        ['label' => 'Ada Penjualan 30 Hari', 'value' => number_format($proposalSummary['products_with_sales']), 'icon' => 'bi-graph-up'],
        ['label' => 'Di Bawah Min Stock', 'value' => number_format($proposalSummary['below_min_stock']), 'icon' => 'bi-exclamation-triangle', 'bg' => 'var(--k-orange-light)', 'color' => 'var(--k-orange)'],
        ['label' => 'Usulan Reorder', 'value' => number_format($proposalSummary['recommendations']), 'icon' => 'bi-lightbulb', 'bg' => 'var(--k-blue-light)', 'color' => 'var(--k-blue)'],
    ]])

    @if($recommendations->isEmpty())
        <div class="dms-empty-state">
            <i class="bi bi-check-circle"></i>
            <p>Tidak ada produk yang perlu reorder untuk target {{ $targetWeeks }} minggu.</p>
            <p style="max-width: 640px; margin: 0 auto; line-height: 1.6;">
                Stok saat ini masih memenuhi min stock dan target week-cover, atau belum ada histori penjualan shipped/delivered dalam 30 hari terakhir untuk dihitung sebagai demand.
            </p>
        </div>
    @else
        <form action="{{ route('purchase-orders.store') }}" method="POST">
            @csrf
            <input type="hidden" name="order_date" value="{{ now()->toDateString() }}">
            <input type="hidden" name="internal_notes" value="Dibuat dari Usulan Pembelian. Target week-cover: {{ $targetWeeks }} minggu.">

            <div class="dms-toolbar">
                <div class="form-group" style="margin: 0; min-width: 280px;">
                    <label class="form-label">Pemasok <span class="dms-required">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">-- Pilih Pemasok --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->category_label }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin: 0; min-width: 220px;">
                    <label class="form-label">Tanggal Perkiraan Datang</label>
                    <input type="date" name="expected_delivery_date" class="form-control" value="{{ now()->addDay()->toDateString() }}">
                </div>
                <div class="form-group" style="margin: 0; flex: 1 1 320px;">
                    <label class="form-label">Catatan Pemasok</label>
                    <input type="text" name="notes" class="form-control" value="PO dari usulan reorder DMS KURMIGO">
                </div>
            </div>

            <div class="dms-table-wrap">
                <table class="dms-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Stok</th>
                            <th>Terjual 30 Hari</th>
                            <th>Week Cover</th>
                            <th>Target</th>
                            <th>Qty Usulan</th>
                            <th>Harga Beli</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recommendations as $index => $item)
                            <tr>
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product']->id }}">
                                    <div class="dms-identity">
                                        <div class="dms-avatar-soft"><i class="bi bi-box-seam"></i></div>
                                        <div>
                                            <div class="dms-strong">{{ $item['product']->name }}</div>
                                            <div class="dms-muted">{{ $item['product']->category ?? '-' }} / {{ $item['product']->unit->symbol ?? $item['product']->unit->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ number_format($item['current_stock']) }}</td>
                                <td>{{ number_format($item['sold_last_30_days']) }}</td>
                                <td>
                                    @if(is_null($item['week_cover']))
                                        -
                                    @else
                                        {{ number_format($item['week_cover'], 1) }} minggu
                                    @endif
                                </td>
                                <td>{{ number_format($item['target_quantity']) }}</td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control" value="{{ $item['recommended_quantity'] }}" min="1" style="min-width: 90px;">
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][price]" class="form-control" value="{{ $item['estimated_price'] }}" min="0" step="100" style="min-width: 120px;">
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $index }}][notes]" class="form-control" value="Stok {{ $item['current_stock'] }}, target {{ $item['target_quantity'] }}, cover {{ is_null($item['week_cover']) ? '-' : number_format($item['week_cover'], 1) }} minggu">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="dms-form-actions" style="margin-top: 1rem;">
                <a href="{{ route('purchase-orders.index') }}" class="dms-btn dms-btn-outline">
                    <i class="bi bi-x-circle"></i>
                    Batal
                </a>
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-receipt"></i>
                    Buat PO dari Usulan
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
