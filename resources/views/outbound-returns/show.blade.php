@extends('layouts.sidebar')

@section('page-title', 'Detail Return Out')
@section('breadcrumb', 'Outbound / Return Out / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-arrow-return-left" style="color: var(--k-green);"></i>
                Detail Return Out
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Return #{{ $outboundReturn->return_number }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('outbound-returns.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Info Cards -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="padding: 1rem; background: var(--k-green-light); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Total Item</div>
            <div style="font-size: 1.5rem; font-weight: 700;">{{ number_format($outboundReturn->items->sum('quantity')) }}</div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Total Nilai</div>
            <div style="font-size: 1.2rem; font-weight: 600;">Rp {{ number_format($outboundReturn->total, 0, ',', '.') }}</div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Tanggal Return</div>
            <div style="font-size: 0.9rem; font-weight: 500;">{{ $outboundReturn->return_date->format('d M Y') }}</div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Tipe Return</div>
            <div>
                <span class="dms-badge dms-badge-warning">{{ $outboundReturn->type_label }}</span>
            </div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Tindakan</div>
            <div>
                <span class="dms-badge dms-badge-info">{{ $outboundReturn->action_label }}</span>
            </div>
        </div>
    </div>

    <!-- Pelanggan Information -->
    <div style="margin-bottom: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-person" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Informasi Pelanggan
        </h4>
        
        <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Nama Pelanggan</div>
                    <div style="font-weight: 600;">{{ $outboundReturn->customer_name }}</div>
                </div>
                @if($outboundReturn->customer_phone)
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Telepon</div>
                    <div>{{ $outboundReturn->customer_phone }}</div>
                </div>
                @endif
                @if($outboundReturn->reference_order)
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Referensi Order</div>
                    <div><strong>{{ $outboundReturn->reference_order }}</strong></div>
                </div>
                @endif
                @if($outboundReturn->replacement_order)
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Order Pengganti</div>
                    <div><strong>{{ $outboundReturn->replacement_order }}</strong></div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div style="margin-bottom: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-box-seam" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Detail Produk Return
        </h4>
        
        <div style="overflow-x: auto;">
            <table class="dms-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Satuan</th>
                        <th>Quantity Return</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($outboundReturn->items as $item)
                    <tr>
                        <td>
                            <div style="font-weight: 600;">{{ $item->product->name }}</div>
                            @if($item->notes)
                            <div style="font-size: 0.65rem; color: var(--k-gray-500);">Catatan: {{ $item->notes }}</div>
                            @endif
                        </td>
                        <td>{{ $item->product->unit->name ?? '-' }}</td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: var(--k-green-light);">
                        <td colspan="4" style="text-align: right; font-weight: 700;">Total</td>
                        <td style="font-weight: 700;">Rp {{ number_format($outboundReturn->total, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Notes -->
    @if($outboundReturn->notes || $outboundReturn->reason_detail)
    <div>
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-chat-dots" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Catatan
        </h4>
        
        @if($outboundReturn->reason_detail)
        <div style="margin-bottom: 1rem; padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Detail Alasan Return</div>
            <p style="color: var(--k-gray-700);">{{ $outboundReturn->reason_detail }}</p>
        </div>
        @endif
        
        @if($outboundReturn->notes)
        <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Catatan</div>
            <p style="color: var(--k-gray-700);">{{ $outboundReturn->notes }}</p>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection
