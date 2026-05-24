@extends('layouts.sidebar')

@section('page-title', 'Detail Stok Produk')
@section('breadcrumb', 'Stock / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-box-seam" style="color: var(--k-green);"></i>
                Detail Stok Produk
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                {{ $product->name }} ({{ $product->unit->name ?? '-' }})
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('stock.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Stock Info Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="padding: 1rem; background: var(--k-green-light); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Stok Saat Ini</div>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--k-green);">
                {{ number_format($stock ? $stock->quantity : 0) }}
            </div>
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">{{ $product->unit->name ?? '-' }}</div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-100); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Minimal Stok</div>
            <div style="font-size: 1.5rem; font-weight: 600; color: var(--k-gray-700);">
                {{ number_format($stock ? $stock->min_stock : 0) }}
            </div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-100); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Maksimal Stok</div>
            <div style="font-size: 1.5rem; font-weight: 600; color: var(--k-gray-700);">
                {{ $stock && $stock->max_stock ? number_format($stock->max_stock) : '-' }}
            </div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-100); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Status</div>
            <div style="font-size: 1rem; font-weight: 600; margin-top: 0.5rem;">
                @php
                    $quantity = $stock ? $stock->quantity : 0;
                    $minStock = $stock ? $stock->min_stock : 0;
                @endphp
                @if($quantity == 0)
                    <span class="dms-badge dms-badge-danger">Stok Habis</span>
                @elseif($quantity <= $minStock)
                    <span class="dms-badge dms-badge-warning">Stok Menipis</span>
                @else
                    <span class="dms-badge dms-badge-success">Tersedia</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <a href="{{ route('stock.add-form', $product) }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Stok
        </a>
        <a href="{{ route('stock.reduce-form', $product) }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-dash-circle"></i> Kurangi Stok
        </a>
        <a href="{{ route('stock.adjustment-form', $product) }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-sliders2"></i> Penyesuaian Stok
        </a>
    </div>

    <!-- Stock Movement History -->
    <div>
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-clock-history" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Riwayat Pergerakan Stok
        </h4>
        
        <div style="overflow-x: auto;">
            <table class="dms-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                        <th style="padding: 0.75rem; text-align: left;">Tanggal</th>
                        <th style="padding: 0.75rem; text-align: left;">Jenis</th>
                        <th style="padding: 0.75rem; text-align: center;">Jumlah</th>
                        <th style="padding: 0.75rem; text-align: center;">Sebelum</th>
                        <th style="padding: 0.75rem; text-align: center;">Sesudah</th>
                        <th style="padding: 0.75rem; text-align: left;">Sumber</th>
                        <th style="padding: 0.75rem; text-align: left;">Keterangan</th>
                        <th style="padding: 0.75rem; text-align: left;">Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                    <tr style="border-bottom: 1px solid var(--k-gray-200);">
                        <td style="padding: 0.75rem;">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 0.8rem;">{{ $movement->created_at->format('d M Y') }}</span>
                                <span style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $movement->created_at->format('H:i') }}</span>
                            </div>
                        </td>
                        <td style="padding: 0.75rem;">
                            @if($movement->type == 'in')
                                <span class="dms-badge dms-badge-success">Stock Masuk</span>
                            @elseif($movement->type == 'out')
                                <span class="dms-badge dms-badge-danger">Stock Keluar</span>
                            @else
                                <span class="dms-badge dms-badge-warning">Penyesuaian</span>
                            @endif
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <span style="font-weight: 600; color: {{ $movement->type == 'in' ? 'var(--k-green)' : 'var(--k-red)' }}">
                                {{ $movement->type == 'in' ? '+' : '-' }}{{ number_format($movement->quantity) }}
                            </span>
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">{{ number_format($movement->before_quantity) }}</td>
                        <td style="padding: 0.75rem; text-align: center;">{{ number_format($movement->after_quantity) }}</td>
                        <td style="padding: 0.75rem;">
                            <span class="dms-badge dms-badge-{{ $movement->source_badge }}">
                                {{ $movement->source_label }}
                            </span>
                        </td>
                        <td style="padding: 0.75rem;">
                            <div style="max-width: 250px;">
                                @if($movement->source_type == 'direct_purchase' && $movement->directPurchase)
                                    <a href="{{ route('direct-purchases.show', $movement->directPurchase) }}" style="color: var(--k-green); text-decoration: none;">
                                        {{ $movement->directPurchase->invoice_number }}
                                    </a>
                                @elseif($movement->source_type == 'purchase_order' && $movement->purchaseOrder)
                                    <a href="{{ route('purchase-orders.show', $movement->purchaseOrder) }}" style="color: var(--k-green); text-decoration: none;">
                                        PO #{{ $movement->purchaseOrder->po_number }}
                                    </a>
                                @elseif($movement->source_type == 'foc')
                                    <span class="dms-badge dms-badge-success">FOC</span>
                                @elseif($movement->source_type == 'order' && $movement->order)
                                    <a href="{{ route('orders.show', $movement->order) }}" style="color: var(--k-green); text-decoration: none;">
                                        Order #{{ $movement->order->order_number }}
                                    </a>
                                @elseif($movement->source_type == 'foc_out' && $movement->outboundFoc)
                                    <a href="{{ route('outbound-focs.show', $movement->outboundFoc) }}" style="color: var(--k-green); text-decoration: none;">
                                        FOC #{{ $movement->outboundFoc->foc_number }}
                                    </a>
                                @elseif($movement->source_type == 'return_out' && $movement->outboundReturn)
                                    <a href="{{ route('outbound-returns.show', $movement->outboundReturn) }}" style="color: var(--k-green); text-decoration: none;">
                                        Return #{{ $movement->outboundReturn->return_number }}
                                    </a>
                                @elseif($movement->source_type == 'adjustment')
                                    <span class="dms-badge dms-badge-warning">Manual</span>
                                @else
                                    {{ $movement->reason ?? '-' }}
                                @endif
                            </div>
                        </td>
                        <td style="padding: 0.75rem;">{{ $movement->createdBy->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding: 2rem; text-align: center;">
                            <i class="bi bi-inbox" style="font-size: 2rem; color: var(--k-gray-300);"></i>
                            <p style="margin-top: 0.5rem; color: var(--k-gray-500);">Belum ada riwayat pergerakan stok</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 1rem;">
            {{ $movements->links() }}
        </div>
    </div>
</div>

<style>
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
.pagination .disabled span {
    background: var(--k-gray-100);
    color: var(--k-gray-400);
    border-color: var(--k-gray-200);
    cursor: not-allowed;
}
.dms-table th {
    background: var(--k-gray-100);
    font-weight: 600;
}
</style>
@endsection