@extends('layouts.sidebar')

@section('page-title', 'Edit Satuan')
@section('breadcrumb', 'Master / Satuan / Edit')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Edit Satuan</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">Edit informasi satuan: {{ $unit->name }}</p>
    </div>

    <form action="{{ route('units.update', $unit) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <div class="form-group">
                <label class="form-label">Nama Satuan <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="name" value="{{ old('name', $unit->name) }}" class="form-control" required>
                @error('name') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Kode <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="code" value="{{ old('code', $unit->code) }}" class="form-control" required>
                <small style="color: var(--k-gray-500);">Kode unik untuk sistem</small>
                @error('code') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Simbol</label>
                <input type="text" name="symbol" value="{{ old('symbol', $unit->symbol) }}" class="form-control">
                <small style="color: var(--k-gray-500);">Simbol singkatan (opsional)</small>
                @error('symbol') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
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
                @error('category') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $unit->sort_order) }}" class="form-control">
                <small style="color: var(--k-gray-500);">Semakin kecil angka, semakin atas tampilannya</small>
                @error('sort_order') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $unit->description) }}</textarea>
                @error('description') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $unit->is_active) ? 'checked' : '' }}>
                    <span>Satuan aktif</span>
                </label>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('units.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Satuan
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