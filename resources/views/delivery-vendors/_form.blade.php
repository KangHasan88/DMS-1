@php
    $selectedBranchId = old('company_branch_id', $vendor?->company_branch_id);
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
                    <option value="{{ $branch->id }}" {{ (string) old('company_branch_id', $vendor?->company_branch_id) === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} - {{ $branch->code }}{{ $branch->is_active ? '' : ' - nonaktif' }}
                    </option>
                @endforeach
            </select>
            @if($branchLocked)
                <input type="hidden" name="company_branch_id" value="{{ $companyBranches->first()?->id }}">
            @endif
            @if($selectedBranch && !$selectedBranch->is_active)
                <small class="dms-form-help" style="color: var(--k-orange); font-weight: 600;">Cabang ini sedang nonaktif, tapi tetap ditampilkan karena masih tersimpan di ekspedisi ini.</small>
            @endif
            <small class="dms-form-help">Kosongkan jika ekspedisi bisa dipakai semua cabang.</small>
            @error('company_branch_id') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Nama Ekspedisi <span class="dms-required">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $vendor?->name) }}" required placeholder="Contoh: JNE, Lalamove, Vendor Trucking">
            @error('name') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Kode</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $vendor?->code) }}" placeholder="Contoh: JNE">
            @error('code') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Tipe Vendor <span class="dms-required">*</span></label>
            <select name="vendor_type" class="form-control" required>
                @foreach([
                    \App\Models\DeliveryVendor::TYPE_EXPEDITION => 'Ekspedisi',
                    \App\Models\DeliveryVendor::TYPE_INSTANT => 'Instant Courier',
                    \App\Models\DeliveryVendor::TYPE_TRUCKING => 'Trucking',
                    \App\Models\DeliveryVendor::TYPE_CUSTOM => 'Lainnya',
                ] as $value => $label)
                    <option value="{{ $value }}" {{ old('vendor_type', $vendor?->vendor_type ?? \App\Models\DeliveryVendor::TYPE_EXPEDITION) === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('vendor_type') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Kontak Person</label>
            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $vendor?->contact_person) }}">
            @error('contact_person') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Telepon</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $vendor?->phone) }}">
            @error('phone') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Termin Tagihan <span class="dms-required">*</span></label>
            <select name="payment_term" class="form-control" required>
                @foreach([
                    \App\Models\DeliveryVendor::PAYMENT_TERM_CASH => 'Tunai',
                    \App\Models\DeliveryVendor::PAYMENT_TERM_INVOICE => 'Per Invoice',
                    \App\Models\DeliveryVendor::PAYMENT_TERM_WEEKLY => 'Mingguan',
                    \App\Models\DeliveryVendor::PAYMENT_TERM_MONTHLY => 'Bulanan',
                ] as $value => $label)
                    <option value="{{ $value }}" {{ old('payment_term', $vendor?->payment_term ?? \App\Models\DeliveryVendor::PAYMENT_TERM_INVOICE) === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('payment_term') <span class="dms-error">{{ $message }}</span> @enderror
        </div>

        @if($vendor)
        <div class="form-group">
            <label class="form-label">Status</label>
            <label style="display: flex; gap: 0.5rem; align-items: center; min-height: 44px;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $vendor->is_active) ? 'checked' : '' }}>
                Aktif
            </label>
        </div>
        @endif

        <div class="form-group dms-form-span-2">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan billing, PIC, atau SLA pengiriman">{{ old('notes', $vendor?->notes) }}</textarea>
            @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
        <a href="{{ route('delivery-vendors.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Batal
        </a>
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi bi-save"></i> Simpan
        </button>
    </div>
</form>
