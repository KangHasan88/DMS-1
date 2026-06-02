@extends('layouts.sidebar')

@section('page-title', 'Edit Tipe Pelanggan')
@section('breadcrumb', 'Pelanggan / Tipe Pelanggan / Edit')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Tipe Pelanggan</h3>
        <p class="dms-form-subtitle">Edit tipe: {{ $customerType->name }}</p>
    </div>

    <form action="{{ route('customer-types.update', $customerType) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="dms-form-grid">
            <div class="form-group">
                <label class="form-label">Nama Tipe <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name', $customerType->name) }}" class="form-control" required>
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $customerType->sort_order) }}" class="form-control" min="0">
                <small class="dms-form-help">Semakin kecil angka, semakin atas tampilannya.</small>
                @error('sort_order') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $customerType->description) }}</textarea>
                @error('description') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $customerType->is_active) ? 'checked' : '' }}>
                    <span>Tipe aktif</span>
                </label>
            </div>
        </div>

        <div class="dms-form-actions">
            <a href="{{ route('customer-types.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Tipe
            </button>
        </div>
    </form>
</div>
@endsection
