@extends('layouts.sidebar')

@section('page-title', 'Buat Order Baru')
@section('breadcrumb', 'Orders / Tambah')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Detail Order Penjualan</h3>
        <p class="dms-form-subtitle">Pilih pelanggan, mode pemenuhan, produk, dan biaya sebelum order diproses.</p>
    </div>

    <form id="order-form" action="{{ route('orders.store') }}" method="POST">
        @csrf
        <input type="hidden" name="order_source" id="order_source" value="admin">
        <input type="hidden" name="payment_method" id="payment_method" value="manual">
        
        <!-- Pelanggan & Order Type Section -->
        <div style="margin-bottom: 1.5rem;">
            <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid var(--k-gray-200);">
                <i class="bi bi-person" style="margin-right: 0.4rem; color: var(--k-green);"></i>
                Informasi Pelanggan & Mode Order
            </h4>
            
            <div class="dms-form-grid">
                <!-- Pelanggan -->
                <div>
                    <label class="form-label">Pelanggan <span class="dms-required">*</span></label>
                    <select name="user_id" id="customer-select" class="form-control dms-native-select" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        @foreach($customers as $customer)
                            @php
                                $profile = $customer->customer;
                                $addresses = $profile?->activeAddresses ?? collect();
                                if ($profile && $addresses->isEmpty()) {
                                    $addresses = $profile->activeAddresses()->get();
                                }
                                $invoiceAddresses = $addresses->filter(fn ($address) => in_array($address->type, ['invoice', 'both'], true))->values();
                                $shippingAddresses = $addresses->filter(fn ($address) => in_array($address->type, ['shipping', 'both'], true))->values();
                                $formatAddress = fn ($address) => [
                                    'id' => $address->id,
                                    'label' => $address->label,
                                    'type_label' => $address->type_label,
                                    'address' => $address->address,
                                    'recipient_name' => $address->recipient_name,
                                    'recipient_phone' => $address->recipient_phone,
                                    'latitude' => $address->latitude,
                                    'longitude' => $address->longitude,
                                    'is_default_invoice' => $address->is_default_invoice,
                                    'is_default_shipping' => $address->is_default_shipping,
                                ];
                            @endphp
                            <option
                                value="{{ $customer->id }}"
                                data-address="{{ e($customer->customer?->address ?? $customer->address ?? '') }}"
                                data-latitude="{{ e($customer->customer?->latitude ?? '') }}"
                                data-longitude="{{ e($customer->customer?->longitude ?? '') }}"
                                data-invoice-addresses='@json($invoiceAddresses->map($formatAddress)->values(), JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)'
                                data-shipping-addresses='@json($shippingAddresses->map($formatAddress)->values(), JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)'
                                data-address-url="{{ route('customers.show', $customer->customer?->id ?? 0) }}#customer-addresses"
                                {{ old('user_id') == $customer->id ? 'selected' : '' }}
                            >
                                {{ $customer->name }} ({{ $customer->phone }})
                            </option>
                        @endforeach
                    </select>
                    <div class="dms-combobox js-searchable-dropdown" data-select-id="customer-select" data-search-placeholder="Cari nama atau telepon pelanggan..."></div>
                    @can('create customers')
                    <small class="dms-form-help">
                        <a href="{{ route('customers.create') }}" target="_blank" style="color: var(--k-green);">+ Tambah Pelanggan Baru</a>
                    </small>
                    @endcan
                    @error('user_id') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                
                <!-- Fulfillment Type -->
                <div>
                    <label class="form-label">Mode Pemenuhan Order <span class="dms-required">*</span></label>
                    <div style="display: flex; gap: 1rem; margin-top: 0.3rem;">
                        <label class="dms-check">
                            <input type="radio" name="fulfillment_type" value="stock" {{ old('fulfillment_type', $defaultFulfillmentType) == 'stock' ? 'checked' : '' }} onchange="toggleFulfillmentMode()">
                            <span><i class="bi bi-archive"></i> Stock (Ambil dari Gudang)</span>
                        </label>
                        <label class="dms-check">
                            <input type="radio" name="fulfillment_type" value="jit" {{ old('fulfillment_type') == 'jit' ? 'checked' : '' }} onchange="toggleFulfillmentMode()">
                            <span><i class="bi bi-truck"></i> BLJ (Beli langsung jual)</span>
                        </label>
                    </div>
                    <div id="stock-info" style="margin-top: 0.5rem; padding: 0.5rem; background: var(--k-green-light); border-radius: 6px; font-size: 0.7rem; color: var(--k-green);">
                        <i class="bi bi-info-circle"></i> Mode Stock: Barang akan diambil dari stok gudang. Pastikan stok mencukupi.
                    </div>
                    <div id="jit-info" style="display: none; margin-top: 0.5rem; padding: 0.5rem; background: var(--k-orange-light); border-radius: 6px; font-size: 0.7rem; color: var(--k-orange);">
                        <i class="bi bi-info-circle"></i> Mode BLJ: Barang dibeli dari pabrik/supplier dan dikirim langsung ke pelanggan tanpa masuk gudang.
                    </div>
                    @error('fulfillment_type') <span class="dms-error">{{ $message }}</span> @enderror
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
            
            <div class="dms-products-table-wrap">
                <table style="width: 100%; border-collapse: collapse; background: white;">
                    <thead>
                        <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                            <th style="padding: 0.6rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 30%;">Produk</th>
                            <th style="padding: 0.6rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 10%;">Jumlah</th>
                            <th style="padding: 0.6rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 15%;">Harga</th>
                            <th style="padding: 0.6rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 12%;">Diskon %</th>
                            <th style="padding: 0.6rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 15%;">Subtotal</th>
                            <th style="padding: 0.6rem; text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--k-gray-600); width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        <tr class="product-row" style="border-bottom: 1px solid var(--k-gray-200);">
                            <td style="padding: 0.5rem;">
                                <select name="items[0][product_id]" id="product-select-0" class="product-select dms-native-select" required onchange="updateProductPrice(this, 0)">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $productsWithStock[$product->id]['stock'] ?? 0 }}" data-has-stock="{{ $productsWithStock[$product->id]['has_stock'] ? 'true' : 'false' }}">
                                            {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="dms-combobox js-searchable-dropdown product-search" data-select-id="product-select-0" data-search-placeholder="Cari produk..."></div>
                                <div class="stock-warning" style="display: none; font-size: 0.6rem; color: var(--k-red); margin-top: 0.25rem;"></div>
                            </td>
                            <td style="padding: 0.5rem;">
                                <input type="number" name="items[0][quantity]" class="quantity-input" value="1" min="1" onchange="calculateSubtotal(this, 0)" style="width: 100%; padding: 0.5rem; border: 1px solid var(--k-gray-300); border-radius: 6px; font-size: 0.75rem;">
                            </td>
                            <td style="padding: 0.5rem;">
                                <span class="product-price-display" style="font-size: 0.75rem;">Rp 0</span>
                                <input type="hidden" name="items[0][price]" class="price-input" value="0">
                            </td>
                            <td style="padding: 0.5rem;">
                                <input type="number" name="items[0][discount_percent]" class="discount-percent-input" value="0" min="0" max="100" step="1" onchange="calculateSubtotal(this, 0)" style="width: 100%; padding: 0.4rem; border: 1px solid var(--k-gray-300); border-radius: 6px; font-size: 0.7rem;">
                            </td>
                            <td style="padding: 0.5rem;">
                                <span class="subtotal-display" style="font-weight: 500; color: var(--k-green); font-size: 0.75rem;">Rp 0</span>
                                <input type="hidden" name="items[0][subtotal]" class="subtotal-input" value="0">
                            </td>
                            <td style="padding: 0.5rem; text-align: center;">
                                <button type="button" class="remove-btn" onclick="removeProductRow(this)" style="background: none; border: none; color: var(--k-red); cursor: pointer; font-size: 1rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                 </table>
            </div>
        </div>
        
        <!-- Discount, Shipping & Packing Section -->
        <div class="dms-fee-grid">
            <div class="dms-fee-panel">
                <h4 class="dms-fee-title">
                    <i class="bi bi-tag"></i>
                    Diskon Order
                </h4>
                <div>
                    <label class="form-label">Tipe Diskon</label>
                    <select name="discount_type" id="discount_type" class="form-control" onchange="toggleDiscountType()">
                        <option value="none">Tanpa Diskon</option>
                        <option value="percent">Persentase (%)</option>
                        <option value="nominal">Nominal (Rp)</option>
                    </select>
                </div>
                <div id="discount_value_container" style="display: none; margin-top: 0.75rem;">
                    <label class="form-label" id="discount_label">Nilai Diskon</label>
                    <input type="number" name="discount_value" id="discount_value" class="form-control" value="0" step="1" onchange="calculateGrandTotal()">
                </div>
            </div>

            <div class="dms-fee-panel">
                <h4 class="dms-fee-title">
                    <i class="bi bi-truck"></i>
                    Ongkos Kirim
                </h4>
                <div>
                    <label class="form-label">Tipe Ongkir <span style="color: var(--k-gray-500); font-weight: 400;">(opsional)</span></label>
                    <select name="shipping_type" id="shipping_type" class="form-control" onchange="toggleShippingType()">
                        <option value="none">Tanpa Ongkir</option>
                        <option value="flat">Flat Rate (Tetap)</option>
                        <option value="weight">Berdasarkan Berat</option>
                        <option value="distance">Berdasarkan Jarak</option>
                    </select>
                </div>
                <div class="dms-shipping-extra">
                    <div id="shipping_rate_container">
                        <label class="form-label">Tarif Dasar <span style="color: var(--k-gray-500); font-weight: 400;">(opsional)</span></label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); font-size: 0.7rem;">Rp</span>
                            <input type="number" name="shipping_rate" id="shipping_rate" class="form-control" value="0" step="1000" onchange="calculateGrandTotal()" style="padding-left: 1.8rem;">
                        </div>
                    </div>
                    <div id="shipping_weight_container" style="display: none;">
                        <label class="form-label">Berat (kg)</label>
                        <input type="number" name="shipping_weight" id="shipping_weight" class="form-control" value="0" step="0.1" onchange="calculateGrandTotal()">
                    </div>
                    <div id="shipping_distance_container" style="display: none;">
                        <label class="form-label">Jarak (km)</label>
                        <input type="number" name="shipping_distance" id="shipping_distance" class="form-control" value="0" step="1" onchange="calculateGrandTotal()">
                    </div>
                </div>
            </div>

            <div class="dms-fee-panel dms-fee-panel-packing">
                <h4 class="dms-fee-title">
                    <i class="bi bi-box2"></i>
                    Packing / Repack
                </h4>
                <p class="dms-fee-note">Biaya packing dihitung terpisah dari ongkos kirim.</p>
                <div style="max-width: 520px;">
                    <label class="form-label">Biaya Packing / Repack <span style="color: var(--k-gray-500); font-weight: 400;">(opsional)</span></label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); font-size: 0.7rem;">Rp</span>
                        <input type="number" name="packing_fee" id="packing_fee" class="form-control" value="0" step="500" onchange="calculateGrandTotal()" style="padding-left: 1.8rem;">
                    </div>
                    <small class="dms-form-help">Kosong atau 0 berarti tanpa biaya packing/repack.</small>
                </div>
            </div>
        </div>
        
        <!-- PPN Section -->
        <div style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <label class="dms-check">
                    <input type="checkbox" name="include_ppn" id="include_ppn" value="1" onchange="togglePPN()">
                    <span><i class="bi bi-percent"></i> Include PPN 11%</span>
                </label>
                <div id="ppn_rate_container" style="display: none;">
                    <label class="form-label">Rate PPN (%)</label>
                    <input type="number" name="ppn_rate" id="ppn_rate" class="form-control" value="11" step="0.1" style="width: 100px;" onchange="calculateGrandTotal()">
                </div>
            </div>
        </div>
        
        <!-- Calculation Summary -->
        <div style="margin-bottom: 1.5rem; background: var(--k-gray-50); border-radius: 8px; padding: 0.75rem;">
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: var(--k-gray-600);">Subtotal Produk:</span>
                    <span id="subtotal-total" style="font-weight: 600;">Rp 0</span>
                    <input type="hidden" name="subtotal" id="subtotal-input" value="0">
                </div>
                <div id="discount-row" style="display: none; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: var(--k-green);">Diskon Order:</span>
                    <span id="discount-amount" style="font-weight: 600; color: var(--k-green);">Rp 0</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: var(--k-gray-600);">Total Setelah Diskon:</span>
                    <span id="after-discount" style="font-weight: 600;">Rp 0</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: var(--k-gray-600);">Ongkos Kirim:</span>
                    <span id="shipping-cost" style="font-weight: 600;">Rp 0</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: var(--k-gray-600);">Biaya Packing:</span>
                    <span id="packing-fee-display" style="font-weight: 600;">Rp 0</span>
                </div>
                <div id="ppn-row" style="display: none; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: var(--k-orange);">PPN:</span>
                    <span id="ppn-amount" style="font-weight: 600; color: var(--k-orange);">Rp 0</span>
                </div>
                <div style="border-top: 1px solid var(--k-gray-200); margin: 0.25rem 0;"></div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; font-weight: 700;">Grand Total:</span>
                    <span id="grand-total" style="font-weight: 700; font-size: 1rem; color: var(--k-green);">Rp 0</span>
                    <input type="hidden" name="total" id="total-input" value="0">
                </div>
            </div>
        </div>
        
        <!-- Delivery Information -->
        <div style="margin-bottom: 1.5rem;">
            <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid var(--k-gray-200);">
                <i class="bi bi-geo-alt" style="margin-right: 0.4rem; color: var(--k-green);"></i>
                Informasi Pengiriman
            </h4>
            
            <div class="dms-form-grid">
                <div>
                    <label class="form-label">Tanggal Pengiriman <span class="dms-required">*</span></label>
                    <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', $defaultDeliveryDate) }}" required>
                    @error('delivery_date') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="form-label">Waktu Pengiriman <span class="dms-required">*</span></label>
                    <select name="delivery_time_slot" class="form-control" required>
                        <option value="06:00-09:00">06:00 - 09:00 (Pagi)</option>
                        <option value="09:00-12:00">09:00 - 12:00 (Siang)</option>
                        <option value="12:00-15:00">12:00 - 15:00 (Sore)</option>
                    </select>
                    @error('delivery_time_slot') <span class="dms-error">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="form-label">Alamat Invoice / Dokumen</label>
                    <select name="invoice_address_id" id="invoice-address-select" class="form-control">
                        <option value="">Pilih pelanggan terlebih dahulu</option>
                    </select>
                    <small class="dms-form-help">Alamat untuk dokumen invoice/tagihan.</small>
                </div>

                <div>
                    <label class="form-label">Alamat Pengiriman</label>
                    <select name="shipping_address_id" id="shipping-address-select" class="form-control">
                        <option value="">Pilih pelanggan terlebih dahulu</option>
                    </select>
                    <label class="dms-check" style="margin-top: 0.45rem;">
                        <input type="checkbox" name="shipping_same_as_invoice" id="shipping_same_as_invoice" value="1" checked>
                        <span>Sama dengan alamat invoice/dokumen</span>
                    </label>
                </div>

                <div class="dms-form-span-2">
                    <div style="padding: 0.65rem 0.75rem; border: 1px solid var(--k-gray-200); border-radius: 8px; background: var(--k-gray-50); font-size: 0.78rem; color: var(--k-gray-600); display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <span>Alamat invoice dan alamat kirim dikelola di master pelanggan.</span>
                        <a href="#" id="manage-customer-address-link" target="_blank" style="color: var(--k-green); font-weight: 600; pointer-events: none; opacity: 0.5;">
                            Kelola alamat pelanggan
                        </a>
                    </div>
                </div>
                
                <div class="dms-form-span-2">
                    <label class="form-label">Alamat Pengiriman <span class="dms-required">*</span></label>
                    <textarea name="address" id="delivery-address" class="form-control" rows="2" required placeholder="Pilih pelanggan untuk mengisi alamat pengiriman otomatis">{{ old('address') }}</textarea>
                    <small class="dms-form-help">Alamat diambil dari master alamat pelanggan sebagai snapshot order. Jika alamat kirim berbeda, tambahkan alamat tersebut di master pelanggan.</small>
                    @error('address') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="form-label">Latitude (opsional)</label>
                    <input type="text" name="latitude" id="delivery-latitude" class="form-control" value="{{ old('latitude') }}" placeholder="-6.200000">
                </div>
                
                <div>
                    <label class="form-label">Longitude (opsional)</label>
                    <input type="text" name="longitude" id="delivery-longitude" class="form-control" value="{{ old('longitude') }}" placeholder="106.816666">
                </div>
            </div>
        </div>
        
        <!-- Notes -->
        <div style="margin-bottom: 1.5rem;">
            <label class="form-label">Catatan Order</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan untuk tim operasional (opsional)" style="padding: 0.6rem; font-size: 0.8rem;">{{ old('notes') }}</textarea>
        </div>
        
        <!-- Buttons - Paling Bawah -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('orders.index') }}" class="dms-btn dms-btn-outline" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-save"></i> Simpan Order
            </button>
        </div>
    </form>
</div>

<script>
let productIndex = 1;

function buildSearchableDropdown(dropdown) {
    const select = document.getElementById(dropdown.dataset.selectId);
    if (!select || dropdown.dataset.dropdownReady === 'true') {
        return;
    }

    dropdown.dataset.dropdownReady = 'true';
    const placeholder = select.options[0]?.textContent.trim() || 'Pilih data';
    const searchPlaceholder = dropdown.dataset.searchPlaceholder || 'Cari...';

    dropdown.innerHTML = `
        <button type="button" class="dms-combobox-trigger" aria-haspopup="listbox" aria-expanded="false">
            <span class="dms-combobox-value">${placeholder}</span>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div class="dms-combobox-menu" hidden>
            <input type="search" class="dms-combobox-search" placeholder="${searchPlaceholder}" autocomplete="off">
            <div class="dms-combobox-options" role="listbox"></div>
        </div>
    `;

    const trigger = dropdown.querySelector('.dms-combobox-trigger');
    const valueLabel = dropdown.querySelector('.dms-combobox-value');
    const menu = dropdown.querySelector('.dms-combobox-menu');
    const search = dropdown.querySelector('.dms-combobox-search');
    const optionsWrap = dropdown.querySelector('.dms-combobox-options');

    const updateLabel = () => {
        const selectedOption = select.options[select.selectedIndex];
        valueLabel.textContent = selectedOption?.value ? selectedOption.textContent.trim() : placeholder;
    };

    const closeMenu = () => {
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        dropdown.classList.remove('is-open');
        dropdown.closest('.product-row')?.classList.remove('dms-combobox-row-open');
    };

    const renderOptions = () => {
        const query = search.value.trim().toLowerCase();
        const options = Array.from(select.options).filter(option => option.value);
        const matches = options.filter(option => option.textContent.toLowerCase().includes(query));

        optionsWrap.innerHTML = '';

        if (!matches.length) {
            const empty = document.createElement('div');
            empty.className = 'dms-combobox-empty';
            empty.textContent = 'Tidak ada data cocok';
            optionsWrap.appendChild(empty);
            return;
        }

        matches.forEach(option => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'dms-combobox-option' + (option.value === select.value ? ' is-selected' : '');
            button.dataset.value = option.value;
            button.setAttribute('role', 'option');
            button.textContent = option.textContent.trim();
            optionsWrap.appendChild(button);
        });
    };

    trigger.addEventListener('click', () => {
        const shouldOpen = menu.hidden;
        document.querySelectorAll('.dms-combobox-menu').forEach(openMenu => {
            openMenu.hidden = true;
            const openDropdown = openMenu.closest('.dms-combobox');
            openDropdown?.classList.remove('is-open');
            openDropdown?.closest('.product-row')?.classList.remove('dms-combobox-row-open');
            openDropdown?.querySelector('.dms-combobox-trigger')?.setAttribute('aria-expanded', 'false');
        });

        menu.hidden = !shouldOpen;
        trigger.setAttribute('aria-expanded', String(shouldOpen));
        dropdown.classList.toggle('is-open', shouldOpen);
        dropdown.closest('.product-row')?.classList.toggle('dms-combobox-row-open', shouldOpen);

        if (shouldOpen) {
            search.value = '';
            renderOptions();
            search.focus();
        }
    });

    search.addEventListener('input', renderOptions);
    optionsWrap.addEventListener('click', event => {
        const optionButton = event.target.closest('.dms-combobox-option');
        if (!optionButton) {
            return;
        }

        select.value = optionButton.dataset.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        updateLabel();
        closeMenu();
    });

    select.addEventListener('change', updateLabel);
    if (select.id === 'customer-select') {
        select.addEventListener('change', () => fillDeliveryAddressFromCustomer(true));
    }
    updateLabel();
    renderOptions();
}

function initializeSearchableDropdowns(scope = document) {
    scope.querySelectorAll('.js-searchable-dropdown').forEach(buildSearchableDropdown);
}

function fillDeliveryAddressFromCustomer(force = false) {
    const customerSelect = document.getElementById('customer-select');
    const invoiceSelect = document.getElementById('invoice-address-select');
    const shippingSelect = document.getElementById('shipping-address-select');
    const sameAsInvoice = document.getElementById('shipping_same_as_invoice');
    const manageAddressLink = document.getElementById('manage-customer-address-link');
    const addressInput = document.getElementById('delivery-address');
    const latitudeInput = document.getElementById('delivery-latitude');
    const longitudeInput = document.getElementById('delivery-longitude');

    if (!customerSelect || !addressInput || !invoiceSelect || !shippingSelect) {
        return;
    }

    const selectedOption = customerSelect.options[customerSelect.selectedIndex];
    if (!selectedOption?.value) {
        populateAddressSelect(invoiceSelect, [], 'Pilih pelanggan terlebih dahulu');
        populateAddressSelect(shippingSelect, [], 'Pilih pelanggan terlebih dahulu');
        if (manageAddressLink) {
            manageAddressLink.href = '#';
            manageAddressLink.style.pointerEvents = 'none';
            manageAddressLink.style.opacity = '0.5';
        }
        return;
    }

    const invoiceAddresses = parseAddressDataset(selectedOption?.dataset.invoiceAddresses);
    const shippingAddresses = parseAddressDataset(selectedOption?.dataset.shippingAddresses);

    if (manageAddressLink) {
        const addressUrl = selectedOption?.dataset.addressUrl || '';
        manageAddressLink.href = addressUrl || '#';
        manageAddressLink.style.pointerEvents = addressUrl ? 'auto' : 'none';
        manageAddressLink.style.opacity = addressUrl ? '1' : '0.5';
    }

    populateAddressSelect(invoiceSelect, invoiceAddresses, 'Pilih alamat invoice');
    populateAddressSelect(shippingSelect, shippingAddresses, 'Pilih alamat pengiriman');

    const defaultInvoice = invoiceAddresses.find(address => address.is_default_invoice) || invoiceAddresses[0] || null;
    const defaultShipping = shippingAddresses.find(address => address.is_default_shipping) || shippingAddresses[0] || null;

    invoiceSelect.value = defaultInvoice?.id || '';
    shippingSelect.value = defaultShipping?.id || '';
    shippingSelect.disabled = Boolean(sameAsInvoice?.checked);

    updateDeliveryAddressSnapshot(force);
}

function parseAddressDataset(rawValue) {
    if (!rawValue) {
        return [];
    }

    try {
        return JSON.parse(rawValue);
    } catch (error) {
        return [];
    }
}

function populateAddressSelect(select, addresses, emptyLabel) {
    select.innerHTML = `<option value="">${emptyLabel}</option>`;

    addresses.forEach(address => {
        const option = document.createElement('option');
        option.value = address.id;
        option.dataset.address = address.address || '';
        option.dataset.latitude = address.latitude || '';
        option.dataset.longitude = address.longitude || '';
        option.dataset.recipientName = address.recipient_name || '';
        option.dataset.recipientPhone = address.recipient_phone || '';
        option.textContent = `${address.label} - ${address.address}`;
        select.appendChild(option);
    });
}

function updateDeliveryAddressSnapshot(force = true) {
    const invoiceSelect = document.getElementById('invoice-address-select');
    const shippingSelect = document.getElementById('shipping-address-select');
    const sameAsInvoice = document.getElementById('shipping_same_as_invoice');
    const addressInput = document.getElementById('delivery-address');
    const latitudeInput = document.getElementById('delivery-latitude');
    const longitudeInput = document.getElementById('delivery-longitude');

    if (!invoiceSelect || !shippingSelect || !addressInput) {
        return;
    }

    shippingSelect.disabled = Boolean(sameAsInvoice?.checked);
    const sourceSelect = sameAsInvoice?.checked ? invoiceSelect : shippingSelect;
    const selectedOption = sourceSelect.options[sourceSelect.selectedIndex];
    const address = selectedOption?.dataset.address || '';
    const latitude = selectedOption?.dataset.latitude || '';
    const longitude = selectedOption?.dataset.longitude || '';

    if (force || !addressInput.value.trim()) {
        addressInput.value = address;
    }

    if (latitudeInput && (force || !latitudeInput.value.trim())) {
        latitudeInput.value = latitude;
    }

    if (longitudeInput && (force || !longitudeInput.value.trim())) {
        longitudeInput.value = longitude;
    }
}

function toggleFulfillmentMode() {
    const stockSelected = document.querySelector('input[name="fulfillment_type"]:checked').value === 'stock';
    const stockInfo = document.getElementById('stock-info');
    const jitInfo = document.getElementById('jit-info');
    
    if (stockSelected) {
        stockInfo.style.display = 'block';
        jitInfo.style.display = 'none';
        checkAllStock();
    } else {
        stockInfo.style.display = 'none';
        jitInfo.style.display = 'block';
        document.querySelectorAll('.stock-warning').forEach(el => el.style.display = 'none');
    }
}

function toggleDiscountType() {
    const discountType = document.getElementById('discount_type').value;
    const container = document.getElementById('discount_value_container');
    const label = document.getElementById('discount_label');
    
    if (discountType === 'none') {
        container.style.display = 'none';
        document.getElementById('discount_value').value = 0;
        document.getElementById('discount-row').style.display = 'none';
    } else {
        container.style.display = 'block';
        label.innerText = discountType === 'percent' ? 'Nilai Diskon (%)' : 'Nilai Diskon (Rp)';
        document.getElementById('discount-row').style.display = 'flex';
    }
    calculateGrandTotal();
}

function toggleShippingType() {
    const shippingType = document.getElementById('shipping_type').value;
    const weightContainer = document.getElementById('shipping_weight_container');
    const distanceContainer = document.getElementById('shipping_distance_container');
    const rateContainer = document.getElementById('shipping_rate_container');
    
    weightContainer.style.display = shippingType === 'weight' ? 'block' : 'none';
    distanceContainer.style.display = shippingType === 'distance' ? 'block' : 'none';
    rateContainer.style.display = shippingType === 'none' ? 'none' : 'block';
    calculateGrandTotal();
}

function togglePPN() {
    const includePPN = document.getElementById('include_ppn').checked;
    const ppnRow = document.getElementById('ppn-row');
    const ppnRateContainer = document.getElementById('ppn_rate_container');
    
    if (includePPN) {
        ppnRow.style.display = 'flex';
        ppnRateContainer.style.display = 'block';
    } else {
        ppnRow.style.display = 'none';
        ppnRateContainer.style.display = 'none';
        document.getElementById('ppn_rate').value = 11;
    }
    calculateGrandTotal();
}

function checkAllStock() {
    const rows = document.querySelectorAll('.product-row');
    rows.forEach((row, idx) => {
        const select = row.querySelector('.product-select');
        const selectedOption = select.options[select.selectedIndex];
        const hasStock = selectedOption?.getAttribute('data-has-stock') === 'true';
        const stockQty = parseInt(selectedOption?.getAttribute('data-stock') || 0);
        const quantity = parseInt(row.querySelector('.quantity-input').value) || 0;
        const warningDiv = row.querySelector('.stock-warning');
        
        const stockSelected = document.querySelector('input[name="fulfillment_type"]:checked').value === 'stock';
        if (stockSelected && (!hasStock || stockQty < quantity)) {
            warningDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Stok tidak mencukupi. Tersedia: ' + stockQty;
            warningDiv.style.display = 'block';
        } else {
            warningDiv.style.display = 'none';
        }
    });
}

function addProductRow() {
    const tbody = document.getElementById('products-tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'product-row';
    newRow.style.borderBottom = '1px solid var(--k-gray-200)';
    newRow.innerHTML = `
        <td style="padding: 0.5rem;">
            <select name="items[${productIndex}][product_id]" id="product-select-${productIndex}" class="product-select dms-native-select" required onchange="updateProductPrice(this, ${productIndex})">
                <option value="">-- Pilih Produk --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $productsWithStock[$product->id]['stock'] ?? 0 }}" data-has-stock="{{ $productsWithStock[$product->id]['has_stock'] ? 'true' : 'false' }}">
                        {{ $product->name }} ({{ $product->unit->name ?? '-' }}) - Rp {{ number_format($product->price, 0, ',', '.') }}
                    </option>
                @endforeach
            </select>
            <div class="dms-combobox js-searchable-dropdown product-search" data-select-id="product-select-${productIndex}" data-search-placeholder="Cari produk..."></div>
            <div class="stock-warning" style="display: none; font-size: 0.6rem; color: var(--k-red); margin-top: 0.25rem;"></div>
        </td>
        <td style="padding: 0.5rem;">
            <input type="number" name="items[${productIndex}][quantity]" class="quantity-input" value="1" min="1" onchange="calculateSubtotal(this, ${productIndex})" style="width: 100%; padding: 0.5rem; border: 1px solid var(--k-gray-300); border-radius: 6px; font-size: 0.75rem;">
        </td>
        <td style="padding: 0.5rem;">
            <span class="product-price-display" style="font-size: 0.75rem;">Rp 0</span>
            <input type="hidden" name="items[${productIndex}][price]" class="price-input" value="0">
        </td>
        <td style="padding: 0.5rem;">
            <input type="number" name="items[${productIndex}][discount_percent]" class="discount-percent-input" value="0" min="0" max="100" step="1" onchange="calculateSubtotal(this, ${productIndex})" style="width: 100%; padding: 0.4rem; border: 1px solid var(--k-gray-300); border-radius: 6px; font-size: 0.7rem;">
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
    initializeSearchableDropdowns(newRow);
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
    const discountPercent = parseInt(row.querySelector('.discount-percent-input').value) || 0;
    const discountAmount = (price * discountPercent / 100) * quantity;
    const subtotal = (price * quantity) - discountAmount;
    
    const subtotalDisplay = row.querySelector('.subtotal-display');
    const subtotalInput = row.querySelector('.subtotal-input');
    
    if (subtotalDisplay) {
        subtotalDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
        if (subtotalInput) subtotalInput.value = subtotal;
    }
    
    calculateGrandTotal();
    
    const stockSelected = document.querySelector('input[name="fulfillment_type"]:checked').value === 'stock';
    if (stockSelected) {
        const select = row.querySelector('.product-select');
        const selectedOption = select.options[select.selectedIndex];
        const hasStock = selectedOption?.getAttribute('data-has-stock') === 'true';
        const stockQty = parseInt(selectedOption?.getAttribute('data-stock') || 0);
        const warningDiv = row.querySelector('.stock-warning');
        
        if (!hasStock || stockQty < quantity) {
            warningDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Stok tidak mencukupi. Tersedia: ' + stockQty;
            warningDiv.style.display = 'block';
        } else {
            warningDiv.style.display = 'none';
        }
    }
}

function calculateGrandTotal() {
    let subtotal = 0;
    const subtotalInputs = document.querySelectorAll('.subtotal-input');
    subtotalInputs.forEach(input => {
        subtotal += parseInt(input.value) || 0;
    });
    
    // Order discount
    const discountType = document.getElementById('discount_type').value;
    let discountAmount = 0;
    const discountValue = parseFloat(document.getElementById('discount_value').value) || 0;
    
    if (discountType === 'percent') {
        discountAmount = subtotal * discountValue / 100;
    } else if (discountType === 'nominal') {
        discountAmount = discountValue;
    }
    
    const afterDiscount = subtotal - discountAmount;
    
    // Shipping cost
    const shippingType = document.getElementById('shipping_type').value;
    const shippingRate = parseInt(document.getElementById('shipping_rate').value) || 0;
    let shippingCost = shippingType === 'none' ? 0 : shippingRate;
    
    if (shippingType === 'weight') {
        const weight = parseFloat(document.getElementById('shipping_weight').value) || 0;
        shippingCost = weight * shippingRate;
    } else if (shippingType === 'distance') {
        const distance = parseInt(document.getElementById('shipping_distance').value) || 0;
        shippingCost = distance * shippingRate;
    }
    
    // Packing fee
    const packingFee = parseInt(document.getElementById('packing_fee').value) || 0;
    
    // PPN
    const includePPN = document.getElementById('include_ppn').checked;
    let ppnAmount = 0;
    let ppnRate = 0;
    
    if (includePPN) {
        ppnRate = parseFloat(document.getElementById('ppn_rate').value) || 11;
        ppnAmount = (afterDiscount + shippingCost + packingFee) * ppnRate / 100;
    }
    
    const grandTotal = afterDiscount + shippingCost + packingFee + ppnAmount;
    
    // Update display
    document.getElementById('subtotal-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
    document.getElementById('subtotal-input').value = subtotal;
    
    if (discountAmount > 0) {
        document.getElementById('discount-amount').innerText = '- Rp ' + new Intl.NumberFormat('id-ID').format(discountAmount);
        document.getElementById('discount-row').style.display = 'flex';
    } else {
        document.getElementById('discount-row').style.display = 'none';
    }
    
    document.getElementById('after-discount').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(afterDiscount);
    document.getElementById('shipping-cost').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(shippingCost);
    document.getElementById('packing-fee-display').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(packingFee);
    
    if (includePPN && ppnAmount > 0) {
        document.getElementById('ppn-amount').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(ppnAmount) + ' (' + ppnRate + '%)';
        document.getElementById('ppn-row').style.display = 'flex';
    } else {
        document.getElementById('ppn-row').style.display = 'none';
    }
    
    document.getElementById('grand-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
    document.getElementById('total-input').value = grandTotal;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    initializeSearchableDropdowns();
    fillDeliveryAddressFromCustomer(false);
    calculateGrandTotal();
    toggleFulfillmentMode();
    toggleDiscountType();
    toggleShippingType();
    togglePPN();
    
    document.getElementById('discount_type').addEventListener('change', calculateGrandTotal);
    document.getElementById('discount_value').addEventListener('input', calculateGrandTotal);
    document.getElementById('shipping_type').addEventListener('change', calculateGrandTotal);
    document.getElementById('shipping_rate').addEventListener('input', calculateGrandTotal);
    document.getElementById('shipping_weight').addEventListener('input', calculateGrandTotal);
    document.getElementById('shipping_distance').addEventListener('input', calculateGrandTotal);
    document.getElementById('packing_fee').addEventListener('input', calculateGrandTotal);
    document.getElementById('invoice-address-select').addEventListener('change', () => updateDeliveryAddressSnapshot(true));
    document.getElementById('shipping-address-select').addEventListener('change', () => updateDeliveryAddressSnapshot(true));
    document.getElementById('shipping_same_as_invoice').addEventListener('change', () => updateDeliveryAddressSnapshot(true));
    document.getElementById('include_ppn').addEventListener('change', calculateGrandTotal);
    document.getElementById('ppn_rate').addEventListener('input', calculateGrandTotal);
});

document.addEventListener('click', function(event) {
    if (event.target.closest('.dms-combobox')) {
        return;
    }

    document.querySelectorAll('.dms-combobox-menu').forEach(menu => {
        menu.hidden = true;
        const dropdown = menu.closest('.dms-combobox');
        dropdown?.classList.remove('is-open');
        dropdown?.closest('.product-row')?.classList.remove('dms-combobox-row-open');
        dropdown?.querySelector('.dms-combobox-trigger')?.setAttribute('aria-expanded', 'false');
    });
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
.dms-fee-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    grid-template-areas:
        "discount shipping"
        "packing shipping";
    align-items: start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.dms-fee-panel {
    min-width: 0;
}
.dms-fee-panel:nth-child(1) {
    grid-area: discount;
}
.dms-fee-panel:nth-child(2) {
    grid-area: shipping;
}
.dms-fee-panel-packing {
    grid-area: packing;
    padding-top: 0.85rem;
    border-top: 1px dashed var(--k-gray-200);
}
.dms-fee-title {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--k-gray-800);
    margin-bottom: 0.75rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid var(--k-gray-200);
}
.dms-fee-title i {
    color: var(--k-green);
}
.dms-shipping-extra {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.75rem;
    margin-top: 0.75rem;
}
.dms-fee-note {
    font-size: 0.72rem;
    color: var(--k-gray-500);
    margin: 0 0 0.65rem;
}
.dms-products-table-wrap {
    overflow: visible;
    position: relative;
}
.dms-native-select {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
.dms-combobox {
    position: relative;
    width: 100%;
}
.dms-combobox-trigger {
    width: 100%;
    min-height: 42px;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 6px;
    background: white;
    color: var(--k-gray-800);
    font-size: 0.8rem;
    text-align: left;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    cursor: pointer;
}
.dms-combobox-trigger:focus {
    outline: none;
    border-color: var(--k-green);
    box-shadow: 0 0 0 2px var(--k-green-light);
}
.dms-combobox-value {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.dms-combobox-menu {
    position: absolute;
    top: calc(100% + 0.25rem);
    left: 0;
    right: 0;
    z-index: 80;
    padding: 0.45rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 8px;
    background: white;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.14);
}
.dms-combobox-search {
    width: 100%;
    padding: 0.45rem 0.55rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 6px;
    font-size: 0.75rem;
    margin-bottom: 0.4rem;
}
.dms-combobox-search:focus {
    outline: none;
    border-color: var(--k-green);
    box-shadow: 0 0 0 2px var(--k-green-light);
}
.dms-combobox-options {
    max-height: 190px;
    overflow-y: auto;
}
.dms-combobox-option {
    width: 100%;
    padding: 0.45rem 0.55rem;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--k-gray-800);
    font-size: 0.75rem;
    text-align: left;
    cursor: pointer;
}
.dms-combobox-option:hover,
.dms-combobox-option.is-selected {
    background: var(--k-green-light);
    color: var(--k-green);
}
.dms-combobox-empty {
    padding: 0.55rem;
    color: var(--k-gray-500);
    font-size: 0.72rem;
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
@media (max-width: 900px) {
    .dms-fee-grid {
        grid-template-columns: 1fr;
        grid-template-areas:
            "discount"
            "shipping"
            "packing";
    }
}
</style>
@endsection
