@extends('layouts.sidebar')

@section('page-title', 'Edit Order')
@section('breadcrumb', 'Orders / Edit')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Edit Order</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">
            Edit order: {{ $order->order_number }} (Status: {{ $order->status_label }})
        </p>
        <div style="margin-top: 0.5rem;">
            <span class="dms-badge dms-badge-{{ $order->order_source == 'app' ? 'success' : 'info' }}">
                {{ $order->order_source == 'app' ? 'Dari Aplikasi' : 'Dari Admin' }}
            </span>
            <span class="dms-badge dms-badge-{{ $order->fulfillment_type == 'stock' ? 'warning' : 'info' }}">
                {{ $order->fulfillment_type == 'stock' ? 'Mode Stock' : 'Mode BLJ' }}
            </span>
        </div>
    </div>

    <form action="{{ route('orders.update', $order) }}" method="POST">
        @csrf
        @method('PUT')
        
        <!-- Pelanggan Info (readonly) -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="form-group">
                <label class="form-label">Pelanggan</label>
                <input type="text" class="form-control" value="{{ $order->user->name ?? '-' }} ({{ $order->user->phone ?? '-' }})" readonly disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Mode Pemenuhan</label>
                <input type="text" class="form-control" value="{{ $order->fulfillment_type == 'stock' ? 'Stock (Ambil dari Gudang)' : 'BLJ (Beli langsung jual)' }}" readonly disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Skema Pembayaran</label>
                <input type="text" class="form-control" value="{{ $order->payment_timing == 'pre_paid' ? 'Pre-paid' : 'Post-paid' }}" readonly disabled>
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
                            <th style="width: 20%;">Harga Satuan</th>
                            <th style="width: 20%;">Subtotal</th>
                            <th style="width: 10%;">Aksi</th>
                        </thead>
                    </thead>
                    <tbody id="products-tbody">
                        @foreach($order->items as $index => $item)
                        <tr class="product-row">
                            <td>
                                <select name="items[{{ $index }}][product_id]" class="form-control product-select" required onchange="updateProductPrice(this, {{ $index }})">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $productsWithStock[$product->id]['stock'] ?? 0 }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                <div class="stock-warning" style="display: none; font-size: 0.65rem; color: var(--k-red); margin-top: 0.25rem;"></div>
                            </td>
                            <td>
                                <input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity-input" value="{{ $item->quantity }}" min="1" onchange="calculateSubtotal(this, {{ $index }})">
                            </td>
                            <td>
                                <span class="product-price-display">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                <input type="hidden" name="items[{{ $index }}][price]" class="product-price-input" value="{{ $item->price }}">
                            </td>
                            <td>
                                <span class="subtotal-display">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                <input type="hidden" name="items[{{ $index }}][subtotal]" class="subtotal-input" value="{{ $item->subtotal }}">
                            </td>
                            <td>
                                <button type="button" class="dms-btn dms-btn-outline" style="padding: 0.2rem 0.5rem; color: var(--k-red);" onclick="removeProductRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--k-gray-50);">
                            <td colspan="3" style="text-align: right; font-weight: 600;">Subtotal:</td>
                            <td colspan="2"><span id="subtotal-total">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span><input type="hidden" name="subtotal" id="subtotal-input" value="{{ $order->subtotal }}"></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 600;">Biaya Pengiriman (opsional):</td>
                            <td colspan="2">
                                <div style="position: relative; display: inline-block;">
                                    <span style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%);">Rp</span>
                                    <input type="number" name="delivery_fee" id="delivery-fee" class="form-control" style="width: 150px; padding-left: 2rem;" value="{{ $order->delivery_fee ?? 0 }}" min="0" onchange="calculateGrandTotal()">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 600;">
                                <label style="display: inline-flex; align-items: center; gap: 0.35rem; margin: 0; cursor: pointer;">
                                    <input type="hidden" name="requires_packing" value="0">
                                    <input type="checkbox" name="requires_packing" id="requires_packing" value="1" {{ $order->requiresPacking() ? 'checked' : '' }}>
                                    <span>Gunakan packing / repack</span>
                                </label>
                            </td>
                            <td colspan="2">
                                <div id="packing-fee-container" style="position: relative; display: inline-block; {{ $order->requiresPacking() ? '' : 'display: none;' }}">
                                    <span style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%);">Rp</span>
                                    <input type="number" name="packing_fee" id="packing-fee" class="form-control" style="width: 150px; padding-left: 2rem;" value="{{ $order->packing_fee ?? 0 }}" min="0" onchange="calculateGrandTotal()">
                                </div>
                            </td>
                        </tr>
                        <tr style="background: var(--k-green-light);">
                            <td colspan="3" style="text-align: right; font-weight: 700; font-size: 1.1rem;">Total:</td>
                            <td colspan="2"><span id="grand-total" style="font-weight: 700; font-size: 1.2rem; color: var(--k-green);">Rp {{ number_format($order->total, 0, ',', '.') }}</span><input type="hidden" name="total" id="total-input" value="{{ $order->total }}"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Delivery Information -->
        <div style="margin-bottom: 2rem;">
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem;">Informasi Pengiriman</h4>
            
            <div class="dms-form-grid">
                <div class="form-group">
                    <label class="form-label">Tanggal Pengiriman</label>
                    <input type="date" name="delivery_date" class="form-control" value="{{ $order->delivery_date->format('Y-m-d') }}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Waktu Pengiriman</label>
                    <select name="delivery_time_slot" class="form-control" required>
                        <option value="06:00-09:00" {{ $order->delivery_time_slot == '06:00-09:00' ? 'selected' : '' }}>06:00 - 09:00 (Pagi)</option>
                        <option value="09:00-12:00" {{ $order->delivery_time_slot == '09:00-12:00' ? 'selected' : '' }}>09:00 - 12:00 (Siang)</option>
                        <option value="12:00-15:00" {{ $order->delivery_time_slot == '12:00-15:00' ? 'selected' : '' }}>12:00 - 15:00 (Sore)</option>
                    </select>
                </div>
                
                <div class="form-group dms-form-span-2">
                    <label class="form-label">Alamat Pengiriman</label>
                    <textarea name="address" class="form-control" rows="2" required>{{ $order->address }}</textarea>
                </div>
            </div>
        </div>
        
        <!-- Notes -->
        <div class="form-group">
            <label class="form-label">Catatan Order</label>
            <textarea name="notes" class="form-control" rows="2">{{ $order->notes }}</textarea>
        </div>
        
        <!-- Buttons -->
        <div class="dms-form-actions">
            <a href="{{ route('orders.show', $order) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Order
            </button>
        </div>
    </form>
</div>

<script>
let productIndex = {{ count($order->items) }};

function addProductRow() {
    const tbody = document.getElementById('products-tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'product-row';
    newRow.innerHTML = `
        <tr>
            <select name="items[${productIndex}][product_id]" class="form-control product-select" required onchange="updateProductPrice(this, ${productIndex})">
                <option value="">-- Pilih Produk --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $productsWithStock[$product->id]['stock'] ?? 0 }}">
                        {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->price, 0, ',', '.') }}
                    </option>
                @endforeach
            </select>
            <div class="stock-warning" style="display: none; font-size: 0.65rem; color: var(--k-red); margin-top: 0.25rem;"></div>
         </td>
         <td>
            <input type="number" name="items[${productIndex}][quantity]" class="form-control quantity-input" value="1" min="1" onchange="calculateSubtotal(this, ${productIndex})">
         </td>
         <td>
            <span class="product-price-display">Rp 0</span>
            <input type="hidden" name="items[${productIndex}][price]" class="product-price-input" value="0">
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
    const priceDisplay = row.querySelector('.product-price-display');
    const priceInput = row.querySelector('.product-price-input');
    
    if (priceDisplay) {
        priceDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
        if (priceInput) priceInput.value = price;
    }
    
    calculateSubtotal(row.querySelector('.quantity-input'), index);
}

function calculateSubtotal(quantityInput, index) {
    const row = quantityInput.closest('tr');
    const select = row.querySelector('.product-select');
    const selectedOption = select.options[select.selectedIndex];
    const price = parseInt(selectedOption?.getAttribute('data-price') || 0);
    const quantity = parseInt(quantityInput.value) || 0;
    const subtotal = price * quantity;
    
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
    
    const deliveryFee = parseInt(document.getElementById('delivery-fee').value) || 0;
    const packingEnabled = document.getElementById('requires_packing').checked;
    const packingFee = packingEnabled ? (parseInt(document.getElementById('packing-fee').value) || 0) : 0;
    const total = subtotal + deliveryFee + packingFee;
    
    document.getElementById('subtotal-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
    document.getElementById('subtotal-input').value = subtotal;
    document.getElementById('grand-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    document.getElementById('total-input').value = total;
}

function togglePackingRequirement() {
    const enabled = document.getElementById('requires_packing').checked;
    const container = document.getElementById('packing-fee-container');
    if (container) {
        container.style.display = enabled ? 'inline-block' : 'none';
    }
    calculateGrandTotal();
}

// Initial calculation
document.addEventListener('DOMContentLoaded', function() {
    calculateGrandTotal();
    togglePackingRequirement();
    document.getElementById('requires_packing').addEventListener('change', togglePackingRequirement);
});
</script>

@endsection
