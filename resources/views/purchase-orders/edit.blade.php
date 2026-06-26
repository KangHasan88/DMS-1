@extends('layouts.sidebar')

@section('page-title', 'Edit Purchase Order')
@section('breadcrumb', 'Purchase Orders / Edit')

@php
    $selectedSupplierId = old('supplier_id', $purchaseOrder->supplier_id);
    $selectedSupplier = $suppliers->firstWhere('id', (int) $selectedSupplierId);
    $oldItems = old('items');
    $formItems = $oldItems ?: $purchaseOrder->items->map(function ($item) {
        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'subtotal' => $item->subtotal,
            'notes' => $item->notes,
        ];
    })->values()->all();
    $productOptions = $products->keyBy('id');
@endphp

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Purchase Order</h3>
        <p class="dms-form-subtitle">Perbarui rencana pembelian sebelum PO diproses lebih lanjut.</p>
    </div>

    <form action="{{ route('purchase-orders.update', $purchaseOrder) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
            <div class="form-group">
                <label class="form-label">Pemasok <span class="dms-required">*</span></label>
                <select name="supplier_id" class="form-control" required>
                    <option value="">-- Pilih Pemasok --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (int) $selectedSupplierId === $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}{{ $supplier->phone ? ' (' . $supplier->phone . ')' : '' }}{{ $supplier->is_active ? '' : ' - nonaktif' }}
                        </option>
                    @endforeach
                </select>
                @if($selectedSupplier && !$selectedSupplier->is_active)
                    <small class="dms-form-help dms-text-warning">Pemasok ini sedang nonaktif, tapi tetap ditampilkan karena sudah tersimpan di PO ini.</small>
                @endif
                @error('supplier_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Tanggal PO <span class="dms-required">*</span></label>
                <input type="date" name="order_date" class="form-control" value="{{ old('order_date', optional($purchaseOrder->order_date)->format('Y-m-d')) }}" required>
                @error('order_date') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Tanggal Perkiraan Datang</label>
                <input type="date" name="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date', optional($purchaseOrder->expected_delivery_date)->format('Y-m-d')) }}">
                @error('expected_delivery_date') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <h4 style="font-size: 1rem; font-weight: 700; color: var(--k-navy); margin: 0;">Daftar Produk</h4>
                    <p class="dms-form-subtitle" style="margin: 0.25rem 0 0;">Produk nonaktif hanya muncul jika sudah tersimpan di PO ini.</p>
                </div>
                <button type="button" class="dms-btn dms-btn-outline" onclick="addProductRow()">
                    <i class="bi bi-plus-circle"></i> Tambah Produk
                </button>
            </div>

            @error('items') <span class="dms-error">{{ $message }}</span> @enderror

            <div style="overflow-x: auto;">
                <table class="dms-table" id="products-table">
                    <thead>
                        <tr>
                            <th style="width: 34%;">Produk</th>
                            <th style="width: 12%;">Qty</th>
                            <th style="width: 18%;">Harga Beli</th>
                            <th style="width: 18%;">Subtotal</th>
                            <th style="width: 10%;">Catatan</th>
                            <th style="width: 8%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        @forelse($formItems as $index => $item)
                            @php
                                $itemProduct = $productOptions->get((int) ($item['product_id'] ?? 0));
                                $itemSubtotal = (int) (($item['quantity'] ?? 0) * ($item['price'] ?? 0));
                            @endphp
                            <tr class="product-row">
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}">
                                    <select name="items[{{ $index }}][product_id]" class="form-control product-select" required onchange="updateProductPrice(this)">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}"
                                                data-price="{{ $product->base_price ?: $product->price ?: 0 }}"
                                                {{ (int) ($item['product_id'] ?? 0) === $product->id ? 'selected' : '' }}>
                                                {{ $product->display_name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->base_price ?: $product->price ?: 0, 0, ',', '.') }}{{ $product->is_active ? '' : ' - nonaktif' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($itemProduct && !$itemProduct->is_active)
                                        <small class="dms-form-help dms-text-warning">Produk ini sedang nonaktif, tapi tetap ditampilkan karena sudah tersimpan di PO ini.</small>
                                    @endif
                                    @error("items.$index.product_id") <span class="dms-error">{{ $message }}</span> @enderror
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity-input" value="{{ $item['quantity'] ?? 1 }}" min="1" onchange="calculateSubtotal(this)">
                                    @error("items.$index.quantity") <span class="dms-error">{{ $message }}</span> @enderror
                                </td>
                                <td>
                                    <div style="position: relative;">
                                        <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                                        <input type="number" name="items[{{ $index }}][price]" class="form-control price-input" value="{{ $item['price'] ?? 0 }}" step="1000" min="0" onchange="calculateSubtotal(this)" style="padding-left: 2.25rem;">
                                    </div>
                                    @error("items.$index.price") <span class="dms-error">{{ $message }}</span> @enderror
                                </td>
                                <td>
                                    <span class="subtotal-display">Rp {{ number_format($item['subtotal'] ?? $itemSubtotal, 0, ',', '.') }}</span>
                                    <input type="hidden" name="items[{{ $index }}][subtotal]" class="subtotal-input" value="{{ $item['subtotal'] ?? $itemSubtotal }}">
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $index }}][notes]" class="form-control" value="{{ $item['notes'] ?? '' }}" placeholder="Opsional">
                                </td>
                                <td>
                                    <button type="button" class="dms-btn dms-btn-outline" style="padding: 0.35rem 0.65rem; color: var(--k-red);" onclick="removeProductRow(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="product-row"></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--k-gray-50);">
                            <td colspan="3" style="text-align: right; font-weight: 700;">Total: </td>
                            <td colspan="3">
                                <span id="grand-total" style="font-weight: 800; color: var(--k-navy);">Rp {{ number_format($purchaseOrder->total, 0, ',', '.') }}</span>
                                <input type="hidden" name="total" id="total-input" value="{{ $purchaseOrder->total }}">
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan untuk pemasok">{{ old('notes', $purchaseOrder->notes) }}</textarea>
        </div>

        <div class="form-group" style="margin-bottom: 2rem;">
            <label class="form-label">Catatan Internal</label>
            <textarea name="internal_notes" class="form-control" rows="2" placeholder="Catatan internal untuk admin">{{ old('internal_notes', $purchaseOrder->internal_notes) }}</textarea>
        </div>

        <div class="dms-form-actions">
            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<script>
let productIndex = {{ max(count($formItems), 0) }};

function activeProductOptions() {
    return `
        <option value="">-- Pilih Produk --</option>
        @foreach($activeProducts as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->base_price ?: $product->price ?: 0 }}">
                {{ $product->display_name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->base_price ?: $product->price ?: 0, 0, ',', '.') }}
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
            <input type="hidden" name="items[${productIndex}][id]" value="">
            <select name="items[${productIndex}][product_id]" class="form-control product-select" required onchange="updateProductPrice(this)">
                ${activeProductOptions()}
            </select>
        </td>
        <td>
            <input type="number" name="items[${productIndex}][quantity]" class="form-control quantity-input" value="1" min="1" onchange="calculateSubtotal(this)">
        </td>
        <td>
            <div style="position: relative;">
                <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                <input type="number" name="items[${productIndex}][price]" class="form-control price-input" value="0" step="1000" min="0" onchange="calculateSubtotal(this)" style="padding-left: 2.25rem;">
            </div>
        </td>
        <td>
            <span class="subtotal-display">Rp 0</span>
            <input type="hidden" name="items[${productIndex}][subtotal]" class="subtotal-input" value="0">
        </td>
        <td>
            <input type="text" name="items[${productIndex}][notes]" class="form-control" placeholder="Opsional">
        </td>
        <td>
            <button type="button" class="dms-btn dms-btn-outline" style="padding: 0.35rem 0.65rem; color: var(--k-red);" onclick="removeProductRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    productIndex++;
}

function removeProductRow(button) {
    if (document.querySelectorAll('.product-row').length <= 1) {
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
