@extends('layouts.sidebar')

@section('page-title', 'Buat Dokumen Stok')
@section('breadcrumb', 'Inventori / Dokumen Stok / Buat')

@section('content')
<style>
    .document-form-panel {
        border: 1px solid var(--k-gray-200);
        border-radius: 8px;
        background: var(--k-gray-50);
        padding: 1rem;
    }

    .document-header-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .document-item-row {
        display: grid;
        grid-template-columns: minmax(260px, 1.4fr) minmax(110px, .45fr) minmax(150px, .65fr) minmax(220px, 1fr) auto;
        gap: 0.65rem;
        align-items: end;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--k-gray-200);
    }

    .document-item-row:last-child {
        border-bottom: 0;
    }

    @media (max-width: 1100px) {
        .document-header-grid,
        .document-item-row {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 720px) {
        .document-header-grid,
        .document-item-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Buat Dokumen Stok</h3>
            <p class="dms-section-subtitle">Gunakan BTB/BKB untuk barang masuk-keluar dan Transfer Gudang untuk mutasi antar gudang.</p>
        </div>
        <a href="{{ route('inventory-documents.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div style="margin-bottom: 1rem; padding: 0.75rem 0.9rem; border: 1px solid #fecaca; border-radius: 8px; background: #fff1f2; color: #b91c1c; font-weight: 700;">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('inventory-documents.store') }}" method="POST" class="document-form-panel">
        @csrf
        <div class="document-header-grid">
            <div class="form-group">
                <label class="form-label">Tipe Dokumen <span class="dms-required">*</span></label>
                <select name="type" id="document-type" class="form-control" required>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ old('type', $selectedType) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal <span class="dms-required">*</span></label>
                <input type="date" name="document_date" value="{{ old('document_date', now()->format('Y-m-d')) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label" id="warehouse-label">Gudang <span class="dms-required">*</span></label>
                <select name="warehouse_id" id="warehouse-id" class="form-control" required>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ (string) old('warehouse_id', $warehouses->firstWhere('is_default', true)?->id ?? $warehouse->id) === (string) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="transfer-destination-field" style="display: none;">
                <label class="form-label">Gudang Tujuan <span class="dms-required">*</span></label>
                <select name="transfer_to_warehouse_id" id="transfer-to-warehouse-id" class="form-control">
                    <option value="">-- Pilih Gudang Tujuan --</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ (string) old('transfer_to_warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Cabang</label>
                <select name="company_branch_id" class="form-control">
                    <option value="">Global / tanpa cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">No. Referensi</label>
                <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control" placeholder="PO / DO / manual">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Catatan</label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Catatan operasional">
            </div>
        </div>

        <div class="dms-section-header" style="margin-top: 0.4rem; margin-bottom: 0.4rem;">
            <div>
                <h4 class="dms-section-title" style="font-size: 0.98rem;">Item Barang</h4>
                <p class="dms-section-subtitle">Tambahkan produk dan qty yang akan diposting ke stok.</p>
            </div>
            <button type="button" class="dms-btn dms-btn-outline" onclick="addDocumentItem()">
                <i class="bi bi-plus-circle"></i> Tambah Item
            </button>
        </div>

        <div id="document-items">
            <div class="document-item-row">
                <div class="form-group">
                    <label class="form-label">Produk <span class="dms-required">*</span></label>
                    <select name="items[0][product_id]" class="form-control" required>
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}{{ $product->unit ? ' - '.$product->unit->symbol : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Qty <span class="dms-required">*</span></label>
                    <input type="number" name="items[0][quantity]" value="1" class="form-control" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nilai / Unit</label>
                    <input type="number" name="items[0][unit_cost]" class="form-control" min="0" placeholder="Opsional">
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan Item</label>
                    <input type="text" name="items[0][notes]" class="form-control" placeholder="Opsional">
                </div>
                <button type="button" class="dms-btn dms-btn-outline" onclick="removeDocumentItem(this)" aria-label="Hapus item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.65rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('inventory-documents.index') }}" class="dms-btn dms-btn-outline">Batal</a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Simpan Draft
            </button>
        </div>
    </form>
</div>

<script>
    let documentItemIndex = 1;
    const productOptions = `@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}{{ $product->unit ? ' - '.$product->unit->symbol : '' }}</option>@endforeach`;

    function addDocumentItem() {
        const wrap = document.getElementById('document-items');
        const div = document.createElement('div');
        div.className = 'document-item-row';
        div.innerHTML = `
            <div class="form-group">
                <label class="form-label">Produk <span class="dms-required">*</span></label>
                <select name="items[${documentItemIndex}][product_id]" class="form-control" required>
                    <option value="">-- Pilih Produk --</option>${productOptions}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Qty <span class="dms-required">*</span></label>
                <input type="number" name="items[${documentItemIndex}][quantity]" value="1" class="form-control" min="1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nilai / Unit</label>
                <input type="number" name="items[${documentItemIndex}][unit_cost]" class="form-control" min="0" placeholder="Opsional">
            </div>
            <div class="form-group">
                <label class="form-label">Catatan Item</label>
                <input type="text" name="items[${documentItemIndex}][notes]" class="form-control" placeholder="Opsional">
            </div>
            <button type="button" class="dms-btn dms-btn-outline" onclick="removeDocumentItem(this)" aria-label="Hapus item">
                <i class="bi bi-trash"></i>
            </button>
        `;
        wrap.appendChild(div);
        documentItemIndex++;
    }

    function removeDocumentItem(button) {
        const rows = document.querySelectorAll('.document-item-row');
        if (rows.length <= 1) {
            return;
        }
        button.closest('.document-item-row').remove();
    }

    function syncDocumentTypeFields() {
        const type = document.getElementById('document-type').value;
        const destinationField = document.getElementById('transfer-destination-field');
        const destinationSelect = document.getElementById('transfer-to-warehouse-id');
        const warehouseLabel = document.getElementById('warehouse-label');
        const isTransfer = type === '{{ \App\Models\InventoryDocument::TYPE_TRANSFER }}';

        destinationField.style.display = isTransfer ? 'block' : 'none';
        destinationSelect.required = isTransfer;
        warehouseLabel.innerHTML = isTransfer
            ? 'Gudang Asal <span class="dms-required">*</span>'
            : 'Gudang <span class="dms-required">*</span>';

        if (! isTransfer) {
            destinationSelect.value = '';
        }
    }

    document.getElementById('document-type').addEventListener('change', syncDocumentTypeFields);
    syncDocumentTypeFields();
</script>
@endsection
