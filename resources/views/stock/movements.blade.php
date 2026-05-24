@extends('layouts.sidebar')

@section('page-title', 'Stock Movement Log')
@section('breadcrumb', 'Stock / Movements')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-green); margin-bottom: 0.25rem;">
            <i class="bi bi-arrow-left-right" style="margin-right: 0.5rem;"></i>
            Stock Movement Log
        </h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">
            Riwayat lengkap pergerakan stok masuk dan keluar
        </p>
    </div>

    <!-- Filter Form -->
    <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
        <form action="{{ route('stock.movements') }}" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
            <!-- Filter Tanggal -->
            <div>
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div>
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            
            <!-- Filter Produk -->
            <div>
                <label class="form-label">Produk</label>
                <select name="product_id" class="form-control">
                    <option value="">-- Semua Produk --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Filter Jenis (In/Out/Adjustment) -->
            <div>
                <label class="form-label">Jenis</label>
                <select name="type" class="form-control">
                    <option value="">-- Semua Jenis --</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Filter Sumber -->
            <div>
                <label class="form-label">Sumber</label>
                <select name="source_type" class="form-control">
                    <option value="">-- Semua Sumber --</option>
                    @foreach($sourceTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('source_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Tombol Aksi -->
            <div style="display: flex; gap: 0.5rem; align-items: end;">
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('stock.movements') }}" class="dms-btn dms-btn-outline">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Export Button -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
        <button onclick="exportToExcel()" class="dms-btn dms-btn-outline">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export to Excel
        </button>
    </div>

    <!-- Movements Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                    <th style="padding: 0.6rem;">Tanggal & Waktu</th>
                    <th style="padding: 0.6rem;">Produk</th>
                    <th style="padding: 0.6rem;">Jenis</th>
                    <th style="padding: 0.6rem;">Jumlah</th>
                    <th style="padding: 0.6rem;">Stok Sebelum</th>
                    <th style="padding: 0.6rem;">Stok Sesudah</th>
                    <th style="padding: 0.6rem;">No. Referensi</th>
                    <th style="padding: 0.6rem;">Keterangan</th>
                    <th style="padding: 0.6rem;">Oleh</th>
                  </thead>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                <tr style="border-bottom: 1px solid var(--k-gray-200);">
                    <td style="padding: 0.6rem;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem;">{{ $movement->created_at->format('d M Y') }}</span>
                            <span style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $movement->created_at->format('H:i:s') }}</span>
                        </div>
                    </td>
                    <td style="padding: 0.6rem;">
                        <a href="{{ route('stock.show', $movement->product) }}" style="color: var(--k-green); text-decoration: none;">
                            {{ $movement->product->name ?? '-' }}
                        </a>
                    </td>
                    <td style="padding: 0.6rem;">
                        @if($movement->type == 'in')
                            <span class="dms-badge dms-badge-success">
                                <i class="bi bi-arrow-down"></i> Masuk
                            </span>
                        @elseif($movement->type == 'out')
                            <span class="dms-badge dms-badge-danger">
                                <i class="bi bi-arrow-up"></i> Keluar
                            </span>
                        @else
                            <span class="dms-badge dms-badge-warning">
                                <i class="bi bi-sliders2"></i> Penyesuaian
                            </span>
                        @endif
                    </td>
                    <td style="padding: 0.6rem;">
                        <span style="font-weight: 600; color: {{ $movement->type == 'in' ? 'var(--k-green)' : 'var(--k-red)' }}">
                            {{ $movement->type == 'in' ? '+' : '-' }}{{ number_format($movement->quantity) }}
                        </span>
                    </td>
                    <td style="padding: 0.6rem;">{{ number_format($movement->before_quantity) }}</td>
                    <td style="padding: 0.6rem;">{{ number_format($movement->after_quantity) }}</td>
                    <td style="padding: 0.6rem;">
                        @if($movement->source_type == 'direct_purchase' && $movement->directPurchase)
                            <a href="{{ route('direct-purchases.show', $movement->directPurchase) }}" style="color: var(--k-green); text-decoration: none;">
                                <i class="bi bi-cash"></i> {{ $movement->directPurchase->invoice_number }}
                            </a>
                        @elseif($movement->source_type == 'purchase_order' && $movement->purchaseOrder)
                            <a href="{{ route('purchase-orders.show', $movement->purchaseOrder) }}" style="color: var(--k-green); text-decoration: none;">
                                <i class="bi bi-receipt"></i> PO-{{ $movement->purchaseOrder->po_number }}
                            </a>
                        @elseif($movement->source_type == 'order' && $movement->order)
                            <a href="{{ route('orders.show', $movement->order) }}" style="color: var(--k-green); text-decoration: none;">
                                <i class="bi bi-cart"></i> #{{ $movement->order->order_number }}
                            </a>
                        @elseif($movement->source_type == 'foc_out' && $movement->outboundFoc)
                            <a href="{{ route('outbound-focs.show', $movement->outboundFoc) }}" style="color: var(--k-green); text-decoration: none;">
                                <i class="bi bi-gift"></i> FOC-{{ $movement->outboundFoc->foc_number }}
                            </a>
                        @elseif($movement->source_type == 'return_out' && $movement->outboundReturn)
                            <a href="{{ route('outbound-returns.show', $movement->outboundReturn) }}" style="color: var(--k-green); text-decoration: none;">
                                <i class="bi bi-arrow-return-left"></i> RET-{{ $movement->outboundReturn->return_number }}
                            </a>
                        @elseif($movement->source_type == 'foc')
                            <span class="dms-badge dms-badge-success">Bonus</span>
                        @elseif($movement->source_type == 'consignment')
                            <span class="dms-badge dms-badge-info">Titipan</span>
                        @elseif($movement->source_type == 'consignment_sale')
                            <span class="dms-badge dms-badge-info">Titipan Terjual</span>
                        @elseif($movement->source_type == 'consignment_return')
                            <span class="dms-badge dms-badge-warning">Titipan Return</span>
                        @elseif($movement->source_type == 'adjustment')
                            <span style="color: var(--k-gray-500);">-</span>
                        @else
                            <span class="dms-badge dms-badge-secondary">-</span>
                        @endif
                    </td>
                    <td style="padding: 0.6rem; max-width: 200px;">
                        <span style="font-size: 0.7rem; color: var(--k-gray-600);">{{ Str::limit($movement->reason ?? '-', 50) }}</span>
                    </td>
                    <td style="padding: 0.6rem;">
                        <span style="font-size: 0.7rem;">{{ $movement->createdBy->name ?? '-' }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="padding: 3rem; text-align: center;">
                        <i class="bi bi-inbox" style="font-size: 2.5rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 0.75rem; color: var(--k-gray-500);">Belum ada data pergerakan stok</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination & Info -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.8rem; color: var(--k-gray-500);">
            Menampilkan {{ $movements->firstItem() ?? 0 }} - {{ $movements->lastItem() ?? 0 }} dari {{ $movements->total() }} data
        </div>
        <div>
            {{ $movements->withQueryString()->links() }}
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    let url = "{{ route('stock.movements') }}?export=excel&" + window.location.search.substring(1);
    window.location.href = url;
}
</script>

<style>
.form-label {
    display: block;
    margin-bottom: 0.3rem;
    color: var(--k-gray-700);
    font-size: 0.7rem;
    font-weight: 500;
}
.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 6px;
    font-size: 0.8rem;
    transition: all 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: var(--k-green);
    box-shadow: 0 0 0 2px var(--k-green-light);
}
.pagination {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}
.pagination li {
    display: inline-block;
}
.pagination li a, .pagination li span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.5rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 8px;
    color: var(--k-gray-600);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.2s;
}
.pagination li.active span {
    background: var(--k-green);
    color: white;
    border-color: var(--k-green);
}
.pagination li a:hover {
    background: var(--k-gray-100);
    border-color: var(--k-green);
}
.dms-table th {
    background: var(--k-gray-100);
    font-weight: 600;
    font-size: 0.7rem;
}
.dms-table td {
    font-size: 0.75rem;
}
</style>
@endsection