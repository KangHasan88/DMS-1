@extends('layouts.sidebar')

@section('page-title', 'Tambah Customer')
@section('breadcrumb', 'Customers / Tambah')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Tambah Customer Baru</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">Isi form berikut untuk menambahkan customer baru</p>
    </div>

    <form action="{{ route('customers.store') }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <!-- Name -->
            <div class="form-group">
                <label class="form-label">Nama Lengkap <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Contoh: Budi Santoso">
                @error('name') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" required placeholder="Contoh: 081234567890">
                <small style="color: var(--k-gray-500);">Format: 081234567890</small>
                @error('phone') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="customer@example.com">
                @error('email') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Customer Type -->
            <div class="form-group">
                <label class="form-label">Tipe Customer <span style="color: var(--k-red);">*</span></label>
                <select name="customer_type" class="form-control" required>
                    <option value="regular" {{ old('customer_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                    <option value="premium" {{ old('customer_type') == 'premium' ? 'selected' : '' }}>Premium</option>
                    <option value="wholesale" {{ old('customer_type') == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                </select>
                <small style="color: var(--k-gray-500);">Premium: diskon khusus, Wholesale: harga grosir</small>
                @error('customer_type') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Address -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="3" placeholder="Jl. Contoh No. 123, RT/RW, Kelurahan, Kecamatan, Kota">{{ old('address') }}</textarea>
                @error('address') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Latitude & Longitude -->
            <div class="form-group">
                <label class="form-label">Latitude</label>
                <input type="text" name="latitude" value="{{ old('latitude') }}" class="form-control" placeholder="-6.200000">
                <small style="color: var(--k-gray-500);">Koordinat lokasi customer (opsional)</small>
                @error('latitude') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Longitude</label>
                <input type="text" name="longitude" value="{{ old('longitude') }}" class="form-control" placeholder="106.816666">
                @error('longitude') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Notes -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Catatan khusus tentang customer (opsional)">{{ old('notes') }}</textarea>
                @error('notes') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Active Status -->
            <div class="form-group" style="grid-column: span 2;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span>Aktifkan customer ini</span>
                </label>
                <small style="color: var(--k-gray-500);">Customer yang tidak aktif tidak dapat melakukan order</small>
            </div>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('customers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Customer
            </button>
        </div>
    </form>
</div>

<style>
.form-group {
    margin-bottom: 1rem;
}
.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--k-gray-700);
    font-size: 0.85rem;
}
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: var(--k-green);
    box-shadow: 0 0 0 3px var(--k-green-light);
}
textarea.form-control {
    resize: vertical;
    min-height: 80px;
}
</style>
@endsection