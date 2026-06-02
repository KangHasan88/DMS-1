@extends('layouts.sidebar')

@section('page-title', 'Tambah Kategori Produk')
@section('breadcrumb', 'Katalog / Kategori Produk / Tambah')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Tambah Kategori Produk</h3>
        <p class="dms-form-subtitle">Buat kategori katalog sesuai kebutuhan bisnis.</p>
    </div>

    <form action="{{ route('product-categories.store') }}" method="POST">
        @csrf

        <div class="dms-form-grid">
            <div class="form-group">
                <label class="form-label">Nama Kategori <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Contoh: Sayur Organik, Frozen Food">
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control" min="0">
                <small class="dms-form-help">Semakin kecil angka, semakin atas tampilannya.</small>
                @error('sort_order') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Catatan internal tentang kategori ini">{{ old('description') }}</textarea>
                @error('description') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span>Aktifkan kategori ini</span>
                </label>
                <small class="dms-form-help">Kategori aktif akan muncul di form produk.</small>
            </div>
        </div>

        <div class="dms-form-actions">
            <a href="{{ route('product-categories.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Kategori
            </button>
        </div>
    </form>
</div>
@endsection
