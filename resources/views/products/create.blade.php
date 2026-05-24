@extends('layouts.sidebar')

@section('page-title', 'Tambah Produk')
@section('breadcrumb', 'Products / Tambah')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Tambah Produk Baru</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">Isi form berikut untuk menambahkan produk baru</p>
    </div>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem;">
            <!-- Left Column - Image -->
            <div>
                <div style="text-align: center; padding: 1.5rem; background: var(--k-gray-50); border-radius: 12px; border: 1px solid var(--k-gray-200);">
                    <label class="form-label" style="font-weight: 600; margin-bottom: 1rem; display: block;">Gambar Produk</label>
                    
                    <!-- Preview Image -->
                    <div id="image-preview" style="width: 200px; height: 200px; margin: 0 auto 1rem; border-radius: 12px; overflow: hidden; border: 2px dashed var(--k-gray-300); background: var(--k-white); display: flex; align-items: center; justify-content: center;">
                        <div style="text-align: center;">
                            <i class="bi bi-image" style="font-size: 3rem; color: var(--k-gray-400);"></i>
                            <p style="font-size: 0.7rem; color: var(--k-gray-500); margin-top: 0.5rem;">Preview gambar</p>
                        </div>
                    </div>
                    
                    <!-- Upload Button -->
                    <div style="margin-bottom: 0.5rem;">
                        <label for="image" class="dms-btn dms-btn-outline" style="cursor: pointer; display: inline-flex; width: auto;">
                            <i class="bi bi-upload"></i> Pilih Gambar
                        </label>
                        <input type="file" name="image" id="image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>
                    <small style="color: var(--k-gray-500);">Format: JPG, PNG. Maks: 2MB</small>
                    @error('image') <div style="color: var(--k-red); font-size: 0.75rem; margin-top: 0.5rem;">{{ $message }}</div> @enderror
                </div>
            </div>

            <!-- Right Column - Form Fields -->
            <div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <!-- Product Name -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" style="font-weight: 600;">Nama Produk <span style="color: var(--k-red);">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Contoh: Kangkung, Daging Ayam, Cabai Merah">
                        @error('name') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Category -->
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-control">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Sayur" {{ old('category') == 'Sayur' ? 'selected' : '' }}>Sayur</option>
                            <option value="Buah" {{ old('category') == 'Buah' ? 'selected' : '' }}>Buah</option>
                            <option value="Lauk" {{ old('category') == 'Lauk' ? 'selected' : '' }}>Lauk</option>
                            <option value="Bumbu" {{ old('category') == 'Bumbu' ? 'selected' : '' }}>Bumbu</option>
                            <option value="Lainnya" {{ old('category') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                        @error('category') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Unit (Dropdown from Master) -->
                    <div class="form-group">
                        <label class="form-label">Satuan <span style="color: var(--k-red);">*</span></label>
                        <select name="unit_id" class="form-control" required>
                            <option value="">-- Pilih Satuan --</option>
                            @foreach(\App\Models\Unit::active()->orderBy('sort_order')->orderBy('name')->get() as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->display_name }}
                                    @if($unit->category) 
                                        <span style="color: var(--k-gray-500); font-size: 0.7rem;">({{ $unit->category }})</span>
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small style="color: var(--k-gray-500);">
                            <i class="bi bi-info-circle"></i> 
                            <a href="{{ route('units.index') }}" target="_blank" style="color: var(--k-green);">Kelola satuan</a>
                        </small>
                        @error('unit_id') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Selling Price -->
                    <div class="form-group">
                        <label class="form-label">Harga Jual <span style="color: var(--k-red);">*</span></label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                            <input type="number" name="price" value="{{ old('price') }}" class="form-control" required placeholder="0" style="padding-left: 2.5rem;">
                        </div>
                        @error('price') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Base Price (from market) -->
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Pasar)</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                            <input type="number" name="base_price" value="{{ old('base_price') }}" class="form-control" placeholder="0" style="padding-left: 2.5rem;">
                        </div>
                        <small style="color: var(--k-gray-500);">Harga beli dari pedagang pasar (untuk perhitungan margin)</small>
                        @error('base_price') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Deskripsi Produk</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Contoh: Sayur segar langsung dari pasar tradisional...">{{ old('description') }}</textarea>
                        @error('description') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <span>Aktifkan produk ini</span>
                        </label>
                        <small style="color: var(--k-gray-500);">Produk yang tidak aktif tidak akan ditampilkan ke customer</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('products.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Produk
            </button>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            var preview = document.getElementById('image-preview');
            preview.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;" id="preview-image">';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

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