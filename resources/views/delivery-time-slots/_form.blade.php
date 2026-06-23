@php
    $selectedBranchId = old('company_branch_id', $slot?->company_branch_id);
    $selectedBranch = $companyBranches->firstWhere('id', (int) $selectedBranchId);
@endphp

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
                    <option value="{{ $branch->id }}" {{ (string) old('company_branch_id', $slot?->company_branch_id) === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} - {{ $branch->code }}{{ $branch->is_active ? '' : ' - nonaktif' }}
                    </option>
                @endforeach
            </select>
            @if($branchLocked)
                <input type="hidden" name="company_branch_id" value="{{ $companyBranches->first()?->id }}">
            @endif
            @if($selectedBranch && !$selectedBranch->is_active)
                <small class="dms-form-help" style="color: var(--k-orange); font-weight: 600;">Cabang ini sedang nonaktif, tapi tetap ditampilkan karena masih tersimpan di slot waktu ini.</small>
            @endif
            <small class="dms-form-help">Kosongkan jika slot waktu berlaku untuk semua cabang.</small>
            @error('company_branch_id') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Nama Slot <span class="dms-required">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $slot?->name) }}" required placeholder="Contoh: Pagi, Siang, Malam">
            @error('name') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Jam Mulai <span class="dms-required">*</span></label>
            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $slot?->start_time ? substr($slot->start_time, 0, 5) : '') }}" required>
            @error('start_time') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Jam Selesai <span class="dms-required">*</span></label>
            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $slot?->end_time ? substr($slot->end_time, 0, 5) : '') }}" required>
            @error('end_time') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Label Periode</label>
            <input type="text" name="period_label" class="form-control" value="{{ old('period_label', $slot?->period_label) }}" placeholder="Contoh: Pagi">
            @error('period_label') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Urutan</label>
            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $slot?->sort_order ?? 0) }}" min="0">
            @error('sort_order') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        @if($slot)
        <div class="form-group">
            <label class="form-label">Status</label>
            <label class="dms-check" style="min-height: 44px;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $slot->is_active) ? 'checked' : '' }}>
                <span>Aktif</span>
            </label>
        </div>
        @endif
    </div>

    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
        <a href="{{ route('delivery-time-slots.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Batal
        </a>
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi bi-save"></i> Simpan
        </button>
    </div>
</form>
