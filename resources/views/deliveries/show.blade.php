@extends('layouts.sidebar')

@section('page-title', 'Detail Delivery')
@section('breadcrumb', 'Deliveries / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-truck" style="color: var(--k-green);"></i>
                Detail Pengiriman
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Delivery #{{ $delivery->id }} - Order #{{ $delivery->order->order_number ?? '-' }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('deliveries.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Status Badges -->
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div>
                <span class="dms-badge dms-badge-{{ $delivery->status_color }}" style="font-size: 1rem; padding: 0.5rem 1rem;">
                    {{ $delivery->status_label }}
                </span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Order Information -->
        <div>
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                <i class="bi bi-receipt" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                Informasi Order
            </h4>
            
            <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 0.5rem;">
                    <div style="font-weight: 600;">No. Order</div>
                    <div><strong>{{ $delivery->order->order_number ?? '-' }}</strong></div>
                    
                    <div style="font-weight: 600;">Total</div>
                    <div>Rp {{ number_format($delivery->order->total ?? 0, 0, ',', '.') }}</div>
                    
                    <div style="font-weight: 600;">Alamat</div>
                    <div>{{ $delivery->order->address ?? '-' }}</div>
                </div>
            </div>
        </div>
        
        <!-- Kurir Information -->
        <div>
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                <i class="bi bi-person-badge" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                Informasi Kurir
            </h4>
            
            <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 0.5rem;">
                    <div style="font-weight: 600;">Nama</div>
                    <div>{{ $delivery->kurir->name ?? '-' }}</div>
                    
                    <div style="font-weight: 600;">Telepon</div>
                    <div>{{ $delivery->kurir->phone ?? '-' }}</div>
                    
                    <div style="font-weight: 600;">Email</div>
                    <div>{{ $delivery->kurir->email ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Timeline -->
    <div style="margin-top: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-clock-history" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                Timeline Pengiriman
        </h4>
        
        <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 32px; text-align: center;">
                        <i class="bi bi-person-check" style="color: var(--k-green);"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600;">Assigned</div>
                        <div style="font-size: 0.75rem; color: var(--k-gray-500);">{{ $delivery->assigned_at ? $delivery->assigned_at->format('d M Y H:i') : '-' }}</div>
                    </div>
                </div>
                
                @if($delivery->picked_up_at)
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 32px; text-align: center;">
                        <i class="bi bi-box-seam" style="color: var(--k-green);"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600;">Picked Up</div>
                        <div style="font-size: 0.75rem; color: var(--k-gray-500);">{{ $delivery->picked_up_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
                @endif
                
                @if($delivery->in_transit_at)
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 32px; text-align: center;">
                        <i class="bi bi-truck" style="color: var(--k-green);"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600;">In Transit</div>
                        <div style="font-size: 0.75rem; color: var(--k-gray-500);">{{ $delivery->in_transit_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
                @endif
                
                @if($delivery->completed_at)
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 32px; text-align: center;">
                        <i class="bi bi-flag" style="color: var(--k-green);"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600;">Completed</div>
                        <div style="font-size: 0.75rem; color: var(--k-gray-500);">{{ $delivery->completed_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Notes -->
    @if($delivery->notes)
    <div style="margin-top: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-chat-dots" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Catatan
        </h4>
        <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
            <p style="color: var(--k-gray-700);">{{ $delivery->notes }}</p>
        </div>
    </div>
    @endif
    
    <!-- Proof Image -->
    @if($delivery->proof_image)
    <div style="margin-top: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-image" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Bukti Pengiriman
        </h4>
        <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem; text-align: center;">
            <img src="{{ asset('storage/' . $delivery->proof_image) }}" alt="Bukti Pengiriman" style="max-width: 100%; max-height: 300px; border-radius: 8px;">
        </div>
    </div>
    @endif
</div>
@endsection