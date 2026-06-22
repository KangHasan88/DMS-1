@php
    $session = $session ?? null;
    $selectedBranch = old('company_branch_id', $session?->company_branch_id ?? $selectedBranchId);
    $selectedStatus = old('status', $session?->status ?? \App\Models\DeliveryRouteSession::STATUS_PLANNED);
    $selectedMode = old('selling_mode', $session?->selling_mode ?? \App\Models\DeliveryRouteSession::MODE_FULL_CANVAS);
@endphp

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom: 1rem;">
    <ul style="margin: 0; padding-left: 1.25rem;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="route-form-grid">
    <section class="route-panel">
        <div class="route-panel-title">
            <i class="bi bi-diagram-3"></i>
            <span>Informasi Rute</span>
        </div>
        <div class="route-fields two-col">
            <div class="form-group">
                <label>Cabang <span class="text-danger">*</span></label>
                <select name="company_branch_id" class="form-control" {{ $branchScopeId ? 'disabled' : '' }} required>
                    @foreach($companyBranches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) $selectedBranch === (string) $branch->id)>
                        {{ $branch->name }} - {{ $branch->code }}
                    </option>
                    @endforeach
                </select>
                @if($branchScopeId)
                    <input type="hidden" name="company_branch_id" value="{{ $selectedBranch }}">
                @endif
            </div>
            <div class="form-group">
                <label>Tanggal Rute <span class="text-danger">*</span></label>
                <input type="date" name="route_date" class="form-control"
                       value="{{ old('route_date', optional($session?->route_date)->format('Y-m-d') ?? now()->toDateString()) }}" required>
            </div>
            <div class="form-group">
                <label>Area Sales <span class="text-danger">*</span></label>
                <select name="sales_territory_id" class="form-control" required>
                    <option value="">-- Pilih Area --</option>
                    @foreach($territories as $territory)
                    <option value="{{ $territory->id }}" @selected((string) old('sales_territory_id', $session?->sales_territory_id) === (string) $territory->id)>
                        {{ $territory->code }} - {{ $territory->name }}
                    </option>
                    @endforeach
                </select>
                <small class="dms-muted">Area dipakai untuk canvas route dan coverage customer.</small>
            </div>
            <div class="form-group">
                <label>Mode Penjualan <span class="text-danger">*</span></label>
                <select name="selling_mode" class="form-control" required>
                    @foreach(\App\Models\DeliveryRouteSession::MODE_LIST as $value => $label)
                    <option value="{{ $value }}" @selected($selectedMode === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="route-panel">
        <div class="route-panel-title">
            <i class="bi bi-truck"></i>
            <span>Penugasan</span>
        </div>
        <div class="route-fields two-col">
            <div class="form-group">
                <label>Sales Owner <span class="text-danger">*</span></label>
                <select name="salesperson_id" class="form-control" required>
                    <option value="">-- Pilih Sales --</option>
                    @foreach($salespeople as $salesperson)
                    <option value="{{ $salesperson->id }}" @selected((string) old('salesperson_id', $session?->salesperson_id) === (string) $salesperson->id)>
                        {{ $salesperson->name }}{{ $salesperson->companyBranch?->code ? ' - ' . $salesperson->companyBranch->code : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Driver <span class="text-danger">*</span></label>
                <select name="driver_id" class="form-control" required>
                    <option value="">-- Pilih Driver --</option>
                    @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" @selected((string) old('driver_id', $session?->driver_id) === (string) $driver->id)>
                        {{ $driver->name }}{{ $driver->companyBranch?->code ? ' - ' . $driver->companyBranch->code : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group wide">
                <label>Armada <span class="text-danger">*</span></label>
                <select name="delivery_vehicle_id" class="form-control" required>
                    <option value="">-- Pilih Armada --</option>
                    @foreach($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected((string) old('delivery_vehicle_id', $session?->delivery_vehicle_id) === (string) $vehicle->id)>
                        {{ $vehicle->code }} - {{ $vehicle->name }}{{ $vehicle->plate_number ? ' (' . $vehicle->plate_number . ')' : '' }}
                    </option>
                    @endforeach
                </select>
                <small class="dms-muted">Jika armada utama tidak tersedia, pilih armada pengganti yang statusnya tersedia.</small>
            </div>
        </div>
    </section>

    <section class="route-panel route-panel-soft">
        <div class="route-panel-title">
            <i class="bi bi-box-seam"></i>
            <span>Rekap Muatan</span>
        </div>
        <div class="route-fields four-col">
            <div class="form-group">
                <label>Qty Awal</label>
                <input type="number" min="0" name="opening_qty" class="form-control" value="{{ old('opening_qty', $session?->opening_qty ?? 0) }}">
            </div>
            <div class="form-group">
                <label>Terjual</label>
                <input type="number" min="0" name="sold_qty" class="form-control" value="{{ old('sold_qty', $session?->sold_qty ?? 0) }}">
            </div>
            <div class="form-group">
                <label>Kembali</label>
                <input type="number" min="0" name="returned_qty" class="form-control" value="{{ old('returned_qty', $session?->returned_qty ?? 0) }}">
            </div>
            <div class="form-group">
                <label>Rusak</label>
                <input type="number" min="0" name="damaged_qty" class="form-control" value="{{ old('damaged_qty', $session?->damaged_qty ?? 0) }}">
            </div>
        </div>
    </section>

    <section class="route-panel route-panel-soft">
        <div class="route-panel-title">
            <i class="bi bi-clipboard-check"></i>
            <span>Status & Catatan</span>
        </div>
        <div class="route-fields two-col">
            <div class="form-group">
                <label>Status <span class="text-danger">*</span></label>
                <select name="status" class="form-control" required>
                    @foreach(\App\Models\DeliveryRouteSession::STATUS_LIST as $value => $label)
                    <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group wide">
                <label>Catatan</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan route, alasan perubahan, atau instruksi loading">{{ old('notes', $session?->notes) }}</textarea>
            </div>
        </div>
    </section>
</div>

<div class="dms-form-actions">
    <a href="{{ route('delivery-route-sessions.index') }}" class="dms-btn dms-btn-outline">
        <i class="bi bi-arrow-left"></i> Batal
    </a>
    <button type="submit" class="dms-btn dms-btn-primary">
        <i class="bi bi-save"></i> {{ $submitLabel ?? 'Simpan Sesi Rute' }}
    </button>
</div>

@once
<style>
.route-form-grid {
    display: grid;
    gap: 1rem;
}
.route-panel {
    border: 1px solid var(--k-gray-200);
    border-radius: 6px;
    padding: 1rem;
    background: #fff;
}
.route-panel-soft {
    background: #fbfdff;
}
.route-panel-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: .9rem;
    color: var(--k-navy);
    font-size: .95rem;
    font-weight: 700;
}
.route-fields {
    display: grid;
    gap: .9rem 1rem;
}
.route-fields.two-col {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.route-fields.four-col {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}
.route-fields .wide {
    grid-column: 1 / -1;
}
.dms-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--k-gray-200);
}
@media (max-width: 900px) {
    .route-fields.two-col,
    .route-fields.four-col {
        grid-template-columns: 1fr;
    }
}
</style>
@endonce
