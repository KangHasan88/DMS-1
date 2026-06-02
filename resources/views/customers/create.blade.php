@extends('layouts.sidebar')

@section('page-title', 'Tambah Pelanggan')
@section('breadcrumb', 'Pelanggan / Tambah')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Tambah Pelanggan Baru</h3>
        <p class="dms-form-subtitle">Isi form berikut untuk menambahkan pelanggan baru</p>
    </div>

    <form action="{{ route('customers.store') }}" method="POST">
        @csrf
        
        <div class="dms-form-grid">
            <!-- Name -->
            <div class="form-group">
                <label class="form-label">Nama Lengkap <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Contoh: Budi Santoso">
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon <span class="dms-required">*</span></label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" required placeholder="Contoh: 081234567890">
                <small class="dms-form-help">Format: 081234567890</small>
                @error('phone') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="pelanggan@example.com">
                @error('email') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Tipe Pelanggan -->
            <div class="form-group">
                <label class="form-label">Tipe Pelanggan <span class="dms-required">*</span></label>
                <select name="customer_type" class="form-control" required>
                    <option value="regular" {{ old('customer_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                    <option value="premium" {{ old('customer_type') == 'premium' ? 'selected' : '' }}>Premium</option>
                    <option value="wholesale" {{ old('customer_type') == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                </select>
                <small class="dms-form-help">Premium: diskon khusus, Wholesale: harga grosir</small>
                @error('customer_type') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Termin Pembayaran</label>
                <select name="payment_term" class="form-control js-payment-term">
                    <option value="cash" {{ old('payment_term', 'cash') == 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="credit" {{ old('payment_term') == 'credit' ? 'selected' : '' }}>Kredit</option>
                </select>
                <small class="dms-form-help">Tunai tidak memakai credit limit. Kredit memakai aturan kredit di bawah.</small>
                @error('payment_term') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Credit Limit</label>
                <input type="number" name="credit_limit" value="{{ old('credit_limit', 0) }}" class="form-control js-credit-control" min="0" placeholder="0">
                <small class="dms-form-help">Hanya berlaku untuk termin Kredit.</small>
                @error('credit_limit') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Maks. Outstanding Order</label>
                <input type="number" name="max_outstanding_orders" value="{{ old('max_outstanding_orders', 0) }}" class="form-control js-credit-control" min="0" max="999" placeholder="0">
                <small class="dms-form-help">Hanya berlaku untuk termin Kredit. Isi 0 jika tidak dibatasi.</small>
                @error('max_outstanding_orders') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Status Kredit <span class="dms-required">*</span></label>
                <select name="credit_status" class="form-control js-credit-control" required>
                    <option value="normal" {{ old('credit_status', 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="watchlist" {{ old('credit_status') == 'watchlist' ? 'selected' : '' }}>Watchlist</option>
                    <option value="blocked" {{ old('credit_status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
                </select>
                <small class="dms-form-help">Hanya berlaku untuk termin Kredit. Tunai mengabaikan status kredit.</small>
                @error('credit_status') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Catatan Kredit</label>
                <textarea name="credit_notes" class="form-control js-credit-control" rows="2" placeholder="Catatan pembayaran, termin, atau alasan watchlist">{{ old('credit_notes') }}</textarea>
                @error('credit_notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Address -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="3" placeholder="Jl. Contoh No. 123, RT/RW, Kelurahan, Kecamatan, Kota">{{ old('address') }}</textarea>
                @error('address') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Latitude & Longitude -->
            <div class="form-group">
                <label class="form-label">Latitude</label>
                <input type="text" name="latitude" value="{{ old('latitude') }}" class="form-control" placeholder="-6.200000">
                <small class="dms-form-help">Koordinat lokasi pelanggan (opsional)</small>
                @error('latitude') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Longitude</label>
                <input type="text" name="longitude" value="{{ old('longitude') }}" class="form-control" placeholder="106.816666">
                @error('longitude') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Notes -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Catatan khusus tentang pelanggan (opsional)">{{ old('notes') }}</textarea>
                @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Active Status -->
            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span>Aktifkan pelanggan ini</span>
                </label>
                <small class="dms-form-help">Pelanggan yang tidak aktif tidak dapat melakukan pesanan</small>
            </div>
        </div>

        <!-- Buttons -->
        <div class="dms-form-actions">
            <a href="{{ route('customers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Pelanggan
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

@endsection
