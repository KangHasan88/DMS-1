@extends('layouts.sidebar')

@section('page-title', 'Edit Kategori Pemasok')
@section('breadcrumb', 'Relasi Bisnis / Kategori Pemasok / Edit')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Kategori Pemasok</h3>
        <p class="dms-form-subtitle">Edit kategori: {{ $supplierCategory->name }}</p>
    </div>

    <form action="{{ route('supplier-categories.update', $supplierCategory) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="dms-form-grid">
            <div class="form-group">
                <label class="form-label">Nama Kategori <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name', $supplierCategory->name) }}" class="form-control" required>
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $supplierCategory->sort_order) }}" class="form-control" min="0">
                <small class="dms-form-help">Semakin kecil angka, semakin atas tampilannya.</small>
                @error('sort_order') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $supplierCategory->description) }}</textarea>
                @error('description') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supplierCategory->is_active) ? 'checked' : '' }}>
                    <span>Kategori aktif</span>
                </label>
            </div>
        </div>

        <div class="dms-form-actions">
            <a href="{{ route('supplier-categories.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Kategori
            </button>
        </div>
    </form>
</div>
@endsection
