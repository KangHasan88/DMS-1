@extends('layouts.sidebar')

@section('page-title', 'Buat Purchase Order')
@section('breadcrumb', 'Purchase Orders / Tambah')

@php
    $formItems = old('items') ?: [[
        'product_id' => '',
        'quantity' => 1,
        'price' => 0,
        'subtotal' => 0,
        'notes' => '',
    ]];
@endphp

@section('content')
<div class="dms-card">
    <div class="dms-form-header" style="margin-bottom: 1.25rem;">
        <h3 class="dms-form-title">Detail Purchase Order</h3>
        <p class="dms-form-subtitle">Lengkapi pemasok, tanggal dokumen, dan daftar produk sebelum PO diajukan approval.</p>
    </div>

    <form action="{{ route('purchase-orders.store') }}" method="POST">
        @csrf

        <section style="border: 1px solid var(--k-border); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem; background: var(--k-white);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.85rem;">
                <div>
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--k-navy); margin: 0;">Informasi Dokumen</h4>
                    <p class="dms-form-subtitle" style="margin: 0.2rem 0 0;">Data header PO yang menjadi dasar approval dan penerimaan barang.</p>
                </div>
            </div>

            <div class="dms-form-grid" style="grid-template-columns: minmax(320px, 1.4fr) minmax(180px, 0.8fr) minmax(180px, 0.8fr); gap: 1rem; align-items: start;">
                <div class="form-group">
                    <label class="form-label">Pemasok <span class="dms-required">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">-- Pilih Pemasok --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (int) old('supplier_id') === $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}{{ $supplier->phone ? ' (' . $supplier->phone . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="dms-form-help">
                        <a href="{{ route('suppliers.create') }}" target="_blank" style="color: var(--k-navy); font-weight: 700;">+ Tambah Pemasok Baru</a>
                    </small>
                    @error('supplier_id') <span class="dms-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal PO <span class="dms-required">*</span></label>
                    <input type="date" name="order_date" class="form-control" value="{{ old('order_date', date('Y-m-d')) }}" required>
                    @error('order_date') <span class="dms-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Estimasi Datang</label>
                    <input type="date" name="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date') }}">
                    @error('expected_delivery_date') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </section>

        <section style="border: 1px solid var(--k-border); border-radius: 8px; margin-bottom: 1.25rem; background: var(--k-white); overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.95rem 1rem; border-bottom: 1px solid var(--k-border); background: var(--k-gray-50);">
                <div>
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--k-navy); margin: 0;">Item Pembelian</h4>
                    <p class="dms-form-subtitle" style="margin: 0.2rem 0 0;">Masukkan produk, qty, harga beli, dan catatan item.</p>
                </div>
                <button type="button" class="dms-btn dms-btn-outline" onclick="addProductRow()">
                    <i class="bi bi-plus-circle"></i> Tambah Produk
                </button>
            </div>

            @error('items') <span class="dms-error" style="display: block; margin: 0.75rem 1rem 0;">{{ $message }}</span> @enderror

            <div style="overflow-x: auto; padding: 0.85rem 1rem 1rem;">
                <table class="dms-table" id="products-table" style="border: 1px solid var(--k-border); border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr>
                            <th style="width: 36%;">Produk</th>
                            <th style="width: 10%; text-align: right;">Qty</th>
                            <th style="width: 16%; text-align: right;">Harga Beli</th>
                            <th style="width: 16%; text-align: right;">Subtotal</th>
                            <th style="width: 16%;">Catatan</th>
                            <th style="width: 6%; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        @foreach($formItems as $index => $item)
                            @php
                                $itemSubtotal = (int) (($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                            @endphp
                            <tr class="product-row">
                                <td>
                                    <select name="items[{{ $index }}][product_id]" class="form-control product-select" required onchange="updateProductPrice(this)">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}"
                                                data-price="{{ $product->base_price ?: $product->price ?: 0 }}"
                                                {{ (int) ($item['product_id'] ?? 0) === $product->id ? 'selected' : '' }}>
                                                {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->base_price ?: $product->price ?: 0, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("items.$index.product_id") <span class="dms-error">{{ $message }}</span> @enderror
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity-input" value="{{ $item['quantity'] ?? 1 }}" min="1" onchange="calculateSubtotal(this)" style="text-align: right;">
                                    @error("items.$index.quantity") <span class="dms-error">{{ $message }}</span> @enderror
                                </td>
                                <td>
                                    <div style="position: relative;">
                                        <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                                        <input type="number" name="items[{{ $index }}][price]" class="form-control price-input" value="{{ $item['price'] ?? 0 }}" step="1000" min="0" onchange="calculateSubtotal(this)" style="padding-left: 2.25rem; text-align: right;">
                                    </div>
                                    @error("items.$index.price") <span class="dms-error">{{ $message }}</span> @enderror
                                </td>
                                <td style="text-align: right; font-weight: 700; color: var(--k-navy); white-space: nowrap;">
                                    <span class="subtotal-display">Rp {{ number_format($item['subtotal'] ?? $itemSubtotal, 0, ',', '.') }}</span>
                                    <input type="hidden" name="items[{{ $index }}][subtotal]" class="subtotal-input" value="{{ $item['subtotal'] ?? $itemSubtotal }}">
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $index }}][notes]" class="form-control" value="{{ $item['notes'] ?? '' }}" placeholder="Opsional">
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="dms-btn dms-btn-outline" style="padding: 0.35rem 0.55rem; color: var(--k-red);" onclick="removeProductRow(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--k-gray-50);">
                            <td colspan="6" style="padding: 0.9rem 1rem;">
                                <div style="display: flex; justify-content: flex-end;">
                                    <div style="min-width: 300px; display: flex; justify-content: space-between; align-items: center; gap: 2rem; padding: 0.75rem 1rem; border: 1px solid var(--k-border); border-radius: 8px; background: var(--k-white);">
                                        <span style="font-size: 0.8rem; font-weight: 800; color: var(--k-gray-600); text-transform: uppercase;">Total PO</span>
                                        <span id="grand-total" style="font-weight: 900; color: var(--k-navy); font-size: 1.05rem;">Rp 0</span>
                                    </div>
                                </div>
                                <input type="hidden" name="total" id="total-input" value="0">
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section style="border: 1px solid var(--k-border); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem; background: var(--k-white);">
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Catatan Pemasok</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan yang relevan untuk pemasok">{{ old('notes') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Internal</label>
                    <textarea name="internal_notes" class="form-control" rows="2" placeholder="Catatan internal untuk proses approval">{{ old('internal_notes') }}</textarea>
                </div>
            </div>
        </section>

        <div class="dms-form-actions">
            <a href="{{ route('purchase-orders.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Draft PO
            </button>
        </div>
    </form>
</div>

<script>
let productIndex = {{ count($formItems) }};

function productOptions() {
    return `
        <option value="">-- Pilih Produk --</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->base_price ?: $product->price ?: 0 }}">
                {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->base_price ?: $product->price ?: 0, 0, ',', '.') }}
            </option>
        @endforeach
    `;
}

function addProductRow() {
    const tbody = document.getElementById('products-tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'product-row';
    newRow.innerHTML = `
        <td>
            <select name="items[${productIndex}][product_id]" class="form-control product-select" required onchange="updateProductPrice(this)">
                ${productOptions()}
            </select>
        </td>
        <td>
            <input type="number" name="items[${productIndex}][quantity]" class="form-control quantity-input" value="1" min="1" onchange="calculateSubtotal(this)" style="text-align: right;">
        </td>
        <td>
            <div style="position: relative;">
                <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                <input type="number" name="items[${productIndex}][price]" class="form-control price-input" value="0" step="1000" min="0" onchange="calculateSubtotal(this)" style="padding-left: 2.25rem; text-align: right;">
            </div>
        </td>
        <td style="text-align: right; font-weight: 700; color: var(--k-navy); white-space: nowrap;">
            <span class="subtotal-display">Rp 0</span>
            <input type="hidden" name="items[${productIndex}][subtotal]" class="subtotal-input" value="0">
        </td>
        <td>
            <input type="text" name="items[${productIndex}][notes]" class="form-control" placeholder="Opsional">
        </td>
        <td style="text-align: center;">
            <button type="button" class="dms-btn dms-btn-outline" style="padding: 0.35rem 0.55rem; color: var(--k-red);" onclick="removeProductRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    productIndex++;
}

function removeProductRow(button) {
    const rows = document.querySelectorAll('.product-row');
    if (rows.length <= 1) {
        return;
    }

    button.closest('tr').remove();
    calculateGrandTotal();
}

function updateProductPrice(select) {
    const selectedOption = select.options[select.selectedIndex];
    const price = Number(selectedOption?.getAttribute('data-price')) || 0;
    const row = select.closest('tr');
    const priceInput = row.querySelector('.price-input');

    if (priceInput && price > 0) {
        priceInput.value = price;
    }

    calculateSubtotal(select);
}

function calculateSubtotal(input) {
    const row = input.closest('tr');
    const quantity = Number(row.querySelector('.quantity-input').value) || 0;
    const price = Number(row.querySelector('.price-input').value) || 0;
    const subtotal = quantity * price;
    const subtotalDisplay = row.querySelector('.subtotal-display');
    const subtotalInput = row.querySelector('.subtotal-input');

    subtotalDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
    subtotalInput.value = subtotal;

    calculateGrandTotal();
}

function calculateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.subtotal-input').forEach(input => {
        total += Number(input.value) || 0;
    });

    document.getElementById('grand-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    document.getElementById('total-input').value = total;
}

document.addEventListener('DOMContentLoaded', calculateGrandTotal);
</script>
@endsection
