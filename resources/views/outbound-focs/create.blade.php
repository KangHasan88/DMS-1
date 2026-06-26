@extends('layouts.sidebar')

@section('page-title', 'Tambah Bonus / FOC')
@section('breadcrumb', 'Operasional / Bonus / FOC / Tambah')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-green); margin-bottom: 0.25rem;">Tambah Bonus / FOC</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">
            Catat pengeluaran barang gratis untuk pelanggan (promo, bonus, sampel, dukungan, kompensasi).
            <strong>Stok akan berkurang otomatis.</strong>
        </p>
    </div>

    <form action="{{ route('outbound-focs.store') }}" method="POST">
        @csrf

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label">Cabang Operasional</label>
            <select name="company_branch_id" class="form-control" {{ $branchLocked ? 'disabled' : '' }}>
                @foreach($companyBranches as $branch)
                    <option value="{{ $branch->id }}" {{ (string) old('company_branch_id', $prefill['company_branch_id'] ?? $defaultBranchId) === (string) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }} - {{ $branch->code }}
                    </option>
                @endforeach
            </select>
            @if($branchLocked)
                <input type="hidden" name="company_branch_id" value="{{ $prefill['company_branch_id'] ?? $defaultBranchId }}">
            @endif
            <small class="dms-form-help">Cabang yang mencatat pengeluaran barang bonus.</small>
            @error('company_branch_id') <span class="dms-error">{{ $message }}</span> @enderror
        </div>
        
        <!-- Pelanggan Information -->
        <div style="margin-bottom: 1.5rem;">
            <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid var(--k-gray-200);">
                <i class="bi bi-person" style="margin-right: 0.4rem; color: var(--k-green);"></i>
                Informasi Pelanggan
            </h4>
            
            <div class="dms-form-grid">
                <div>
                    <label class="form-label">Nama Pelanggan <span class="dms-required">*</span></label>
                    <input type="text" name="customer_name" class="form-control" required placeholder="Nama pelanggan" value="{{ old('customer_name', $prefill['customer_name'] ?? '') }}">
                    @error('customer_name') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="form-label">Telepon</label>
                    <input type="text" name="customer_phone" class="form-control" placeholder="Nomor telepon" value="{{ old('customer_phone', $prefill['customer_phone'] ?? '') }}">
                    @error('customer_phone') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                
                <div class="dms-form-span-2">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Alamat pelanggan (opsional)">{{ old('address', $prefill['address'] ?? '') }}</textarea>
                    @error('address') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
        
        <!-- FOC Details -->
        <div style="margin-bottom: 1.5rem;">
            <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid var(--k-gray-200);">
                <i class="bi bi-gift" style="margin-right: 0.4rem; color: var(--k-green);"></i>
                Detail Bonus / FOC
            </h4>
            
            <div class="dms-form-grid">
                <div>
                    <label class="form-label">Tanggal <span class="dms-required">*</span></label>
                    <input type="date" name="foc_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    @error('foc_date') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="form-label">Alasan <span class="dms-required">*</span></label>
                    <select name="reason" class="form-control" required>
                        <option value="">-- Pilih Alasan --</option>
                        @foreach($reasons as $key => $label)
                            <option value="{{ $key }}" {{ old('reason', $prefill['reason'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('reason') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                
                <div class="dms-form-span-2">
                    <label class="form-label">Detail Alasan</label>
                    <textarea name="reason_detail" class="form-control" rows="2" placeholder="Detail alasan pemberian barang bonus (opsional)">{{ old('reason_detail', $prefill['reason_detail'] ?? '') }}</textarea>
                    @error('reason_detail') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="form-label">Referensi Order</label>
                    <input type="text" name="reference_order" class="form-control" placeholder="Nomor order terkait (opsional)" value="{{ old('reference_order', $prefill['reference_order'] ?? '') }}">
                    @error('reference_order') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
        
        <!-- Products Section -->
        <div style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--k-gray-800);">
                    <i class="bi bi-box-seam" style="margin-right: 0.4rem; color: var(--k-green);"></i>
                    Daftar Produk
                </h4>
                <button type="button" class="dms-btn dms-btn-outline" onclick="addProductRow()" style="padding: 0.3rem 0.8rem; font-size: 0.7rem;">
                    <i class="bi bi-plus-circle"></i> Tambah Produk
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: white;">
                    <thead>
                        <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                            <th style="padding: 0.6rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 40%;">Produk</th>
                            <th style="padding: 0.6rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 15%;">Jumlah</th>
                            <th style="padding: 0.6rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 20%;">Harga</th>
                            <th style="padding: 0.6rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 20%;">Subtotal</th>
                            <th style="padding: 0.6rem; text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        @php
                            $prefillItems = old('items', $prefill['items'] ?? [['product_id' => '', 'quantity' => 1]]);
                        @endphp
                        @foreach($prefillItems as $index => $prefillItem)
                        <tr class="product-row" style="border-bottom: 1px solid var(--k-gray-200);">
                            <td style="padding: 0.5rem;">
                                <select name="items[{{ $index }}][product_id]" class="product-select" required onchange="updateProductPrice(this, {{ $index }})" style="width: 100%; padding: 0.5rem; border: 1px solid var(--k-gray-300); border-radius: 6px; font-size: 0.75rem;">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price ?? 0 }}" data-stock="{{ $product->current_stock }}" {{ (string) ($prefillItem['product_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>
                                            {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Stok: {{ number_format($product->current_stock) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding: 0.5rem;">
                                <input type="number" name="items[{{ $index }}][quantity]" class="quantity-input" value="{{ $prefillItem['quantity'] ?? 1 }}" min="1" onchange="calculateSubtotal(this, {{ $index }})" style="width: 100%; padding: 0.5rem; border: 1px solid var(--k-gray-300); border-radius: 6px; font-size: 0.75rem;">
                            </td>
                            <td style="padding: 0.5rem;">
                                <span class="product-price-display" style="font-size: 0.75rem;">Rp 0</span>
                                <input type="hidden" name="items[{{ $index }}][price]" class="price-input" value="0">
                            </td>
                            <td style="padding: 0.5rem;">
                                <span class="subtotal-display" style="font-weight: 500; color: var(--k-green); font-size: 0.75rem;">Rp 0</span>
                                <input type="hidden" name="items[{{ $index }}][subtotal]" class="subtotal-input" value="0">
                            </td>
                            <td style="padding: 0.5rem; text-align: center;">
                                <button type="button" class="remove-btn" onclick="removeProductRow(this)" style="background: none; border: none; color: var(--k-red); cursor: pointer; font-size: 1rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--k-gray-50); border-top: 1px solid var(--k-gray-200);">
                            <td colspan="3" style="padding: 0.6rem; text-align: right; font-weight: 600; font-size: 0.8rem;">Total Nilai:</td>
                            <td colspan="2" style="padding: 0.6rem; font-weight: 700; font-size: 0.9rem; color: var(--k-green);">
                                <span id="grand-total">Rp 0</span>
                                <input type="hidden" name="total" id="total-input" value="0">
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Notes -->
        <div style="margin-bottom: 1.5rem;">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan (opsional)" style="padding: 0.6rem; font-size: 0.8rem;">{{ old('notes', $prefill['notes'] ?? '') }}</textarea>
        </div>
        
        <!-- Buttons -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('outbound-focs.index') }}" class="dms-btn dms-btn-outline" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-save"></i> Simpan & Kurangi Stok
            </button>
        </div>
    </form>
</div>

<script>
let productIndex = {{ count($prefillItems) }};

function addProductRow() {
    const tbody = document.getElementById('products-tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'product-row';
    newRow.style.borderBottom = '1px solid var(--k-gray-200)';
    newRow.innerHTML = `
        <td style="padding: 0.5rem;">
            <select name="items[${productIndex}][product_id]" class="product-select" required onchange="updateProductPrice(this, ${productIndex})" style="width: 100%; padding: 0.5rem; border: 1px solid var(--k-gray-300); border-radius: 6px; font-size: 0.75rem;">
                <option value="">-- Pilih Produk --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price ?? 0 }}" data-stock="{{ $product->current_stock }}">
                        {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Stok: {{ number_format($product->current_stock) }}
                    </option>
                @endforeach
            </select>
        </td>
        <td style="padding: 0.5rem;">
            <input type="number" name="items[${productIndex}][quantity]" class="quantity-input" value="1" min="1" onchange="calculateSubtotal(this, ${productIndex})" style="width: 100%; padding: 0.5rem; border: 1px solid var(--k-gray-300); border-radius: 6px; font-size: 0.75rem;">
        </td>
        <td style="padding: 0.5rem;">
            <span class="product-price-display" style="font-size: 0.75rem;">Rp 0</span>
            <input type="hidden" name="items[${productIndex}][price]" class="price-input" value="0">
        </td>
        <td style="padding: 0.5rem;">
            <span class="subtotal-display" style="font-weight: 500; color: var(--k-green); font-size: 0.75rem;">Rp 0</span>
            <input type="hidden" name="items[${productIndex}][subtotal]" class="subtotal-input" value="0">
        </td>
        <td style="padding: 0.5rem; text-align: center;">
            <button type="button" class="remove-btn" onclick="removeProductRow(this)" style="background: none; border: none; color: var(--k-red); cursor: pointer; font-size: 1rem;">
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
    const priceDisplay = row.querySelector('.product-price-display');
    const priceInput = row.querySelector('.price-input');
    
    if (priceDisplay) {
        priceDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
        if (priceInput) priceInput.value = price;
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
    let total = 0;
    const subtotalInputs = document.querySelectorAll('.subtotal-input');
    subtotalInputs.forEach(input => {
        total += parseInt(input.value) || 0;
    });
    
    document.getElementById('grand-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    document.getElementById('total-input').value = total;
}

// Initial calculation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.product-select').forEach((select, index) => {
        if (select.value) {
            updateProductPrice(select, index);
        }
    });
    calculateGrandTotal();
});
</script>

<style>
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
.remove-btn:hover {
    opacity: 0.7;
}
</style>
@endsection
