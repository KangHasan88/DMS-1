@extends('layouts.sidebar')

@section('page-title', 'Edit Satuan')
@section('breadcrumb', 'Master / Satuan / Edit')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Satuan</h3>
        <p class="dms-form-subtitle">Edit informasi satuan: {{ $unit->name }}</p>
    </div>

    <form action="{{ route('units.update', $unit) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="dms-form-grid">
            <div class="form-group">
                <label class="form-label">Nama Satuan <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name', $unit->name) }}" class="form-control" required>
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Kode <span class="dms-required">*</span></label>
                <input type="text" name="code" value="{{ old('code', $unit->code) }}" class="form-control" required>
                <small class="dms-form-help">Kode unik untuk sistem</small>
                @error('code') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Simbol</label>
                <input type="text" name="symbol" value="{{ old('symbol', $unit->symbol) }}" class="form-control">
                <small class="dms-form-help">Simbol singkatan (opsional)</small>
                @error('symbol') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="category" class="form-control">
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Berat" {{ old('category', $unit->category) == 'Berat' ? 'selected' : '' }}>Berat</option>
                    <option value="Jumlah" {{ old('category', $unit->category) == 'Jumlah' ? 'selected' : '' }}>Jumlah</option>
                    <option value="Volume" {{ old('category', $unit->category) == 'Volume' ? 'selected' : '' }}>Volume</option>
                    <option value="Panjang" {{ old('category', $unit->category) == 'Panjang' ? 'selected' : '' }}>Panjang</option>
                    <option value="Lainnya" {{ old('category', $unit->category) == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                </select>
                @error('category') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $unit->sort_order) }}" class="form-control">
                <small class="dms-form-help">Semakin kecil angka, semakin atas tampilannya</small>
                @error('sort_order') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $unit->description) }}</textarea>
                @error('description') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $unit->is_active) ? 'checked' : '' }}>
                    <span>Satuan aktif</span>
                </label>
            </div>
        </div>

        <div class="dms-form-actions">
            <a href="{{ route('units.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Satuan
            </button>
        </div>
    </form>
</div>

@endsection