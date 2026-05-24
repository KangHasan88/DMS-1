@extends('layouts.sidebar')

@section('page-title', 'Tambah Supplier')
@section('breadcrumb', 'Suppliers / Tambah')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Tambah Supplier Baru</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">Isi form berikut untuk menambahkan supplier/pedagang baru</p>
    </div>

    <form action="{{ route('suppliers.store') }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <!-- Name -->
            <div class="form-group">
                <label class="form-label">Nama Supplier <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Contoh: Pedagang Sayur Makmur">
                @error('name') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" required placeholder="081234567890">
                @error('phone') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Alternate Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon Alternatif</label>
                <input type="text" name="alternate_phone" value="{{ old('alternate_phone') }}" class="form-control" placeholder="081234567891">
                @error('alternate_phone') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="supplier@example.com">
                @error('email') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Market Name -->
            <div class="form-group">
                <label class="form-label">Nama Pasar</label>
                <input type="text" name="market_name" value="{{ old('market_name') }}" class="form-control" placeholder="Pasar Baru, Pasar Lama">
                @error('market_name') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Stall Number -->
            <div class="form-group">
                <label class="form-label">Nomor Lapak/Kios</label>
                <input type="text" name="stall_number" value="{{ old('stall_number') }}" class="form-control" placeholder="A-01, Blok B No. 12">
                @error('stall_number') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Address -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Alamat lengkap supplier">{{ old('address') }}</textarea>
                @error('address') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Category -->
            <div class="form-group">
                <label class="form-label">Kategori <span style="color: var(--k-red);">*</span></label>
                <select name="category" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Specialty -->
            <div class="form-group">
                <label class="form-label">Spesialisasi</label>
                <input type="text" name="specialty" value="{{ old('specialty') }}" class="form-control" placeholder="Contoh: Sayur Organik, Ayam Potong">
                @error('specialty') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Min Order -->
            <div class="form-group">
                <label class="form-label">Minimal Order</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                    <input type="number" name="min_order" value="{{ old('min_order', 0) }}" class="form-control" style="padding-left: 2.5rem;">
                </div>
                <small style="color: var(--k-gray-500);">Minimal pembelian dalam rupiah (0 = tidak ada minimal)</small>
                @error('min_order') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Notes -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Catatan khusus tentang supplier (opsional)">{{ old('notes') }}</textarea>
                @error('notes') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Payment Notes -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Catatan Pembayaran</label>
                <textarea name="payment_notes" class="form-control" rows="2" placeholder="Catatan tentang metode pembayaran, rekening, dll">{{ old('payment_notes') }}</textarea>
                @error('payment_notes') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Active Status -->
            <div class="form-group" style="grid-column: span 2;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span>Aktifkan supplier ini</span>
                </label>
                <small style="color: var(--k-gray-500);">Supplier yang tidak aktif tidak akan ditampilkan di dropdown</small>
            </div>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('suppliers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Supplier
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