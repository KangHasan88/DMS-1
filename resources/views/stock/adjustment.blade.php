@extends('layouts.sidebar')

@section('page-title', 'Penyesuaian Stok')
@section('breadcrumb', 'Stock / Penyesuaian Stok')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-green); margin-bottom: 0.25rem;">Penyesuaian Stok</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">
            Sesuaikan stok fisik dengan stok sistem untuk produk: <strong>{{ $product->name }}</strong>
        </p>
        <div style="margin-top: 0.5rem; padding: 0.5rem 0.75rem; background: var(--k-gray-50); border-radius: 6px; font-size: 0.75rem; color: var(--k-gray-600);">
            <i class="bi bi-info-circle"></i> 
            Penyesuaian stok digunakan untuk mengoreksi stok fisik yang tidak sesuai dengan sistem.
            Setiap perubahan akan tercatat di riwayat pergerakan stok.
        </div>
    </div>

    <form action="{{ route('stock.adjustment', $product) }}" method="POST">
        @csrf
        
        <!-- Stock Info Cards -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
            <div style="padding: 1rem; background: var(--k-orange-light); border-radius: 8px; text-align: center;">
                <div style="font-size: 0.7rem; color: var(--k-gray-600);">Stok Saat Ini (Sistem)</div>
                <div style="font-size: 1.8rem; font-weight: 700; color: var(--k-orange);">
                    {{ number_format($product->current_stock) }}
                </div>
                <div style="font-size: 0.7rem; color: var(--k-gray-500);">{{ $product->unit->name ?? '-' }}</div>
            </div>
            
            <div style="padding: 1rem; background: var(--k-green-light); border-radius: 8px; text-align: center;">
                <div style="font-size: 0.7rem; color: var(--k-gray-600);">Harga Jual</div>
                <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-green);">
                    Rp {{ number_format($product->price, 0, ',', '.') }}
                </div>
                <div style="font-size: 0.7rem; color: var(--k-gray-500);">per {{ $product->unit->name ?? '-' }}</div>
            </div>
        </div>
        
        <!-- Adjustment Form -->
        <div style="margin-bottom: 1.5rem;">
            <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid var(--k-gray-200);">
                <i class="bi bi-sliders2" style="margin-right: 0.4rem; color: var(--k-green);"></i>
                Data Penyesuaian
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Stok Baru (Fisik) <span style="color: var(--k-red);">*</span></label>
                    <input type="number" name="new_quantity" id="new_quantity" class="form-control" required min="0" placeholder="Masukkan jumlah stok fisik" value="{{ old('new_quantity') }}">
                    <small style="color: var(--k-gray-500);">Masukkan jumlah stok hasil pengecekan fisik</small>
                    @error('new_quantity') <span style="color: var(--k-red); font-size: 0.7rem;">{{ $message }}</span> @enderror
                </div>
                
                <div class="form-group">
                    <label class="form-label">Selisih</label>
                    <div id="difference" style="font-size: 1.1rem; font-weight: 600; padding: 0.5rem 0.75rem; background: var(--k-gray-50); border-radius: 6px; border: 1px solid var(--k-gray-200);">
                        <span id="difference-value">0</span>
                    </div>
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Alasan Penyesuaian <span style="color: var(--k-red);">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Contoh: Stock opname, barang rusak, barang hilang, kadaluarsa, dll">{{ old('reason') }}</textarea>
                    @error('reason') <span style="color: var(--k-red); font-size: 0.7rem;">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
        
        <!-- Preview Section -->
        <div style="margin-bottom: 1.5rem;">
            <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid var(--k-gray-200);">
                <i class="bi bi-eye" style="margin-right: 0.4rem; color: var(--k-green);"></i>
                Preview Perubahan
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 6px; text-align: center;">
                    <div style="font-size: 0.65rem; color: var(--k-gray-500);">Stok Sebelum</div>
                    <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-700);">
                        <span id="preview-before">{{ number_format($product->current_stock) }}</span>
                    </div>
                </div>
                
                <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 6px; text-align: center;">
                    <div style="font-size: 0.65rem; color: var(--k-gray-500);">Stok Sesudah</div>
                    <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-green);">
                        <span id="preview-after">{{ number_format($product->current_stock) }}</span>
                    </div>
                </div>
                
                <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 6px; text-align: center;">
                    <div style="font-size: 0.65rem; color: var(--k-gray-500);">Perubahan</div>
                    <div style="font-size: 1.2rem; font-weight: 600;">
                        <span id="preview-change">0</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Buttons -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('stock.show', $product) }}" class="dms-btn dms-btn-outline" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-save"></i> Simpan Penyesuaian
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentStock = {{ $product->current_stock }};
    const newQuantityInput = document.getElementById('new_quantity');
    const previewBefore = document.getElementById('preview-before');
    const previewAfter = document.getElementById('preview-after');
    const previewChange = document.getElementById('preview-change');
    const differenceValue = document.getElementById('difference-value');
    
    function updatePreview() {
        let newQuantity = parseInt(newQuantityInput.value) || 0;
        let diff = newQuantity - currentStock;
        let afterStock = newQuantity;
        
        // Update preview
        previewAfter.innerText = afterStock.toLocaleString('id-ID');
        previewChange.innerText = (diff > 0 ? '+' : '') + diff.toLocaleString('id-ID');
        
        // Update difference display
        if (diff > 0) {
            differenceValue.innerHTML = '<span style="color: var(--k-green);"><i class="bi bi-arrow-up"></i> +' + diff.toLocaleString('id-ID') + '</span>';
            previewChange.style.color = 'var(--k-green)';
        } else if (diff < 0) {
            differenceValue.innerHTML = '<span style="color: var(--k-red);"><i class="bi bi-arrow-down"></i> ' + diff.toLocaleString('id-ID') + '</span>';
            previewChange.style.color = 'var(--k-red)';
        } else {
            differenceValue.innerHTML = '<span style="color: var(--k-gray-500);">0 (Tidak berubah)</span>';
            previewChange.style.color = 'var(--k-gray-500)';
        }
        
        // Update after stock color
        if (afterStock == 0) {
            previewAfter.style.color = 'var(--k-red)';
        } else if (afterStock < currentStock) {
            previewAfter.style.color = 'var(--k-orange)';
        } else {
            previewAfter.style.color = 'var(--k-green)';
        }
    }
    
    newQuantityInput.addEventListener('input', updatePreview);
    updatePreview();
});
</script>

<style>
.form-group {
    margin-bottom: 1rem;
}
.form-label {
    display: block;
    margin-bottom: 0.3rem;
    color: var(--k-gray-700);
    font-size: 0.75rem;
    font-weight: 500;
}
.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 6px;
    font-size: 0.8rem;
    transition: all 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: var(--k-green);
    box-shadow: 0 0 0 2px var(--k-green-light);
}
textarea.form-control {
    resize: vertical;
}
.dms-btn {
    padding: 0.4rem 1rem;
    border-radius: 1.5rem;
    font-weight: 500;
    font-size: 0.7rem;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.2s;
}
.dms-btn-primary {
    background: var(--k-green);
    color: white;
}
.dms-btn-primary:hover {
    background: var(--k-green-dark);
}
.dms-btn-outline {
    background: transparent;
    border: 1px solid var(--k-gray-300);
    color: var(--k-gray-600);
}
.dms-btn-outline:hover {
    border-color: var(--k-green);
    color: var(--k-green);
}
</style>
@endsection