@extends('layouts.sidebar')

@section('page-title', 'Tambah Tipe Pelanggan')
@section('breadcrumb', 'Pelanggan / Tipe Pelanggan / Tambah')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Tambah Tipe Pelanggan</h3>
        <p class="dms-form-subtitle">Buat segmentasi pelanggan sesuai kebutuhan bisnis.</p>
    </div>

    <form action="{{ route('customer-types.store') }}" method="POST">
        @csrf

        <div class="dms-form-grid">
            <div class="form-group">
                <label class="form-label">Nama Tipe <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Contoh: Reseller, Corporate, VIP">
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
                <textarea name="description" class="form-control" rows="3" placeholder="Catatan internal tentang tipe pelanggan ini">{{ old('description') }}</textarea>
                @error('description') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span>Aktifkan tipe ini</span>
                </label>
                <small class="dms-form-help">Tipe aktif akan muncul di form pelanggan.</small>
            </div>
        </div>

        <div class="dms-form-actions">
            <a href="{{ route('customer-types.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Tipe
            </button>
        </div>
    </form>
</div>
@endsection
