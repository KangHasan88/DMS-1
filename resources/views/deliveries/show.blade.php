@extends('layouts.sidebar')

@section('page-title', 'Detail Pengiriman')
@section('breadcrumb', 'Operasional / Pengiriman / Detail')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-truck" style="color: var(--k-green);"></i>
                Detail Pengiriman
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Pengiriman #{{ $delivery->id }} - Order #{{ $delivery->order->order_number ?? '-' }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            @can('edit deliveries')
            @if($delivery->status === \App\Models\Delivery::STATUS_ASSIGNED)
            <a href="{{ route('deliveries.edit', $delivery) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-person-gear"></i> Ubah Penugasan
            </a>
            @endif
            @endcan
            <a href="{{ route('deliveries.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div style="margin-bottom: 2rem;">
        <span class="dms-badge dms-badge-{{ $delivery->status_color }}" style="font-size: 1rem; padding: 0.5rem 1rem;">
            {{ $delivery->status_label }}
        </span>
    </div>

    @can('process deliveries')
    @if(in_array($delivery->status, [\App\Models\Delivery::STATUS_ASSIGNED, \App\Models\Delivery::STATUS_PICKED_UP, \App\Models\Delivery::STATUS_IN_TRANSIT], true))
    <div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;">
        <form action="{{ route('deliveries.update-status', $delivery) }}" method="POST" enctype="multipart/form-data" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
            @csrf
            @if($delivery->status === \App\Models\Delivery::STATUS_ASSIGNED)
                <input type="hidden" name="status" value="{{ \App\Models\Delivery::STATUS_PICKED_UP }}">
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-box-arrow-up"></i> Barang Diambil
                </button>
            @elseif($delivery->status === \App\Models\Delivery::STATUS_PICKED_UP)
                <input type="hidden" name="status" value="{{ \App\Models\Delivery::STATUS_IN_TRANSIT }}">
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-truck"></i> Mulai Pengiriman
                </button>
            @elseif($delivery->status === \App\Models\Delivery::STATUS_IN_TRANSIT)
                <input type="hidden" name="status" value="{{ \App\Models\Delivery::STATUS_COMPLETED }}">
                <div class="form-group" style="min-width: 260px; margin: 0;">
                    <label class="form-label">Bukti Pengiriman (opsional)</label>
                    <input type="file" name="proof_image" class="form-control" accept="image/png,image/jpeg,image/jpg">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-flag"></i> Selesaikan Pengiriman
                </button>
            @endif
        </form>
    </div>
    @endif
    @endcan

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
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
                    <div style="font-weight: 600;">Ongkir Customer</div>
                    <div>Rp {{ number_format($delivery->order->delivery_fee ?? 0, 0, ',', '.') }}</div>
                    <div style="font-weight: 600;">Alamat</div>
                    <div>{{ $delivery->order->address ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div>
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                <i class="bi bi-person-badge" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                Informasi Pengiriman
            </h4>

            <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 0.5rem;">
                    <div style="font-weight: 600;">Metode</div>
                    <div>{{ $delivery->delivery_method_label }}</div>
                    <div style="font-weight: 600;">{{ $delivery->usesExpedition() ? 'Ekspedisi' : 'Kurir' }}</div>
                    <div>{{ $delivery->usesExpedition() ? ($delivery->vendor?->name ?? '-') : ($delivery->kurir?->name ?? '-') }}</div>
                    @if($delivery->usesInternalDelivery())
                        <div style="font-weight: 600;">Armada</div>
                        <div>
                            {{ $delivery->vehicle ? $delivery->vehicle->code . ' - ' . $delivery->vehicle->name : '-' }}
                            @if($delivery->vehicle?->plate_number)
                                <span class="dms-muted">({{ $delivery->vehicle->plate_number }})</span>
                            @endif
                            @if($delivery->vehicle_override_reason)
                                <div class="dms-muted" style="font-size: 0.72rem; margin-top: 0.2rem;">
                                    Armada pengganti: {{ $delivery->vehicle_override_reason }}
                                </div>
                            @endif
                        </div>
                    @endif
                    <div style="font-weight: 600;">Kontak</div>
                    <div>{{ $delivery->usesExpedition() ? ($delivery->vendor?->phone ?? '-') : ($delivery->kurir?->phone ?? '-') }}</div>
                    <div style="font-weight: 600;">No Resi</div>
                    <div>{{ $delivery->tracking_code ?? $delivery->order->tracking_code ?? '-' }}</div>
                    @if($delivery->usesExpedition())
                        <div style="font-weight: 600;">No Tagihan</div>
                        <div>{{ $delivery->vendor_invoice_number ?? '-' }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($delivery->usesExpedition())
    <div style="margin-top: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-cash-coin" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Ringkasan Ongkos Kirim
        </h4>

        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
            <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
                <div class="dms-muted">Ongkir ke Customer</div>
                <div style="font-size: 1.1rem; font-weight: 700;">Rp {{ number_format($delivery->order->delivery_fee ?? 0, 0, ',', '.') }}</div>
            </div>
            <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
                <div class="dms-muted">Biaya Aktual Ekspedisi</div>
                <div style="font-size: 1.1rem; font-weight: 700;">Rp {{ number_format($delivery->actual_shipping_cost ?? 0, 0, ',', '.') }}</div>
                <div class="dms-muted" style="font-size: 0.75rem;">{{ $delivery->shipping_cost_status_label }}</div>
            </div>
            <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
                <div class="dms-muted">Selisih Ongkir</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: {{ $delivery->shippingMargin() < 0 ? 'var(--k-red)' : 'var(--k-blue)' }};">
                    {{ $delivery->shippingMargin() < 0 ? '-' : '' }}Rp {{ number_format(abs($delivery->shippingMargin()), 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
    @endif

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
                        <div style="font-weight: 600;">Ditugaskan</div>
                        <div style="font-size: 0.75rem; color: var(--k-gray-500);">{{ $delivery->assigned_at ? $delivery->assigned_at->format('d M Y H:i') : '-' }}</div>
                    </div>
                </div>

                @if($delivery->picked_up_at)
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 32px; text-align: center;">
                        <i class="bi bi-box-seam" style="color: var(--k-green);"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600;">{{ $delivery->usesExpedition() ? 'Diserahkan ke Ekspedisi' : 'Barang Diambil Kurir' }}</div>
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
                        <div style="font-weight: 600;">Dalam Pengiriman</div>
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
                        <div style="font-weight: 600;">Selesai</div>
                        <div style="font-size: 0.75rem; color: var(--k-gray-500);">{{ $delivery->completed_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

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
