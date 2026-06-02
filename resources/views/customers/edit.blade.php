@extends('layouts.sidebar')

@section('page-title', 'Edit Pelanggan')
@section('breadcrumb', 'Pelanggan / Edit')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Pelanggan</h3>
        <p class="dms-form-subtitle">Edit informasi pelanggan: {{ $customer->name }}</p>
    </div>

    <form action="{{ route('customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="dms-form-grid">
            <!-- Name -->
            <div class="form-group">
                <label class="form-label">Nama Lengkap <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-control" required>
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon <span class="dms-required">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-control" required>
                @error('phone') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control">
                @error('email') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Tipe Pelanggan -->
            <div class="form-group">
                <label class="form-label">Tipe Pelanggan <span class="dms-required">*</span></label>
                <select name="customer_type" class="form-control" required>
                    <option value="regular" {{ old('customer_type', $customer->customer_type) == 'regular' ? 'selected' : '' }}>Regular</option>
                    <option value="premium" {{ old('customer_type', $customer->customer_type) == 'premium' ? 'selected' : '' }}>Premium</option>
                    <option value="wholesale" {{ old('customer_type', $customer->customer_type) == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                </select>
                @error('customer_type') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Termin Pembayaran</label>
                <select name="payment_term" class="form-control js-payment-term">
                    <option value="cash" {{ old('payment_term', $customer->payment_term ?? 'cash') == 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="credit" {{ old('payment_term', $customer->payment_term ?? 'cash') == 'credit' ? 'selected' : '' }}>Kredit</option>
                </select>
                <small class="dms-form-help">Tunai tidak memakai credit limit. Kredit memakai aturan kredit di bawah.</small>
                @error('payment_term') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Credit Limit</label>
                <input type="number" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}" class="form-control js-credit-control" min="0">
                <small class="dms-form-help">Hanya berlaku untuk termin Kredit.</small>
                @error('credit_limit') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Maks. Outstanding Order</label>
                <input type="number" name="max_outstanding_orders" value="{{ old('max_outstanding_orders', $customer->max_outstanding_orders ?? 0) }}" class="form-control js-credit-control" min="0" max="999">
                <small class="dms-form-help">Hanya berlaku untuk termin Kredit. Isi 0 jika tidak dibatasi.</small>
                @error('max_outstanding_orders') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Status Kredit <span class="dms-required">*</span></label>
                <select name="credit_status" class="form-control js-credit-control" required>
                    <option value="normal" {{ old('credit_status', $customer->credit_status ?? 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="watchlist" {{ old('credit_status', $customer->credit_status) == 'watchlist' ? 'selected' : '' }}>Watchlist</option>
                    <option value="blocked" {{ old('credit_status', $customer->credit_status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
                </select>
                <small class="dms-form-help">Hanya berlaku untuk termin Kredit. Tunai mengabaikan status kredit.</small>
                @error('credit_status') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Catatan Kredit</label>
                <textarea name="credit_notes" class="form-control js-credit-control" rows="2">{{ old('credit_notes', $customer->credit_notes) }}</textarea>
                @error('credit_notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Address -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="3">{{ old('address', $customer->address) }}</textarea>
                @error('address') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Latitude & Longitude -->
            <div class="form-group">
                <label class="form-label">Latitude</label>
                <input type="text" name="latitude" value="{{ old('latitude', $customer->latitude) }}" class="form-control">
                @error('latitude') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Longitude</label>
                <input type="text" name="longitude" value="{{ old('longitude', $customer->longitude) }}" class="form-control">
                @error('longitude') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Notes -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $customer->notes) }}</textarea>
                @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Stats Info -->
            <div class="form-group dms-form-span-2">
                <div style="display: flex; gap: 1rem; padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
                    <div style="flex: 1; text-align: center;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Total Orders</div>
                        <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">{{ number_format($customer->total_orders) }}</div>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Total Belanja</div>
                        <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-green);">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <!-- Active Status -->
            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                    <span>Pelanggan aktif</span>
                </label>
            </div>
        </div>

        <!-- Buttons -->
        <div class="dms-form-actions">
            <a href="{{ route('customers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Pelanggan
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentTerm = document.querySelector('.js-payment-term');
    const creditControls = document.querySelectorAll('.js-credit-control');

    function syncCreditControls() {
        const isCash = paymentTerm && paymentTerm.value === 'cash';

        creditControls.forEach((field) => {
            field.disabled = isCash;
            field.closest('.form-group')?.classList.toggle('dms-field-disabled', isCash);

            if (isCash) {
                if (field.name === 'credit_limit' || field.name === 'max_outstanding_orders') {
                    field.value = 0;
                }
                if (field.name === 'credit_status') {
                    field.value = 'normal';
                }
                if (field.name === 'credit_notes') {
                    field.value = '';
                }
            }
        });
    }

    paymentTerm?.addEventListener('change', syncCreditControls);
    syncCreditControls();
});
</script>

<style>
.dms-field-disabled {
    opacity: 0.58;
}

.dms-field-disabled .form-control {
    background: var(--k-gray-50);
    cursor: not-allowed;
}
</style>

@endsection
