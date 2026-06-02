@extends('layouts.sidebar')

@section('page-title', 'Tambah Pemasok')
@section('breadcrumb', 'Pemasok / Tambah')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Tambah Pemasok Baru</h3>
        <p class="dms-form-subtitle">Isi form berikut untuk menambahkan pemasok atau pedagang baru</p>
    </div>

    <form action="{{ route('suppliers.store') }}" method="POST">
        @csrf
        
        <div class="dms-form-grid">
            <!-- Name -->
            <div class="form-group">
                <label class="form-label">Nama Pemasok <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Contoh: Pedagang Sayur Makmur">
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon <span class="dms-required">*</span></label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" required placeholder="081234567890">
                @error('phone') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Alternate Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon Alternatif</label>
                <input type="text" name="alternate_phone" value="{{ old('alternate_phone') }}" class="form-control" placeholder="081234567891">
                @error('alternate_phone') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="pemasok@example.com">
                @error('email') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Market Name -->
            <div class="form-group">
                <label class="form-label">Nama Pasar</label>
                <input type="text" name="market_name" value="{{ old('market_name') }}" class="form-control" placeholder="Pasar Baru, Pasar Lama">
                @error('market_name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Stall Number -->
            <div class="form-group">
                <label class="form-label">Nomor Lapak/Kios</label>
                <input type="text" name="stall_number" value="{{ old('stall_number') }}" class="form-control" placeholder="A-01, Blok B No. 12">
                @error('stall_number') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Address -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Alamat lengkap pemasok">{{ old('address') }}</textarea>
                @error('address') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Category -->
            <div class="form-group">
                <label class="form-label">Kategori <span class="dms-required">*</span></label>
                <select name="category" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="dms-form-help">
                    <i class="bi bi-info-circle"></i>
                    <a href="{{ route('supplier-categories.index') }}" target="_blank" style="color: var(--k-green);">Kelola kategori pemasok</a>
                </small>
                @error('category') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Specialty -->
            <div class="form-group">
                <label class="form-label">Spesialisasi</label>
                <input type="text" name="specialty" value="{{ old('specialty') }}" class="form-control" placeholder="Contoh: Sayur Organik, Ayam Potong">
                @error('specialty') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Min Order -->
            <div class="form-group">
                <label class="form-label">Minimal Order</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                    <input type="number" name="min_order" value="{{ old('min_order', 0) }}" class="form-control" style="padding-left: 2.5rem;">
                </div>
                <small class="dms-form-help">Minimal pembelian dalam rupiah (0 = tidak ada minimal)</small>
                @error('min_order') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Notes -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Catatan khusus tentang pemasok (opsional)">{{ old('notes') }}</textarea>
                @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Payment Notes -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan Pembayaran</label>
                <textarea name="payment_notes" class="form-control" rows="2" placeholder="Catatan tentang metode pembayaran, rekening, dll">{{ old('payment_notes') }}</textarea>
                @error('payment_notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Active Status -->
            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span>Aktifkan pemasok ini</span>
                </label>
                <small class="dms-form-help">Pemasok yang tidak aktif tidak akan ditampilkan di dropdown</small>
            </div>
        </div>

        <!-- Buttons -->
        <div class="dms-form-actions">
            <a href="{{ route('suppliers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Pemasok
            </button>
        </div>
    </form>
</div>

@endsection
