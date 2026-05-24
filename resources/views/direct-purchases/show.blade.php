@extends('layouts.sidebar')

@section('page-title', 'Detail Direct Purchase')
@section('breadcrumb', 'Direct Purchase / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-cash" style="color: var(--k-green);"></i>
                Detail Pembelian Langsung
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Invoice #{{ $directPurchase->invoice_number }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('direct-purchases.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Purchase Info Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="padding: 1rem; background: var(--k-green-light); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Total Pembelian</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--k-green);">
                {{ $directPurchase->formatted_total }}
            </div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Tipe</div>
            <div>
                @if($directPurchase->purchase_type == 'foc')
                    <span class="dms-badge dms-badge-success">
                        <i class="bi bi-gift"></i> Free of Charge (Bonus)
                    </span>
                @else
                    <span class="dms-badge dms-badge-info">
                        <i class="bi bi-cash"></i> Cash
                    </span>
                @endif
            </div>
            @if($directPurchase->reference_po)
            <div style="font-size: 0.65rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Referensi PO: {{ $directPurchase->reference_po }}
            </div>
            @endif
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Tanggal Pembelian</div>
            <div style="font-size: 1rem; font-weight: 600;">{{ $directPurchase->purchase_date->format('d M Y') }}</div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Dibuat Oleh</div>
            <div style="font-size: 0.9rem; font-weight: 500;">{{ $directPurchase->createdBy->name ?? '-' }}</div>
            <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $directPurchase->created_at->format('d M Y H:i') }}</div>
        </div>
    </div>

    <!-- Supplier Information -->
    <div style="margin-bottom: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-shop" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Informasi Pedagang / Supplier
        </h4>
        
        <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Nama Pedagang</div>
                    <div style="font-weight: 600;">{{ $directPurchase->supplier_name }}</div>
                </div>
                @if($directPurchase->supplier_phone)
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Telepon</div>
                    <div>{{ $directPurchase->supplier_phone }}</div>
                </div>
                @endif
                @if($directPurchase->supplier)
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Supplier Terdaftar</div>
                    <div>
                        <a href="{{ route('suppliers.show', $directPurchase->supplier) }}" style="color: var(--k-green);">
                            {{ $directPurchase->supplier->name }}
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Purchase Items -->
    <div style="margin-bottom: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-box-seam" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Detail Produk
        </h4>
        
        <div style="overflow-x: auto;">
            <table class="dms-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Produk</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Satuan</th>
                        <th style="padding: 0.75rem; text-align: center; font-weight: 600;">Quantity</th>
                        <th style="padding: 0.75rem; text-align: right; font-weight: 600;">Harga Beli</th>
                        <th style="padding: 0.75rem; text-align: right; font-weight: 600;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($directPurchase->items as $item)
                    <tr style="border-bottom: 1px solid var(--k-gray-200);">
                        <td style="padding: 0.75rem;">
                            <div style="font-weight: 500;">{{ $item->product->name }}</div>
                            @if($item->notes)
                            <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                                <i class="bi bi-chat-dots"></i> {{ $item->notes }}
                            </div>
                            @endif
                        </td>
                        <td style="padding: 0.75rem;">{{ $item->product->unit->name ?? '-' }}</td>
                        <td style="padding: 0.75rem; text-align: center;">{{ number_format($item->quantity) }}</td>
                        <td style="padding: 0.75rem; text-align: right;">
                            @if($directPurchase->purchase_type == 'foc')
                                <span class="dms-badge dms-badge-success">GRATIS</span>
                            @else
                                Rp {{ number_format($item->price, 0, ',', '.') }}
                            @endif
                        </td>
                        <td style="padding: 0.75rem; text-align: right;">
                            @if($directPurchase->purchase_type == 'foc')
                                <span class="dms-badge dms-badge-success">Rp 0</span>
                            @else
                                <span style="font-weight: 600; color: var(--k-green);">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: var(--k-green-light); border-top: 1px solid var(--k-gray-200);">
                        <td colspan="4" style="padding: 0.75rem; text-align: right; font-weight: 700;">Total</td>
                        <td style="padding: 0.75rem; text-align: right; font-weight: 700;">
                            @if($directPurchase->purchase_type == 'foc')
                                <span class="dms-badge dms-badge-success">GRATIS</span>
                            @else
                                Rp {{ number_format($directPurchase->total, 0, ',', '.') }}
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Notes -->
    @if($directPurchase->notes)
    <div>
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-chat-dots" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Catatan
        </h4>
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
            <p style="color: var(--k-gray-700); line-height: 1.5;">{{ $directPurchase->notes }}</p>
        </div>
    </div>
    @endif
</div>

<style>
.dms-table th, .dms-table td {
    vertical-align: middle;
}
</style>
@endsection