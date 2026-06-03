@extends('layouts.sidebar')

@section('page-title', 'Edit Pasar Pemasok')
@section('breadcrumb', 'Pemasok / Pasar Pemasok / Edit')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Pasar Pemasok</h3>
        <p class="dms-form-subtitle">Edit pasar: {{ $supplierMarket->name }}</p>
    </div>

    <form action="{{ route('supplier-markets.update', $supplierMarket) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="dms-form-grid">
            <div class="form-group">
                <label class="form-label">Nama Pasar <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name', $supplierMarket->name) }}" class="form-control" required>
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $supplierMarket->sort_order) }}" class="form-control" min="0">
                <small class="dms-form-help">Semakin kecil angka, semakin atas tampilannya.</small>
                @error('sort_order') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $supplierMarket->description) }}</textarea>
                @error('description') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supplierMarket->is_active) ? 'checked' : '' }}>
                    <span>Pasar aktif</span>
                </label>
            </div>
        </div>

        <div class="dms-form-actions">
            <a href="{{ route('supplier-markets.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Pasar
            </button>
        </div>
    </form>
</div>
@endsection
