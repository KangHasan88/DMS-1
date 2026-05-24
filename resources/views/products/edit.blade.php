@extends('layouts.sidebar')

@section('page-title', 'Edit Produk')
@section('breadcrumb', 'Products / Edit')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Edit Produk</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">Edit informasi produk: {{ $product->name }}</p>
    </div>

    <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem;">
            <!-- Left Column - Image -->
            <div>
                <div style="text-align: center; padding: 1.5rem; background: var(--k-gray-50); border-radius: 12px; border: 1px solid var(--k-gray-200);">
                    <label class="form-label" style="font-weight: 600; margin-bottom: 1rem; display: block;">Gambar Produk</label>
                    
                    <!-- Preview Image -->
                    <div id="image-preview" style="width: 200px; height: 200px; margin: 0 auto 1rem; border-radius: 12px; overflow: hidden; border: 2px solid var(--k-gray-200); background: var(--k-white);">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;" id="preview-image">
                        @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-image" style="font-size: 3rem; color: var(--k-gray-400);"></i>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Upload Button -->
                    <div style="margin-bottom: 0.5rem;">
                        <label for="image" class="dms-btn dms-btn-outline" style="cursor: pointer; display: inline-flex; width: auto;">
                            <i class="bi bi-upload"></i> Ganti Gambar
                        </label>
                        <input type="file" name="image" id="image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>
                    <small style="color: var(--k-gray-500);">Format: JPG, PNG. Maks: 2MB</small>
                    @if($product->image)
                        <div style="margin-top: 0.5rem;">
                            <small style="color: var(--k-gray-500);">Gambar saat ini: {{ basename($product->image) }}</small>
                        </div>
                    @endif
                    @error('image') <div style="color: var(--k-red); font-size: 0.75rem; margin-top: 0.5rem;">{{ $message }}</div> @enderror
                </div>
            </div>

            <!-- Right Column - Form Fields -->
            <div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <!-- Product Name -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" style="font-weight: 600;">Nama Produk <span style="color: var(--k-red);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" required>
                        @error('name') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Category -->
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-control">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Sayur" {{ old('category', $product->category) == 'Sayur' ? 'selected' : '' }}>Sayur</option>
                            <option value="Buah" {{ old('category', $product->category) == 'Buah' ? 'selected' : '' }}>Buah</option>
                            <option value="Lauk" {{ old('category', $product->category) == 'Lauk' ? 'selected' : '' }}>Lauk</option>
                            <option value="Bumbu" {{ old('category', $product->category) == 'Bumbu' ? 'selected' : '' }}>Bumbu</option>
                            <option value="Lainnya" {{ old('category', $product->category) == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                        @error('category') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Unit (Dropdown from Master) -->
                    <div class="form-group">
                        <label class="form-label">Satuan <span style="color: var(--k-red);">*</span></label>
                        <select name="unit_id" class="form-control" required>
                            <option value="">-- Pilih Satuan --</option>
                            @foreach(\App\Models\Unit::active()->orderBy('sort_order')->orderBy('name')->get() as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>
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
                            <input type="number" name="price" value="{{ old('price', $product->price) }}" class="form-control" required style="padding-left: 2.5rem;">
                        </div>
                        @error('price') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Base Price -->
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Pasar)</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                            <input type="number" name="base_price" value="{{ old('base_price', $product->base_price) }}" class="form-control" style="padding-left: 2.5rem;">
                        </div>
                        <small style="color: var(--k-gray-500);">Harga beli dari pedagang pasar</small>
                        @error('base_price') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Deskripsi Produk</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                        @error('description') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Price Change Reason -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">
                            Alasan Perubahan Harga 
                            <span style="color: var(--k-gray-500); font-weight: normal;">(Opsional)</span>
                        </label>
                        <textarea name="price_change_reason" class="form-control" rows="2" placeholder="Contoh: Harga naik dari pasar, promo khusus, penyesuaian margin, dll.">{{ old('price_change_reason') }}</textarea>
                        <small style="color: var(--k-gray-500);">
                            <i class="bi bi-info-circle"></i> 
                            Catatan: ini akan tercatat di history perubahan harga. Kosongkan jika tidak ada perubahan harga.
                        </small>
                        @error('price_change_reason') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                            <span>Produk aktif</span>
                        </label>
                    </div>

                    <!-- Margin Info (if base_price exists) -->
                    @if($product->base_price && $product->price)
                    <div class="form-group" style="grid-column: span 2;">
                        <div style="padding: 0.75rem; background: var(--k-green-light); border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                                <span>Margin per unit:</span>
                                <strong style="color: var(--k-green);">
                                    Rp {{ number_format($product->price - $product->base_price, 0, ',', '.') }}
                                    ({{ round(($product->price - $product->base_price) / $product->price * 100) }}%)
                                </strong>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('products.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Produk
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