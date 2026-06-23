@php
    $selectedDepots = collect(old('depot_ids', $deliveryZone?->depots?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
    $selectedAddresses = collect(old('customer_address_ids', $deliveryZone?->customerAddresses?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
    $selectedDrivers = collect(old('driver_ids', $deliveryZone?->drivers?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
    $selectedVehicles = collect(old('vehicle_ids', $deliveryZone?->vehicles?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
    $depotPivots = $deliveryZone?->depots?->keyBy('id') ?? collect();
@endphp

<div class="dms-form-header">
    <h3 class="dms-form-title">{{ $deliveryZone ? 'Edit Zona Pengiriman' : 'Tambah Zona Pengiriman' }}</h3>
    <p class="dms-form-subtitle">Satu zona dapat dilayani beberapa depo. Prioritas terkecil menjadi depo utama.</p>
</div>

<form action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <section class="coverage-section">
        <div class="coverage-section-title"><i class="bi bi-geo-alt"></i> Identitas Zona</div>
        <div class="coverage-grid coverage-grid-4">
            <div class="form-group">
                <label class="form-label">Kode Zona <span class="dms-required">*</span></label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $deliveryZone?->code) }}" placeholder="Contoh: JKT-S01" required>
                @error('code') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group coverage-span-2">
                <label class="form-label">Nama Zona <span class="dms-required">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $deliveryZone?->name) }}" placeholder="Contoh: Jakarta Selatan" required>
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $deliveryZone?->sort_order ?? 0) }}">
            </div>
            <div class="form-group coverage-span-4">
                <label class="form-label">Deskripsi</label>
                <input type="text" name="description" class="form-control" value="{{ old('description', $deliveryZone?->description) }}" placeholder="Wilayah layanan dan batas operasional">
            </div>
        </div>
    </section>

    <section class="coverage-section">
        <div class="coverage-section-title"><i class="bi bi-building"></i> Depo Pelayanan</div>
        <p class="coverage-help">Pilih satu atau beberapa cabang sebagai depo. Prioritas 1 menjadi rekomendasi utama.</p>
        <div class="coverage-table">
            <div class="coverage-table-head">
                <span>Aktif</span><span>Depo / Cabang</span><span>Prioritas</span><span>Kapasitas Order/Hari</span>
            </div>
            @foreach($branches as $branch)
            @php($pivot = $depotPivots->get($branch->id)?->pivot)
            <label class="coverage-table-row">
                <span><input type="checkbox" name="depot_ids[]" value="{{ $branch->id }}" @checked($selectedDepots->contains($branch->id) || ($branchScopeId && $branchScopeId === $branch->id))></span>
                <span>
                    <strong>{{ $branch->name }}{{ $branch->is_active ? '' : ' - nonaktif' }}</strong>
                    <small>{{ $branch->code }} &middot; {{ $branch->address ?: 'Alamat belum diisi' }}</small>
                    @if(!$branch->is_active)
                        <small style="color: var(--k-orange); font-weight: 600;">Cabang ini sedang nonaktif, tapi tetap ditampilkan karena masih tersimpan di zona ini.</small>
                    @endif
                </span>
                <span><input type="number" name="depot_priority[{{ $branch->id }}]" class="form-control" min="1" value="{{ old('depot_priority.' . $branch->id, $pivot?->priority ?? ($loop->iteration)) }}"></span>
                <span><input type="number" name="depot_capacity[{{ $branch->id }}]" class="form-control" min="1" value="{{ old('depot_capacity.' . $branch->id, $pivot?->max_daily_orders) }}" placeholder="Opsional"></span>
            </label>
            @endforeach
        </div>
        @error('depot_ids') <span class="dms-error">{{ $message }}</span> @enderror
    </section>

    <div class="coverage-columns">
        <section class="coverage-section">
            <div class="coverage-section-title"><i class="bi bi-person-badge"></i> Driver Coverage</div>
            <div class="coverage-options">
                @forelse($drivers as $driver)
                <label class="coverage-option">
                    <input type="checkbox" name="driver_ids[]" value="{{ $driver->id }}" @checked($selectedDrivers->contains($driver->id))>
                    <span>
                        <strong>{{ $driver->name }}{{ $driver->is_active ? '' : ' - nonaktif' }}</strong>
                        <small>{{ $driver->companyBranch?->code ?? 'Global' }} &middot; {{ $driver->phone ?: '-' }}</small>
                        @if(!$driver->is_active)
                            <small style="color: var(--k-orange); font-weight: 600;">Driver ini sedang nonaktif, tapi tetap ditampilkan karena masih tersimpan di zona ini.</small>
                        @endif
                    </span>
                </label>
                @empty
                <div class="dms-muted">Belum ada driver aktif.</div>
                @endforelse
            </div>
        </section>

        <section class="coverage-section">
            <div class="coverage-section-title"><i class="bi bi-truck-front"></i> Armada Coverage</div>
            <div class="coverage-options">
                @forelse($vehicles as $vehicle)
                <label class="coverage-option">
                    <input type="checkbox" name="vehicle_ids[]" value="{{ $vehicle->id }}" @checked($selectedVehicles->contains($vehicle->id))>
                    <span>
                        <strong>{{ $vehicle->code }} - {{ $vehicle->name }}{{ $vehicle->is_active ? '' : ' - nonaktif' }}</strong>
                        <small>{{ $vehicle->companyBranch?->code ?? 'Global' }} &middot; {{ $vehicle->plate_number ?: '-' }}</small>
                        @if(!$vehicle->is_active)
                            <small style="color: var(--k-orange); font-weight: 600;">Armada ini sedang nonaktif, tapi tetap ditampilkan karena masih tersimpan di zona ini.</small>
                        @endif
                    </span>
                </label>
                @empty
                <div class="dms-muted">Belum ada armada aktif.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="coverage-section">
        <div class="coverage-section-title"><i class="bi bi-pin-map"></i> Alamat Customer dalam Zona</div>
        <p class="coverage-help">Coverage diterapkan ke alamat pengiriman, bukan hanya profil customer. Koordinat yang belum lengkap ditandai untuk verifikasi.</p>
        <div class="coverage-address-list">
            @forelse($addresses as $address)
            <label class="coverage-address">
                <input type="checkbox" name="customer_address_ids[]" value="{{ $address->id }}" @checked($selectedAddresses->contains($address->id))>
                <span class="coverage-address-main">
                    <strong>{{ $address->customer?->name }} &middot; {{ $address->label }}</strong>
                    <small>{{ $address->address }}</small>
                </span>
                <span class="dms-badge {{ $address->latitude && $address->longitude ? 'dms-badge-success' : 'dms-badge-warning' }}">
                    {{ $address->latitude && $address->longitude ? 'Koordinat Siap' : 'Belum Presisi' }}
                </span>
            </label>
            @empty
            <div class="dms-muted">Tidak ada alamat pengiriman yang tersedia untuk assignment.</div>
            @endforelse
        </div>
    </section>

    @if($deliveryZone)
    <label class="coverage-active">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $deliveryZone->is_active))>
        <span>Zona aktif dan dapat dipakai untuk perencanaan pengiriman</span>
    </label>
    @endif

    <div class="coverage-actions">
        <a href="{{ route('delivery-coverage.index') }}" class="dms-btn dms-btn-outline"><i class="bi bi-arrow-left"></i> Batal</a>
        <button type="submit" class="dms-btn dms-btn-primary"><i class="bi bi-save"></i> Simpan Coverage</button>
    </div>
</form>

<style>
.coverage-section { padding: 1rem 0; border-top: 1px solid var(--k-gray-200); }
.coverage-section:first-of-type { border-top: 0; padding-top: 0; }
.coverage-section-title { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; font-size: .9rem; font-weight: 700; color: var(--k-gray-800); }
.coverage-section-title i { color: var(--k-blue); }
.coverage-help { margin: -.35rem 0 .75rem; color: var(--k-gray-500); font-size: .75rem; }
.coverage-grid { display: grid; gap: 1rem 1.25rem; }
.coverage-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.coverage-span-2 { grid-column: span 2; }
.coverage-span-4 { grid-column: span 4; }
.coverage-columns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; }
.coverage-table { border: 1px solid var(--k-gray-200); border-radius: 6px; overflow: hidden; }
.coverage-table-head, .coverage-table-row { display: grid; grid-template-columns: 64px minmax(260px, 1fr) 150px 190px; gap: 1rem; align-items: center; padding: .7rem .85rem; }
.coverage-table-head { background: var(--k-gray-50); color: var(--k-gray-600); font-size: .7rem; font-weight: 700; text-transform: uppercase; }
.coverage-table-row { border-top: 1px solid var(--k-gray-200); cursor: pointer; }
.coverage-table-row small, .coverage-option small, .coverage-address small { display: block; margin-top: .15rem; color: var(--k-gray-500); font-size: .7rem; font-weight: 400; }
.coverage-options { max-height: 245px; overflow: auto; border: 1px solid var(--k-gray-200); border-radius: 6px; }
.coverage-option, .coverage-address { display: flex; align-items: center; gap: .75rem; padding: .7rem .8rem; border-top: 1px solid var(--k-gray-200); cursor: pointer; }
.coverage-option:first-child, .coverage-address:first-child { border-top: 0; }
.coverage-option span, .coverage-address-main { min-width: 0; flex: 1; }
.coverage-address-list { border: 1px solid var(--k-gray-200); border-radius: 6px; max-height: 330px; overflow: auto; }
.coverage-active { display: inline-flex; align-items: center; gap: .6rem; margin-top: .75rem; font-size: .8rem; font-weight: 600; }
.coverage-actions { display: flex; justify-content: flex-end; gap: .75rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200); }
@media (max-width: 900px) {
    .coverage-grid-4, .coverage-columns { grid-template-columns: 1fr; }
    .coverage-span-2, .coverage-span-4 { grid-column: auto; }
    .coverage-table { overflow-x: auto; }
    .coverage-table-head, .coverage-table-row { min-width: 720px; }
}
</style>
