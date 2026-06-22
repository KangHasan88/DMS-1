@extends('layouts.sidebar')

@section('page-title', 'Edit Pengiriman')
@section('breadcrumb', 'Operasional / Pengiriman / Edit')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Pengiriman</h3>
        <p class="dms-form-subtitle">
            Perbarui data pengiriman {{ $delivery->delivery_method_label }} untuk order {{ $delivery->order->order_number ?? '#' . $delivery->id }}.
        </p>
    </div>

    @php($assignmentEditable = $delivery->status === \App\Models\Delivery::STATUS_ASSIGNED)

    @if($delivery->usesInternalDelivery() && !$assignmentEditable)
        <div style="margin-bottom: 1rem; padding: 0.75rem 0.875rem; border: 1px solid var(--k-gray-200); border-radius: 6px; background: var(--k-gray-50); color: var(--k-text-muted); font-size: 0.8rem;">
            <i class="bi bi-lock"></i>
            Driver dan armada dikunci karena barang sudah diproses. Perubahan setelah tahap ini harus melalui penanganan insiden.
        </div>
    @endif

    <form action="{{ route('deliveries.update', $delivery) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem 1.5rem; margin-bottom: 2rem;">
            @if($delivery->usesInternalDelivery())
                <div class="form-group">
                    <label class="form-label">Pilih Driver <span class="dms-required">*</span></label>
                    <select name="kurir_id" id="edit-driver" class="form-control" required {{ !$assignmentEditable ? 'disabled' : '' }}>
                        <option value="">-- Pilih Driver --</option>
                        @foreach($kurirs as $kurir)
                            <option value="{{ $kurir->id }}"
                                data-primary-vehicle-id="{{ $kurir->activeDriverVehicleAssignment?->delivery_vehicle_id ?? '' }}"
                                {{ old('kurir_id', $delivery->kurir_id) == $kurir->id ? 'selected' : '' }}>
                                {{ $kurir->name }} - {{ $kurir->companyBranch->code ?? '-' }} ({{ $kurir->phone }})
                            </option>
                        @endforeach
                    </select>
                    @if(!$assignmentEditable)
                        <input type="hidden" name="kurir_id" value="{{ $delivery->kurir_id }}">
                    @endif
                    @error('kurir_id') <span class="dms-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Armada Aktual <span class="dms-required">*</span></label>
                    <select name="delivery_vehicle_id" class="form-control" required {{ !$assignmentEditable ? 'disabled' : '' }}>
                        <option value="">-- Pilih Armada --</option>
                        @foreach($deliveryVehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ old('delivery_vehicle_id', $delivery->delivery_vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                {{ $vehicle->code }} - {{ $vehicle->name }}{{ $vehicle->plate_number ? ' (' . $vehicle->plate_number . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @if(!$assignmentEditable)
                        <input type="hidden" name="delivery_vehicle_id" value="{{ $delivery->delivery_vehicle_id }}">
                    @endif
                    <small class="dms-form-help">
                        Armada utama mengikuti driver. Pilih berbeda hanya untuk armada backup.
                        <a href="{{ route('delivery-vehicles.index') }}" style="color: var(--k-blue); font-weight: 600;">Kelola armada</a>
                    </small>
                    @error('delivery_vehicle_id') <span class="dms-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group dms-form-span-2">
                    <label class="form-label">Alasan Perubahan Penugasan</label>
                    <input type="text" name="vehicle_override_reason" class="form-control" value="{{ old('vehicle_override_reason') }}" placeholder="Wajib jika driver atau armada diubah" {{ !$assignmentEditable ? 'disabled' : '' }}>
                    <small class="dms-form-help">Contoh: driver berhalangan atau armada utama maintenance.</small>
                    @error('vehicle_override_reason') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
            @else
                <div class="form-group">
                    <label class="form-label">Vendor Ekspedisi</label>
                    <div class="form-control" style="display: flex; align-items: center; background: var(--k-gray-50); color: var(--k-text);">
                        {{ $delivery->vendor->name ?? '-' }}
                    </div>
                    <small class="dms-form-help">Vendor tidak diubah di tahap edit agar riwayat pengiriman tetap jelas.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">No Resi</label>
                    <input type="text" name="tracking_code" class="form-control" value="{{ old('tracking_code', $delivery->tracking_code) }}" placeholder="Contoh: JNE123456789">
                    @error('tracking_code') <span class="dms-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">No Tagihan Vendor</label>
                    <input type="text" name="vendor_invoice_number" class="form-control" value="{{ old('vendor_invoice_number', $delivery->vendor_invoice_number) }}" placeholder="Isi saat invoice/manifest ekspedisi diterima">
                    @error('vendor_invoice_number') <span class="dms-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Status Biaya Ekspedisi</label>
                    <select name="shipping_cost_status" class="form-control">
                        @foreach(\App\Models\Delivery::COST_STATUS_LIST as $value => $label)
                            @continue($value === \App\Models\Delivery::COST_NOT_APPLICABLE)
                            <option value="{{ $value }}" {{ old('shipping_cost_status', $delivery->shipping_cost_status) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('shipping_cost_status') <span class="dms-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Biaya Ekspedisi Aktual</label>
                    <input type="number" name="actual_shipping_cost" class="form-control" value="{{ old('actual_shipping_cost', $delivery->actual_shipping_cost ?? 0) }}" min="0">
                    <small class="dms-form-help">Isi berdasarkan tagihan resmi vendor. Ongkir customer tetap terpisah.</small>
                    @error('actual_shipping_cost') <span class="dms-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Ongkir Customer</label>
                    <div class="form-control" style="display: flex; align-items: center; background: var(--k-gray-50); color: var(--k-text);">
                        Rp {{ number_format($delivery->order->delivery_fee ?? 0, 0, ',', '.') }}
                    </div>
                    <small class="dms-form-help">Nilai yang ditagihkan ke customer, bukan biaya vendor.</small>
                </div>
            @endif

            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $delivery->notes) }}</textarea>
                @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('deliveries.show', $delivery) }}" class="dms-btn dms-btn-outline" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-save"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

@if($delivery->usesInternalDelivery())
<script>
document.addEventListener('DOMContentLoaded', function () {
    const driverSelect = document.getElementById('edit-driver');
    const vehicleSelect = document.querySelector('[name="delivery_vehicle_id"]');
    const reasonInput = document.querySelector('[name="vehicle_override_reason"]');

    if (!driverSelect || driverSelect.disabled) {
        return;
    }

    const originalDriverId = driverSelect.value;
    const originalVehicleId = vehicleSelect.value;

    function syncReasonRequirement() {
        reasonInput.required = driverSelect.value !== originalDriverId || vehicleSelect.value !== originalVehicleId;
    }

    driverSelect.addEventListener('change', function () {
        const primaryVehicleId = driverSelect.options[driverSelect.selectedIndex]?.dataset.primaryVehicleId || '';

        if (primaryVehicleId) {
            vehicleSelect.value = primaryVehicleId;
        }

        syncReasonRequirement();
    });

    vehicleSelect.addEventListener('change', function () {
        syncReasonRequirement();
    });
});
</script>
@endif
@endsection
