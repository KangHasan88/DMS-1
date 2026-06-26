@extends('layouts.sidebar')

@section('page-title', 'Laporan Principal')
@section('breadcrumb', 'Laporan / Principal')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Laporan Principal</h3>
            <p class="dms-section-subtitle">Pantau kontribusi penjualan, pembelian, stok, dan margin per principal.</p>
        </div>
    </div>

    <form method="GET" class="dms-toolbar" style="align-items: end;">
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: end; flex: 1;">
            <div>
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="form-control">
            </div>
            <div>
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="form-control">
            </div>
            <div style="min-width: 220px;">
                <label class="form-label">Principal</label>
                <select name="principal_id" class="form-control">
                    <option value="">Semua Principal</option>
                    @foreach($principalOptions as $principal)
                        <option value="{{ $principal->id }}" {{ (string) $selectedPrincipalId === (string) $principal->id ? 'selected' : '' }}>
                            {{ $principal->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </form>

    @include('reports._summary', ['items' => [
        ['label' => 'Principal Aktif', 'value' => number_format($summary['principal_count']), 'icon' => 'bi-building'],
        ['label' => 'Produk Principal', 'value' => number_format($summary['product_count']), 'icon' => 'bi-box-seam'],
        ['label' => 'Penjualan', 'value' => 'Rp ' . number_format($summary['sales_amount'], 0, ',', '.'), 'icon' => 'bi-graph-up'],
        ['label' => 'Pembelian', 'value' => 'Rp ' . number_format($summary['purchase_amount'], 0, ',', '.'), 'icon' => 'bi-cart-check'],
        ['label' => 'Stok Unit', 'value' => number_format($summary['stock_qty']), 'icon' => 'bi-boxes'],
        ['label' => 'Margin Est.', 'value' => 'Rp ' . number_format($summary['margin'], 0, ',', '.'), 'icon' => 'bi-percent'],
    ]])

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Principal</th>
                    <th>Produk</th>
                    <th>Supplier</th>
                    <th>Sales Qty</th>
                    <th>Penjualan</th>
                    <th>Purchase Qty</th>
                    <th>Pembelian</th>
                    <th>Stok</th>
                    <th>Margin Est.</th>
                </tr>
            </thead>
            <tbody>
                @forelse($principals as $principal)
                    <tr>
                        <td>
                            <div class="dms-strong">{{ $principal->name }}</div>
                            <small style="color: var(--k-gray-500);">{{ $principal->code }}</small>
                        </td>
                        <td>
                            <div>{{ number_format($principal->active_product_count) }} aktif</div>
                            <small style="color: var(--k-gray-500);">{{ number_format($principal->product_count) }} total</small>
                        </td>
                        <td>{{ number_format($principal->suppliers_count) }}</td>
                        <td>
                            <div>{{ number_format($principal->sales_qty) }}</div>
                            <small style="color: var(--k-gray-500);">{{ number_format($principal->order_count) }} order</small>
                        </td>
                        <td class="dms-money">Rp {{ number_format($principal->sales_amount, 0, ',', '.') }}</td>
                        <td>
                            <div>{{ number_format($principal->purchase_qty) }}</div>
                            <small style="color: var(--k-gray-500);">{{ number_format($principal->po_count) }} PO</small>
                        </td>
                        <td>Rp {{ number_format($principal->purchase_amount, 0, ',', '.') }}</td>
                        <td>
                            <div>{{ number_format($principal->stock_qty) }}</div>
                            @if($principal->consignment_qty > 0)
                                <small style="color: var(--k-gray-500);">Konsinyasi {{ number_format($principal->consignment_qty) }}</small>
                            @endif
                        </td>
                        <td>
                            <div class="{{ $principal->gross_margin >= 0 ? 'dms-money' : '' }}">
                                Rp {{ number_format($principal->gross_margin, 0, ',', '.') }}
                            </div>
                            <small style="color: var(--k-gray-500);">
                                {{ $principal->gross_margin_percent === null ? '-' : number_format($principal->gross_margin_percent, 1, ',', '.') . '%' }}
                            </small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2.5rem; color: var(--k-gray-500);">
                            Belum ada data principal pada filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
