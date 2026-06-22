<form action="{{ $action }}" method="POST">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="dms-form-grid">
        <div class="form-group">
            <label class="form-label">Cabang</label>
            <select name="company_branch_id" class="form-control" {{ $branchLocked ? 'disabled' : '' }}>
                <option value="">Global / Semua Cabang</option>
                @foreach($companyBranches as $branch)
                    <option value="{{ $branch->id }}" {{ (string) old('company_branch_id', $vehicle?->company_branch_id) === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} - {{ $branch->code }}
                    </option>
                @endforeach
            </select>
            @if($branchLocked)
                <input type="hidden" name="company_branch_id" value="{{ $companyBranches->first()?->id }}">
            @endif
            <small class="dms-form-help">Pilih cabang pemilik armada. Kosongkan hanya jika armada dipakai lintas cabang.</small>
            @error('company_branch_id') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Kode Armada <span class="dms-required">*</span></label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $vehicle?->code) }}" required placeholder="Contoh: MAI-MTR-001">
            @error('code') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Nama Armada <span class="dms-required">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $vehicle?->name) }}" required placeholder="Contoh: Motor Delivery 01">
            @error('name') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Jenis Armada <span class="dms-required">*</span></label>
            <select name="vehicle_type" class="form-control" required>
                @foreach(\App\Models\DeliveryVehicle::TYPE_LIST as $value => $label)
                    <option value="{{ $value }}" {{ old('vehicle_type', $vehicle?->vehicle_type ?? \App\Models\DeliveryVehicle::TYPE_MOTORCYCLE) === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('vehicle_type') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Nomor Polisi</label>
            <input type="text" name="plate_number" class="form-control" value="{{ old('plate_number', $vehicle?->plate_number) }}" placeholder="Contoh: B 1234 KMG">
            @error('plate_number') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Kapasitas</label>
            <input type="text" name="capacity" class="form-control" value="{{ old('capacity', $vehicle?->capacity) }}" placeholder="Contoh: 50 kg, 20 dus">
            @error('capacity') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Status <span class="dms-required">*</span></label>
            <select name="status" class="form-control" required>
                @foreach(\App\Models\DeliveryVehicle::STATUS_LIST as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $vehicle?->status ?? \App\Models\DeliveryVehicle::STATUS_AVAILABLE) === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <small class="dms-form-help">Status Perbaikan/Tidak Aktif tidak muncul saat penugasan pengiriman.</small>
            @error('status') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Driver Utama</label>
            <select name="primary_driver_id" class="form-control">
                <option value="">-- Belum ditetapkan --</option>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ (string) old('primary_driver_id', $vehicle?->activeDriverAssignment?->driver_id) === (string) $driver->id ? 'selected' : '' }}>
                        {{ $driver->name }} - {{ $driver->companyBranch->code ?? 'Global' }}
                    </option>
                @endforeach
            </select>
            <small class="dms-form-help">Armada ini otomatis dipilih saat driver ditugaskan. Perubahan tetap menyimpan riwayat assignment.</small>
            @error('primary_driver_id') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        @if($vehicle)
        <div class="form-group">
            <label class="form-label">Aktif</label>
            <label class="dms-check" style="min-height: 44px;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $vehicle->is_active) ? 'checked' : '' }}>
                <span>Armada aktif</span>
            </label>
        </div>
        @endif

        <div class="form-group dms-form-span-2">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan servis, kondisi kendaraan, atau PIC armada">{{ old('notes', $vehicle?->notes) }}</textarea>
            @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
        <a href="{{ route('delivery-vehicles.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Batal
        </a>
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi bi-save"></i> Simpan
        </button>
    </div>
</form>
