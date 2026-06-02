@extends('layouts.sidebar')

@section('page-title', 'Tambah Satuan')
@section('breadcrumb', 'Master / Satuan / Tambah')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Tambah Satuan Baru</h3>
        <p class="dms-form-subtitle">Isi form berikut untuk menambahkan satuan baru</p>
    </div>

    <form action="{{ route('units.store') }}" method="POST">
        @csrf
        
        <div class="dms-form-grid">
            <div class="form-group">
                <label class="form-label">Nama Satuan <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Contoh: Kilogram, Ikat, Butir">
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Kode <span class="dms-required">*</span></label>
                <input type="text" name="code" value="{{ old('code') }}" class="form-control" required placeholder="Contoh: kg, ikat, butir">
                <small class="dms-form-help">Kode unik untuk sistem</small>
                @error('code') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Simbol</label>
                <input type="text" name="symbol" value="{{ old('symbol') }}" class="form-control" placeholder="Contoh: kg, ik, btr">
                <small class="dms-form-help">Simbol singkatan (opsional)</small>
                @error('symbol') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="category" class="form-control">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $category)
                    <option value="{{ $category }}" {{ old('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                    @endforeach
                </select>
                <small class="dms-form-help">
                    <a href="{{ route('unit-categories.index') }}">Kelola kategori satuan</a>
                </small>
                @error('category') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control">
                <small class="dms-form-help">Semakin kecil angka, semakin atas tampilannya</small>
                @error('sort_order') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                @error('description') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span>Aktifkan satuan ini</span>
                </label>
            </div>
        </div>

        <div class="dms-form-actions">
            <a href="{{ route('units.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Satuan
            </button>
        </div>
    </form>
</div>

@endsection
