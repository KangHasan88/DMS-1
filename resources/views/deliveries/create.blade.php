@extends('layouts.sidebar')

@section('page-title', 'Tugaskan Pengiriman')
@section('breadcrumb', 'Operasional / Pengiriman / Tugaskan')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Form Penugasan</h3>
        <p class="dms-form-subtitle">Pilih order siap kirim, lalu tentukan pengiriman internal atau ekspedisi.</p>
    </div>

    <form action="{{ route('deliveries.store') }}" method="POST" id="delivery-assignment-form">
        @csrf
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Order Selection -->
            <div class="form-group">
                <label class="form-label">Pilih Order <span class="dms-required">*</span></label>
                <select name="order_id" class="form-control" required>
                    <option value="">-- Pilih Order --</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                            {{ $order->order_number }} - {{ $order->user->name ?? 'N/A' }} - {{ $order->companyBranch->code ?? '-' }} (Rp {{ number_format($order->total, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                <small class="dms-form-help">Hanya menampilkan order dengan status "Siap Kirim".</small>
                @error('order_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div id="order-detail-panel" class="dms-form-span-2" style="display: none; border: 1px solid var(--k-gray-200); border-radius: 8px; background: var(--k-gray-50); padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.85rem;">
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-text-muted); font-weight: 600; text-transform: uppercase;">Detail Order</div>
                        <div id="detail-order-number" style="font-weight: 700; color: var(--k-blue);"></div>
                    </div>
                    <a id="detail-order-link" href="#" target="_blank" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.75rem; font-size: 0.7rem; text-decoration: none;">
                        <i class="bi bi-box-arrow-up-right"></i> Buka Detail
                    </a>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 0.85rem;">
                    <div>
                        <div class="dms-muted" style="font-size: 0.7rem;">Pelanggan</div>
                        <div id="detail-customer" style="font-weight: 600;"></div>
                        <div id="detail-phone" class="dms-muted" style="font-size: 0.7rem;"></div>
                    </div>
                    <div>
                        <div class="dms-muted" style="font-size: 0.7rem;">Cabang</div>
                        <div id="detail-branch" style="font-weight: 600;"></div>
                    </div>
                    <div>
                        <div class="dms-muted" style="font-size: 0.7rem;">Jadwal Kirim</div>
                        <div id="detail-delivery" style="font-weight: 600;"></div>
                    </div>
                    <div>
                        <div class="dms-muted" style="font-size: 0.7rem;">Total</div>
                        <div id="detail-total" style="font-weight: 700; color: var(--k-blue);"></div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1rem;">
                    <div>
                        <div class="dms-muted" style="font-size: 0.7rem;">Alamat Kirim</div>
                        <div id="detail-address" style="font-size: 0.8rem;"></div>
                        <div id="detail-coverage" style="display: none; margin-top: 0.55rem; padding-top: 0.55rem; border-top: 1px dashed var(--k-gray-200);">
                            <div style="display:flex; align-items:center; gap:0.4rem;">
                                <i class="bi bi-pin-map" style="color:var(--k-blue);"></i>
                                <strong id="detail-zone" style="font-size:0.75rem;"></strong>
                                <span id="detail-coverage-status" class="dms-badge"></span>
                            </div>
                            <div id="detail-depots" class="dms-muted" style="font-size:0.7rem; margin-top:0.25rem;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="dms-muted" style="font-size: 0.7rem;">Item Order</div>
                        <div id="detail-items" style="display: flex; flex-direction: column; gap: 0.35rem;"></div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Metode Pengiriman <span class="dms-required">*</span></label>
                <select name="delivery_method" id="delivery-method" class="form-control" required>
                    <option value="{{ \App\Models\Delivery::METHOD_INTERNAL }}" {{ old('delivery_method', \App\Models\Delivery::METHOD_INTERNAL) === \App\Models\Delivery::METHOD_INTERNAL ? 'selected' : '' }}>Internal</option>
                    <option value="{{ \App\Models\Delivery::METHOD_EXPEDITION }}" {{ old('delivery_method') === \App\Models\Delivery::METHOD_EXPEDITION ? 'selected' : '' }}>Ekspedisi</option>
                </select>
                <small class="dms-form-help">Internal memakai kurir sendiri. Ekspedisi dicatat sebagai vendor pihak ketiga; booking tetap mengikuti proses vendor.</small>
                @error('delivery_method') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Driver Selection -->
            <div class="form-group js-internal-delivery">
                <label class="form-label">Pilih Driver <span class="dms-required">*</span></label>
                <select name="kurir_id" class="form-control" required>
                    <option value="">-- Pilih Driver --</option>
                    @foreach($kurirs as $kurir)
                        <option value="{{ $kurir->id }}"
                            data-primary-vehicle-id="{{ $kurir->activeDriverVehicleAssignment?->delivery_vehicle_id ?? '' }}"
                            data-primary-vehicle-label="{{ $kurir->activeDriverVehicleAssignment?->vehicle ? $kurir->activeDriverVehicleAssignment->vehicle->code . ' - ' . $kurir->activeDriverVehicleAssignment->vehicle->name . ($kurir->activeDriverVehicleAssignment->vehicle->plate_number ? ' (' . $kurir->activeDriverVehicleAssignment->vehicle->plate_number . ')' : '') : '' }}"
                            {{ old('kurir_id') == $kurir->id ? 'selected' : '' }}>
                            {{ $kurir->name }} - {{ $kurir->companyBranch->code ?? '-' }} ({{ $kurir->phone }})
                        </option>
                    @endforeach
                </select>
                @error('kurir_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group js-internal-delivery">
                <label class="form-label">Armada Utama</label>
                <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 0.625rem; align-items: center;">
                    <div id="primary-vehicle-display" class="form-control" style="display: flex; align-items: center; background: var(--k-gray-50); min-width: 0;">
                        Pilih driver terlebih dahulu
                    </div>
                    <button type="button" id="toggle-vehicle-override" class="dms-btn dms-btn-outline" style="height: 44px; padding: 0 0.875rem; white-space: nowrap;" disabled title="Gunakan armada pengganti">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Ganti</span>
                    </button>
                </div>

                <div id="vehicle-override-fields" style="display: none; margin-top: 0.875rem; padding: 0.875rem; border: 1px solid var(--k-gray-200); border-radius: 6px; background: var(--k-gray-50);">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--k-text);">Gunakan Armada Pengganti</div>
                        <button type="button" id="close-vehicle-override" class="dms-btn dms-btn-outline dms-btn-sm" title="Batalkan penggantian armada">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <label class="form-label">Armada Pengganti <span class="dms-required">*</span></label>
                    <select name="delivery_vehicle_id" class="form-control">
                    <option value="">-- Pilih Armada --</option>
                    @foreach($deliveryVehicles as $vehicle)
                        <option value="{{ $vehicle->id }}"
                            data-branch-id="{{ $vehicle->company_branch_id ?? '' }}"
                            {{ old('delivery_vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                            {{ $vehicle->code }} - {{ $vehicle->name }}{{ $vehicle->plate_number ? ' (' . $vehicle->plate_number . ')' : '' }}
                        </option>
                    @endforeach
                    </select>
                    <label class="form-label" style="margin-top: 0.75rem;">Alasan Penggantian <span class="dms-required">*</span></label>
                    <input type="text" name="vehicle_override_reason" class="form-control" value="{{ old('vehicle_override_reason') }}" placeholder="Contoh: armada utama maintenance">
                </div>
                <small class="dms-form-help" id="vehicle-availability-help">Armada utama driver dipilih otomatis. <a href="{{ route('delivery-vehicles.index') }}" style="color: var(--k-blue); font-weight: 600;">Kelola armada</a></small>
                @error('delivery_vehicle_id') <span class="dms-error">{{ $message }}</span> @enderror
                @error('vehicle_override_reason') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group js-expedition-delivery" style="display: none;">
                <label class="form-label">Vendor Ekspedisi <span class="dms-required">*</span></label>
                <select name="delivery_vendor_id" class="form-control">
                    <option value="">-- Pilih Ekspedisi --</option>
                    @foreach($deliveryVendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ old('delivery_vendor_id') == $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->name }}{{ $vendor->companyBranch ? ' - ' . $vendor->companyBranch->code : '' }}
                        </option>
                    @endforeach
                </select>
                <small class="dms-form-help">
                    <a href="{{ route('delivery-vendors.index') }}" style="color: var(--k-blue); font-weight: 600;">Kelola ekspedisi</a>
                </small>
                @error('delivery_vendor_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group js-expedition-delivery" style="display: none;">
                <label class="form-label">No Resi <span style="font-weight: 400; color: var(--k-text-muted);">(opsional)</span></label>
                <input type="text" name="tracking_code" class="form-control" value="{{ old('tracking_code') }}" placeholder="Isi jika sudah ada no resi">
                <small class="dms-form-help">Boleh dikosongkan dulu jika resi baru keluar setelah booking, pickup, atau drop ke ekspedisi.</small>
                @error('tracking_code') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group js-expedition-delivery" style="display: none;">
                <label class="form-label">Status Biaya Ekspedisi</label>
                <div class="form-control" style="display: flex; align-items: center; background: var(--k-gray-50); color: var(--k-text-muted);">
                    Belum Ditagih
                </div>
                <small class="dms-form-help">Biaya aktual diisi saat tagihan/manifest ekspedisi sudah diterima.</small>
            </div>
            
            <!-- Notes -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan untuk kurir (alamat khusus, instruksi pengiriman, dll)">{{ old('notes') }}</textarea>
                @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
        </div>
        
        <!-- Buttons -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('deliveries.index') }}" class="dms-btn dms-btn-outline" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary" data-delivery-submit style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-save"></i> Tugaskan Kurir
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const orderDetails = @json($orderDetails);
    const methodSelect = document.getElementById('delivery-method');
    const orderSelect = document.querySelector('[name="order_id"]');
    const orderDetailPanel = document.getElementById('order-detail-panel');
    const internalFields = document.querySelectorAll('.js-internal-delivery');
    const expeditionFields = document.querySelectorAll('.js-expedition-delivery');
    const kurirSelect = document.querySelector('[name="kurir_id"]');
    const vehicleSelect = document.querySelector('[name="delivery_vehicle_id"]');
    const primaryVehicleDisplay = document.getElementById('primary-vehicle-display');
    const toggleVehicleOverride = document.getElementById('toggle-vehicle-override');
    const closeVehicleOverride = document.getElementById('close-vehicle-override');
    const vehicleOverrideFields = document.getElementById('vehicle-override-fields');
    const vehicleOverrideReason = document.querySelector('[name="vehicle_override_reason"]');
    const vehicleAvailabilityHelp = document.getElementById('vehicle-availability-help');
    const vendorSelect = document.querySelector('[name="delivery_vendor_id"]');
    const submitButton = document.querySelector('#delivery-assignment-form [data-delivery-submit]');

    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(value || 0).replace('IDR', 'Rp');
    }

    function renderOrderDetail() {
        const detail = orderDetails[orderSelect.value];

        if (!detail) {
            orderDetailPanel.style.display = 'none';
            return;
        }

        document.getElementById('detail-order-number').textContent = detail.order_number || '-';
        document.getElementById('detail-order-link').href = `/orders/${orderSelect.value}`;
        document.getElementById('detail-customer').textContent = detail.customer_name || '-';
        document.getElementById('detail-phone').textContent = detail.customer_phone || '-';
        document.getElementById('detail-branch').textContent = detail.branch || '-';
        document.getElementById('detail-delivery').textContent = `${detail.delivery_date || '-'} (${detail.delivery_time_slot || '-'})`;
        document.getElementById('detail-total').textContent = formatRupiah(detail.grand_total);
        document.getElementById('detail-address').textContent = detail.shipping_address || '-';

        const coveragePanel = document.getElementById('detail-coverage');
        const coverageStatus = document.getElementById('detail-coverage-status');
        if (detail.delivery_zone) {
            document.getElementById('detail-zone').textContent = detail.delivery_zone;
            coverageStatus.textContent = detail.coverage_verified ? 'Terverifikasi' : 'Perlu Verifikasi';
            coverageStatus.className = `dms-badge ${detail.coverage_verified ? 'dms-badge-success' : 'dms-badge-warning'}`;
            document.getElementById('detail-depots').textContent = detail.depot_options?.length
                ? `Urutan depo: ${detail.depot_options.map((depot) => `${depot.priority}. ${depot.label}`).join(' | ')}`
                : 'Belum ada depo aktif untuk zona ini.';
            coveragePanel.style.display = '';
        } else {
            coveragePanel.style.display = 'none';
        }

        const itemsContainer = document.getElementById('detail-items');
        itemsContainer.innerHTML = '';

        if (!detail.items || !detail.items.length) {
            itemsContainer.innerHTML = '<span class="dms-muted" style="font-size: 0.75rem;">Belum ada item</span>';
        } else {
            detail.items.slice(0, 4).forEach((item) => {
                const row = document.createElement('div');
                row.style.cssText = 'display:flex; justify-content:space-between; gap:0.75rem; font-size:0.75rem;';
                row.innerHTML = `<span>${item.name} x${item.quantity}</span><strong>${formatRupiah(item.subtotal)}</strong>`;
                itemsContainer.appendChild(row);
            });

            if (detail.items.length > 4) {
                const more = document.createElement('div');
                more.className = 'dms-muted';
                more.style.fontSize = '0.7rem';
                more.textContent = `+${detail.items.length - 4} item lainnya`;
                itemsContainer.appendChild(more);
            }
        }

        orderDetailPanel.style.display = '';
        filterVehiclesForOrder();
        syncDriverVehicle();
    }

    function filterVehiclesForOrder() {
        if (!vehicleSelect) {
            return;
        }

        const detail = orderDetails[orderSelect.value];
        const branchId = detail && detail.branch_id ? String(detail.branch_id) : '';
        const unavailableVehicleIds = new Set(
            detail && detail.unavailable_vehicle_ids
                ? detail.unavailable_vehicle_ids.map(String)
                : []
        );
        let selectedStillVisible = false;
        let conflictCount = 0;

        Array.from(vehicleSelect.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const optionBranchId = option.dataset.branchId || '';
            const visible = !branchId || !optionBranchId || optionBranchId === branchId;
            const scheduleConflict = unavailableVehicleIds.has(String(option.value));
            option.hidden = !visible;
            option.disabled = visible && scheduleConflict;

            if (visible && scheduleConflict) {
                conflictCount += 1;
            }

            if (visible && !scheduleConflict && option.selected) {
                selectedStillVisible = true;
            }
        });

        if (!selectedStillVisible) {
            vehicleSelect.value = '';
        }

        if (vehicleAvailabilityHelp) {
            vehicleAvailabilityHelp.firstChild.textContent = conflictCount
                ? `${conflictCount} armada tidak tersedia pada jadwal order ini. `
                : 'Hanya armada berstatus Tersedia yang bisa dipilih. ';
        }
    }

    function syncDriverVehicle() {
        if (!kurirSelect || !vehicleSelect) {
            return;
        }

        const selectedDriver = kurirSelect.options[kurirSelect.selectedIndex];
        const primaryVehicleId = selectedDriver?.dataset.primaryVehicleId || '';
        const primaryVehicleLabel = selectedDriver?.dataset.primaryVehicleLabel || '';
        const hasSelectedDriver = !!selectedDriver?.value;
        const detail = orderDetails[orderSelect.value];
        const unavailableVehicleIds = new Set(detail?.unavailable_vehicle_ids?.map(String) || []);
        const primaryUnavailable = primaryVehicleId && unavailableVehicleIds.has(String(primaryVehicleId));

        primaryVehicleDisplay.textContent = primaryVehicleLabel || (selectedDriver?.value
            ? 'Driver belum memiliki armada utama'
            : 'Pilih driver terlebih dahulu');
        toggleVehicleOverride.disabled = !hasSelectedDriver;

        if (primaryVehicleId && !primaryUnavailable && vehicleOverrideFields.style.display === 'none') {
            vehicleSelect.value = primaryVehicleId;
            vehicleOverrideReason.required = false;
        } else if (hasSelectedDriver && (!primaryVehicleId || primaryUnavailable)) {
            vehicleOverrideFields.style.display = '';
            vehicleSelect.value = '';
            vehicleOverrideReason.required = !!primaryVehicleId;
            primaryVehicleDisplay.textContent = primaryUnavailable
                ? `${primaryVehicleLabel} - bentrok jadwal, pilih armada pengganti`
                : 'Driver belum memiliki armada utama, pilih armada manual';
        } else if (!hasSelectedDriver) {
            vehicleOverrideFields.style.display = 'none';
            vehicleSelect.value = '';
            vehicleOverrideReason.value = '';
            vehicleOverrideReason.required = false;
        }
    }

    function syncDeliveryMethod() {
        const isExpedition = methodSelect.value === '{{ \App\Models\Delivery::METHOD_EXPEDITION }}';

        internalFields.forEach((field) => field.style.display = isExpedition ? 'none' : '');
        expeditionFields.forEach((field) => field.style.display = isExpedition ? '' : 'none');

        if (kurirSelect) {
            kurirSelect.required = !isExpedition;
        }

        if (vehicleSelect) {
            vehicleSelect.required = !isExpedition;
        }

        if (vendorSelect) {
            vendorSelect.required = isExpedition;
        }

        if (submitButton) {
            submitButton.innerHTML = isExpedition
                ? '<i class="bi bi-save"></i> Tugaskan Ekspedisi'
                : '<i class="bi bi-save"></i> Tugaskan Kurir';
        }
    }

    methodSelect.addEventListener('change', syncDeliveryMethod);
    orderSelect.addEventListener('change', renderOrderDetail);
    kurirSelect.addEventListener('change', syncDriverVehicle);
    toggleVehicleOverride.addEventListener('click', function () {
        const opening = vehicleOverrideFields.style.display === 'none';
        vehicleOverrideFields.style.display = opening ? '' : 'none';
        vehicleOverrideReason.required = opening;
        syncDriverVehicle();
    });
    closeVehicleOverride.addEventListener('click', function () {
        vehicleOverrideFields.style.display = 'none';
        vehicleOverrideReason.value = '';
        syncDriverVehicle();
    });
    syncDeliveryMethod();
    filterVehiclesForOrder();
    renderOrderDetail();
    syncDriverVehicle();
});
</script>

@endsection
