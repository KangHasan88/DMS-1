@extends('layouts.sidebar')

@section('page-title', 'Buat Purchase Order')
@section('breadcrumb', 'Purchase Orders / Tambah')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Buat Purchase Order Baru</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">Isi form berikut untuk membuat pesanan pembelian ke pemasok</p>
    </div>

    <form action="{{ route('purchase-orders.store') }}" method="POST">
        @csrf
        
        <!-- Pemasok & Order Info -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="form-group">
                <label class="form-label">Pemasok <span style="color: var(--k-red);">*</span></label>
                <select name="supplier_id" class="form-control" required>
                    <option value="">-- Pilih Pemasok --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }} ({{ $supplier->phone }})
                        </option>
                    @endforeach
                </select>
                <small style="color: var(--k-gray-500);">
                    <a href="{{ route('suppliers.create') }}" target="_blank" style="color: var(--k-green);">+ Tambah Pemasok Baru</a>
                </small>
                @error('supplier_id') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>
            
            <div class="form-group">
                <label class="form-label">Tanggal PO <span style="color: var(--k-red);">*</span></label>
                <input type="date" name="order_date" class="form-control" value="{{ old('order_date', date('Y-m-d')) }}" required>
                @error('order_date') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>
            
            <div class="form-group">
                <label class="form-label">Tanggal Perkiraan Datang</label>
                <input type="date" name="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date') }}">
                @error('expected_delivery_date') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>
        </div>
        
        <!-- Products Section -->
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800);">Daftar Produk</h4>
                <button type="button" class="dms-btn dms-btn-outline" onclick="addProductRow()">
                    <i class="bi bi-plus-circle"></i> Tambah Produk
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="dms-table" id="products-table">
                    <thead>
                         <tr>
                            <th style="width: 35%;">Produk</th>
                            <th style="width: 15%;">Qty</th>
                            <th style="width: 20%;">Harga Beli</th>
                            <th style="width: 20%;">Subtotal</th>
                            <th style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        <tr class="product-row">
                            <td>
                                <select name="items[0][product_id]" class="form-control product-select" required onchange="updateProductPrice(this, 0)">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price ?? 0 }}" data-name="{{ $product->name }}">
                                            {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][quantity]" class="form-control quantity-input" value="1" min="1" onchange="calculateSubtotal(this, 0)">
                            </td>
                            <td>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%);">Rp</span>
                                    <input type="number" name="items[0][price]" class="form-control price-input" value="0" step="1000" onchange="calculateSubtotal(this, 0)" style="padding-left: 2rem;">
                                </div>
                            </td>
                            <td>
                                <span class="subtotal-display">Rp 0</span>
                                <input type="hidden" name="items[0][subtotal]" class="subtotal-input" value="0">
                            </td>
                            <td>
                                <button type="button" class="dms-btn dms-btn-outline" style="padding: 0.2rem 0.5rem; color: var(--k-red);" onclick="removeProductRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--k-gray-50);">
                            <td colspan="3" style="text-align: right; font-weight: 600;">Subtotal: </td>
                            <td colspan="2"><span id="subtotal-total">Rp 0</span><input type="hidden" name="subtotal" id="subtotal-input" value="0"></td>
                        </tr>
                        <tr style="background: var(--k-green-light);">
                            <td colspan="3" style="text-align: right; font-weight: 700; font-size: 1.1rem;">Total: </td>
                            <td colspan="2"><span id="grand-total" style="font-weight: 700; font-size: 1.2rem; color: var(--k-green);">Rp 0</span><input type="hidden" name="total" id="total-input" value="0"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Notes -->
        <div class="form-group" style="margin-bottom: 2rem;">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan untuk pemasok (akan tercetak di PO)">{{ old('notes') }}</textarea>
        </div>
        
        <div class="form-group" style="margin-bottom: 2rem;">
            <label class="form-label">Catatan Internal</label>
            <textarea name="internal_notes" class="form-control" rows="2" placeholder="Catatan internal untuk admin (tidak akan tercetak di PO)">{{ old('internal_notes') }}</textarea>
        </div>
        
        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('purchase-orders.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan PO
            </button>
        </div>
    </form>
</div>

<script>
let productIndex = 1;

function addProductRow() {
    const tbody = document.getElementById('products-tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'product-row';
    newRow.innerHTML = `
        <td>
            <select name="items[${productIndex}][product_id]" class="form-control product-select" required onchange="updateProductPrice(this, ${productIndex})">
                <option value="">-- Pilih Produk --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price ?? 0 }}" data-name="{{ $product->name }}">
                        {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->price, 0, ',', '.') }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="items[${productIndex}][quantity]" class="form-control quantity-input" value="1" min="1" onchange="calculateSubtotal(this, ${productIndex})">
        </td>
        <td>
            <div style="position: relative;">
                <span style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%);">Rp</span>
                <input type="number" name="items[${productIndex}][price]" class="form-control price-input" value="0" step="1000" onchange="calculateSubtotal(this, ${productIndex})" style="padding-left: 2rem;">
            </div>
        </td>
        <td>
            <span class="subtotal-display">Rp 0</span>
            <input type="hidden" name="items[${productIndex}][subtotal]" class="subtotal-input" value="0">
        </td>
        <td>
            <button type="button" class="dms-btn dms-btn-outline" style="padding: 0.2rem 0.5rem; color: var(--k-red);" onclick="removeProductRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    productIndex++;
}

function removeProductRow(button) {
    const row = button.closest('tr');
    row.remove();
    calculateGrandTotal();
}

function updateProductPrice(select, index) {
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption?.getAttribute('data-price') || 0;
    const row = select.closest('tr');
    const priceInput = row.querySelector('.price-input');
    
    if (priceInput && price > 0) {
        priceInput.value = price;
    }
    
    calculateSubtotal(row.querySelector('.quantity-input'), index);
}

function calculateSubtotal(input, index) {
    const row = input.closest('tr');
    const quantity = parseInt(row.querySelector('.quantity-input').value) || 0;
    const price = parseInt(row.querySelector('.price-input').value) || 0;
    const subtotal = quantity * price;
    
    const subtotalDisplay = row.querySelector('.subtotal-display');
    const subtotalInput = row.querySelector('.subtotal-input');
    
    if (subtotalDisplay) {
        subtotalDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
        if (subtotalInput) subtotalInput.value = subtotal;
    }
    
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let subtotal = 0;
    const subtotalInputs = document.querySelectorAll('.subtotal-input');
    subtotalInputs.forEach(input => {
        subtotal += parseInt(input.value) || 0;
    });
    
    document.getElementById('subtotal-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
    document.getElementById('subtotal-input').value = subtotal;
    document.getElementById('grand-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
    document.getElementById('total-input').value = subtotal;
}

// Initial calculation
document.addEventListener('DOMContentLoaded', function() {
    calculateGrandTotal();
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
#products-table td, #products-table th {
    vertical-align: middle;
}
</style>
@endsection
