@extends('layouts.sidebar')

@section('page-title', 'Tambah Stok')
@section('breadcrumb', 'Stock / Tambah Stok')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Tambah Stok</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">
            Tambah stok untuk produk: <strong>{{ $product->name }}</strong>
        </p>
    </div>

    <form action="{{ route('stock.add', $product) }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <!-- Current Stock Info -->
            <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
                <div style="font-size: 0.7rem; color: var(--k-gray-500);">Stok Saat Ini</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--k-green);">
                    {{ number_format($product->current_stock) }} {{ $product->unit->name ?? '-' }}
                </div>
            </div>
            
            <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
                <div style="font-size: 0.7rem; color: var(--k-gray-500);">Harga Jual</div>
                <div style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800);">
                    Rp {{ number_format($product->price, 0, ',', '.') }} / {{ $product->unit->name ?? '-' }}
                </div>
            </div>
            
            <!-- Quantity -->
            <div class="form-group">
                <label class="form-label">Jumlah Stok Ditambahkan <span style="color: var(--k-red);">*</span></label>
                <input type="number" name="quantity" class="form-control" required min="1" placeholder="Masukkan jumlah stok">
                @error('quantity') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>
            
            <!-- Stok Setelah Ditambah -->
            <div class="form-group">
                <label class="form-label">Stok Setelah Ditambah</label>
                <input type="text" id="after-stock" class="form-control" readonly disabled>
            </div>
            
            <!-- Reason -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Keterangan <span style="color: var(--k-gray-500);">(Opsional)</span></label>
                <textarea name="reason" class="form-control" rows="2" placeholder="Contoh: Pembelian dari supplier, retur customer, dll">{{ old('reason') }}</textarea>
                @error('reason') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>
        </div>
        
        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('stock.show', $product) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Tambah Stok
            </button>
        </div>
    </form>
</div>

<script>
document.querySelector('input[name="quantity"]').addEventListener('input', function() {
    const currentStock = {{ $product->current_stock }};
    const quantity = parseInt(this.value) || 0;
    const afterStock = currentStock + quantity;
    document.getElementById('after-stock').value = afterStock.toLocaleString('id-ID');
});
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
}
</style>
@endsection