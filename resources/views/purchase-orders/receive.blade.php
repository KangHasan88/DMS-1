@extends('layouts.sidebar')

@section('page-title', 'Receive Barang')
@section('breadcrumb', 'Purchase Orders / Receive')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Receive Barang</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">
            PO #{{ $purchaseOrder->po_number }} - Supplier: {{ $purchaseOrder->supplier->name }}
        </p>
        <div style="margin-top: 0.5rem; padding: 0.5rem; background: var(--k-green-light); border-radius: 6px;">
            <i class="bi bi-info-circle"></i> 
            Masukkan jumlah barang yang diterima. Stock akan otomatis bertambah sesuai penerimaan.
        </div>
    </div>

    <form action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label class="form-label">Tanggal Penerimaan <span style="color: var(--k-red);">*</span></label>
            <input type="date" name="received_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            @error('received_date') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
        </div>
        
        <div style="overflow-x: auto; margin-top: 1.5rem;">
            <table class="dms-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Satuan</th>
                        <th>Qty PO</th>
                        <th>Sudah Diterima</th>
                        <th>Sisa</th>
                        <th>Jumlah Diterima</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $index => $item)
                    <tr>
                        <td>
                            <div style="font-weight: 600;">{{ $item->product->name }}</div>
                        </td>
                        <td>{{ $item->product->unit->name ?? '-' }}</td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td>{{ number_format($item->received_quantity) }}</td>
                        <td class="remaining-qty" data-max="{{ $item->remaining_quantity }}">
                            <span class="dms-badge dms-badge-warning">{{ number_format($item->remaining_quantity) }}</span>
                        </td>
                        <td>
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                            <input type="number" name="items[{{ $index }}][received_quantity]" 
                                   class="form-control receive-qty" 
                                   value="{{ $item->remaining_quantity }}" 
                                   min="0" 
                                   max="{{ $item->remaining_quantity }}"
                                   style="width: 120px;">
                        </td>
                        <td>
                            <input type="text" name="items[{{ $index }}][notes]" class="form-control" placeholder="Catatan (opsional)" style="width: 200px;">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-box-seam"></i> Konfirmasi Penerimaan
            </button>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('.receive-qty').forEach(input => {
    input.addEventListener('change', function() {
        const row = this.closest('tr');
        const remainingElem = row.querySelector('.remaining-qty span');
        const maxQty = parseInt(row.querySelector('.remaining-qty').getAttribute('data-max'));
        let qty = parseInt(this.value) || 0;
        
        if (qty > maxQty) {
            qty = maxQty;
            this.value = qty;
        }
        
        const newRemaining = maxQty - qty;
        remainingElem.innerText = newRemaining.toLocaleString('id-ID');
        
        if (newRemaining === 0) {
            remainingElem.parentElement.innerHTML = '<span class="dms-badge dms-badge-success">Selesai</span>';
        } else {
            remainingElem.parentElement.innerHTML = '<span class="dms-badge dms-badge-warning">' + newRemaining.toLocaleString('id-ID') + '</span>';
        }
    });
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
.dms-table td {
    vertical-align: middle;
}
</style>
@endsection
