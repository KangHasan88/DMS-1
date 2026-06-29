@extends('layouts.sidebar')

@section('page-title', 'Terima Barang')
@section('breadcrumb', 'Purchase Orders / Terima Barang')

@section('content')
<div class="dms-card">
    <div class="dms-section-header" style="align-items: flex-start; gap: 1rem;">
        <div>
            <h3 class="dms-section-title">Terima Barang</h3>
            <p class="dms-section-subtitle">
                Catat BTB dari PO {{ $purchaseOrder->po_number }} dan update stok sesuai quantity yang benar-benar diterima.
            </p>
        </div>
        <span class="dms-badge dms-badge-info">{{ $purchaseOrder->status_label }}</span>
    </div>

    <div class="dms-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 1rem;">
        <div class="dms-stat-card">
            <span class="dms-stat-label">Pemasok</span>
            <strong class="dms-stat-value" style="font-size: 1.05rem;">{{ $purchaseOrder->supplier->name }}</strong>
            <span class="dms-stat-subtitle">{{ $purchaseOrder->po_number }}</span>
        </div>
        <div class="dms-stat-card">
            <span class="dms-stat-label">Tanggal PO</span>
            <strong class="dms-stat-value" style="font-size: 1.05rem;">{{ $purchaseOrder->order_date?->format('d M Y') ?? '-' }}</strong>
            <span class="dms-stat-subtitle">Estimasi: {{ $purchaseOrder->expected_delivery_date?->format('d M Y') ?? '-' }}</span>
        </div>
        <div class="dms-stat-card">
            <span class="dms-stat-label">Total Item</span>
            <strong class="dms-stat-value" style="font-size: 1.05rem;">{{ number_format($purchaseOrder->items->sum('quantity'), 0, ',', '.') }}</strong>
            <span class="dms-stat-subtitle">Sisa: {{ number_format($purchaseOrder->items->sum('remaining_quantity'), 0, ',', '.') }}</span>
        </div>
    </div>

    <form action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST">
        @csrf

        <div class="dms-filter-panel" style="margin-bottom: 1rem;">
            <div class="form-group" style="max-width: 260px; margin: 0;">
                <label class="form-label">Tanggal Penerimaan <span class="dms-required">*</span></label>
                <input type="date" name="received_date" class="form-control" value="{{ old('received_date', date('Y-m-d')) }}" required>
                @error('received_date') <span class="dms-field-error">{{ $message }}</span> @enderror
            </div>
            <div style="align-self: end; color: var(--k-gray-600); font-size: 0.9rem;">
                <i class="bi bi-info-circle"></i>
                Stok bertambah hanya untuk qty yang diisi lebih dari 0.
            </div>
        </div>

        <div class="dms-table-wrap">
            <table class="dms-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Satuan</th>
                        <th>Qty PO</th>
                        <th>Sudah Diterima</th>
                        <th>Sisa</th>
                        <th>Qty Diterima</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $index => $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product->name }}</strong>
                            </td>
                            <td>{{ $item->product->unit->name ?? '-' }}</td>
                            <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td>{{ number_format($item->received_quantity, 0, ',', '.') }}</td>
                            <td class="remaining-qty" data-max="{{ $item->remaining_quantity }}">
                                <span class="dms-badge dms-badge-warning">{{ number_format($item->remaining_quantity, 0, ',', '.') }}</span>
                            </td>
                            <td style="width: 150px;">
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                <input type="number"
                                       name="items[{{ $index }}][received_quantity]"
                                       class="form-control receive-qty"
                                       value="{{ old("items.$index.received_quantity", $item->remaining_quantity) }}"
                                       min="0"
                                       max="{{ $item->remaining_quantity }}">
                                @error("items.$index.received_quantity") <span class="dms-field-error">{{ $message }}</span> @enderror
                            </td>
                            <td style="min-width: 240px;">
                                <input type="text"
                                       name="items[{{ $index }}][notes]"
                                       class="form-control"
                                       value="{{ old("items.$index.notes") }}"
                                       placeholder="Opsional">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="dms-form-actions" style="justify-content: space-between; margin-top: 1rem;">
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
        const remainingCell = row.querySelector('.remaining-qty');
        const maxQty = parseInt(remainingCell.getAttribute('data-max'), 10);
        let qty = parseInt(this.value, 10) || 0;

        if (qty > maxQty) {
            qty = maxQty;
            this.value = qty;
        }

        if (qty < 0) {
            qty = 0;
            this.value = qty;
        }

        const newRemaining = maxQty - qty;
        const badgeClass = newRemaining === 0 ? 'dms-badge-success' : 'dms-badge-warning';
        const label = newRemaining === 0 ? 'Selesai' : newRemaining.toLocaleString('id-ID');
        remainingCell.innerHTML = '<span class="dms-badge ' + badgeClass + '">' + label + '</span>';
    });
});
</script>

<style>
.dms-required,
.dms-field-error {
    color: var(--k-danger);
}

.dms-field-error {
    display: block;
    font-size: 0.78rem;
    font-weight: 500;
    margin-top: 0.35rem;
}
</style>
@endsection
