@extends('layouts.sidebar')

@section('page-title', 'Detail FOC Out')
@section('breadcrumb', 'Outbound / FOC Out / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-gift" style="color: var(--k-green);"></i>
                Detail FOC Out
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                FOC #{{ $outboundFoc->foc_number }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('outbound-focs.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Info Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="padding: 1rem; background: var(--k-green-light); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Total Item</div>
            <div style="font-size: 1.5rem; font-weight: 700;">{{ number_format($outboundFoc->items->sum('quantity')) }}</div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Total Nilai</div>
            <div style="font-size: 1.2rem; font-weight: 600;">Rp {{ number_format($outboundFoc->total, 0, ',', '.') }}</div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Tanggal</div>
            <div style="font-size: 0.9rem; font-weight: 500;">{{ $outboundFoc->foc_date->format('d M Y') }}</div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Alasan</div>
            <div>
                <span class="dms-badge dms-badge-info">{{ $outboundFoc->reason_label }}</span>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div style="margin-bottom: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-person" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Informasi Customer
        </h4>
        
        <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Nama Customer</div>
                    <div style="font-weight: 600;">{{ $outboundFoc->customer_name }}</div>
                </div>
                @if($outboundFoc->customer_phone)
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Telepon</div>
                    <div>{{ $outboundFoc->customer_phone }}</div>
                </div>
                @endif
                @if($outboundFoc->address)
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Alamat</div>
                    <div>{{ $outboundFoc->address }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div style="margin-bottom: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-box-seam" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Detail Produk
        </h4>
        
        <div style="overflow-x: auto;">
            <table class="dms-table">
                <thead>
                    ??
                        <th>Produk</th>
                        <th>Satuan</th>
                        <th>Quantity</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </thead>
                </thead>
                <tbody>
                    @foreach($outboundFoc->items as $item)
                    ??
                        ??
                            <div style="font-weight: 600;">{{ $item->product->name }}</div>
                            @if($item->notes)
                            <div style="font-size: 0.65rem; color: var(--k-gray-500);">Catatan: {{ $item->notes }}</div>
                            @endif
                        ??
                        ??{{ $item->product->unit->name ?? '-' }}??
                        ??{{ number_format($item->quantity) }}??
                        ??Rp {{ number_format($item->price, 0, ',', '.') }}??
                        ??Rp {{ number_format($item->subtotal, 0, ',', '.') }}??
                    </thead>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: var(--k-green-light);">
                        <td colspan="4" style="text-align: right; font-weight: 700;">Total??
                        <td style="font-weight: 700;">Rp {{ number_format($outboundFoc->total, 0, ',', '.') }}</thead>
                    </tr>
                </tfoot>
            80
        </div>
    </div>

    <!-- Notes -->
    @if($outboundFoc->notes || $outboundFoc->reason_detail)
    <div>
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-chat-dots" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Catatan
        </h4>
        
        @if($outboundFoc->reason_detail)
        <div style="margin-bottom: 1rem; padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Detail Alasan</div>
            <p style="color: var(--k-gray-700);">{{ $outboundFoc->reason_detail }}</p>
        </div>
        @endif
        
        @if($outboundFoc->notes)
        <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Catatan</div>
            <p style="color: var(--k-gray-700);">{{ $outboundFoc->notes }}</p>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection