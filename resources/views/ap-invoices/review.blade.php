@extends('layouts.sidebar')

@section('page-title', 'Review AP Invoice')
@section('breadcrumb', 'Finance / Invoice AP / Review')

@section('content')
@php
    $defaultTaxRate = (float) old('tax_rate', 0);
@endphp
<div class="dms-card">
    <div class="dms-section-header" style="align-items: flex-start; gap: 1rem;">
        <div>
            <h3 class="dms-section-title">Review AP Invoice</h3>
            <p class="dms-section-subtitle">
                Cocokkan PO, penerimaan barang, dan nilai invoice sebelum hutang supplier diposting.
            </p>
        </div>
        <span class="dms-badge dms-badge-{{ $isMatched ? 'success' : 'warning' }}">
            {{ $isMatched ? 'Matched' : 'Ada Selisih Qty' }}
        </span>
    </div>

    <div class="dms-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 1rem;">
        <div class="dms-stat-card">
            <span class="dms-stat-label">Purchase Order</span>
            <strong class="dms-stat-value" style="font-size: 1.05rem;">{{ $purchaseOrder->po_number }}</strong>
            <span class="dms-stat-subtitle">{{ $purchaseOrder->supplier?->name ?? '-' }}</span>
        </div>
        <div class="dms-stat-card">
            <span class="dms-stat-label">Tanggal Terima</span>
            <strong class="dms-stat-value" style="font-size: 1.05rem;">{{ $purchaseOrder->received_date?->format('d M Y') ?? '-' }}</strong>
            <span class="dms-stat-subtitle">{{ $purchaseOrder->companyBranch?->name ?? 'Global / tanpa cabang' }}</span>
        </div>
        <div class="dms-stat-card">
            <span class="dms-stat-label">Nilai PO</span>
            <strong class="dms-stat-value dms-money" style="font-size: 1.05rem;">Rp {{ number_format($orderedSubtotal, 0, ',', '.') }}</strong>
            <span class="dms-stat-subtitle">Nilai order awal</span>
        </div>
        <div class="dms-stat-card">
            <span class="dms-stat-label">Nilai Diterima</span>
            <strong class="dms-stat-value dms-money" style="font-size: 1.05rem;">Rp {{ number_format($receivedSubtotal, 0, ',', '.') }}</strong>
            <span class="dms-stat-subtitle">Dasar AP invoice</span>
        </div>
    </div>

    <form action="{{ route('ap-invoices.store') }}" method="POST" id="ap-invoice-review-form">
        @csrf
        <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">

        <div class="dms-table-wrap">
            <table class="dms-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Qty PO</th>
                        <th>Qty Diterima</th>
                        <th>Satuan</th>
                        <th>Harga PO</th>
                        <th>Harga Invoice Supplier</th>
                        <th>Selisih</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                        @php
                            $submittedPrice = old("item_prices.{$item->id}", $item->price);
                            $lineTotal = (int) $item->received_quantity * (int) $submittedPrice;
                            $lineMatched = (int) $item->received_quantity === (int) $item->quantity;
                        @endphp
                        <tr class="js-ap-line" data-qty="{{ (int) $item->received_quantity }}" data-po-price="{{ (int) $item->price }}">
                            <td>
                                <strong>{{ $item->product?->name ?? 'Item PO' }}</strong>
                                <div class="dms-muted">{{ $lineMatched ? 'Qty sesuai PO' : 'Qty diterima berbeda dari PO' }}</div>
                            </td>
                            <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td>{{ number_format($item->received_quantity, 0, ',', '.') }}</td>
                            <td>{{ $item->product?->unit?->name ?? '-' }}</td>
                            <td class="dms-money">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td style="min-width: 180px;">
                                <input
                                    type="number"
                                    min="0"
                                    name="item_prices[{{ $item->id }}]"
                                    value="{{ $submittedPrice }}"
                                    class="form-control js-ap-price"
                                    aria-label="Harga invoice supplier {{ $item->product?->name ?? 'Item PO' }}"
                                >
                                @error("item_prices.{$item->id}") <span class="dms-error">{{ $message }}</span> @enderror
                            </td>
                            <td>
                                <span class="dms-badge js-ap-variance-badge dms-badge-{{ (int) $submittedPrice === (int) $item->price ? 'success' : 'warning' }}">
                                    {{ (int) $submittedPrice === (int) $item->price ? 'Match' : 'Selisih Harga' }}
                                </span>
                            </td>
                            <td class="dms-money js-ap-line-total">Rp {{ number_format($lineTotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="dms-form-section" style="margin-top: 1rem; padding: 1rem; border: 1px solid var(--k-border); border-radius: 8px; background: #f8fbff;">
            <div class="dms-section-header" style="padding-bottom: 0.75rem; margin-bottom: 1rem; border-bottom: 1px solid var(--k-border);">
                <div>
                    <h3 class="dms-section-title">Pajak & Catatan Matching</h3>
                    <p class="dms-section-subtitle">Isi PPN dan nomor faktur pajak jika dokumen supplier sudah diterima.</p>
                </div>
            </div>

            <div class="dms-form-grid">
                <div class="form-group">
                    <label class="form-label">PPN Supplier (%)</label>
                    <input type="number" min="0" max="100" step="0.01" name="tax_rate" id="ap-tax-rate" value="{{ old('tax_rate', 0) }}" class="form-control">
                    @error('tax_rate') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">No. Faktur Pajak Supplier</label>
                    <input type="text" name="supplier_tax_invoice_number" value="{{ old('supplier_tax_invoice_number') }}" class="form-control" placeholder="Opsional">
                    @error('supplier_tax_invoice_number') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Faktur Pajak</label>
                    <input type="date" name="supplier_tax_invoice_date" value="{{ old('supplier_tax_invoice_date') }}" class="form-control">
                    @error('supplier_tax_invoice_date') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group dms-form-span-3">
                    <label class="form-label">Catatan Selisih</label>
                    <input type="text" name="variance_note" value="{{ old('variance_note') }}" class="form-control" placeholder="Wajib jika harga invoice supplier berbeda dari PO">
                    @error('variance_note') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="dms-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-top: 1rem;">
                <div class="dms-stat-card">
                    <span class="dms-stat-label">DPP Supplier</span>
                    <strong class="dms-stat-value dms-money" id="ap-review-subtotal">Rp {{ number_format($receivedSubtotal, 0, ',', '.') }}</strong>
                    <span class="dms-stat-subtitle">Qty diterima x harga invoice</span>
                </div>
                <div class="dms-stat-card">
                    <span class="dms-stat-label">PPN Masukan</span>
                    <strong class="dms-stat-value dms-money" id="ap-review-tax">Rp {{ number_format((int) round($receivedSubtotal * ($defaultTaxRate / 100)), 0, ',', '.') }}</strong>
                    <span class="dms-stat-subtitle">Masuk akun pajak masukan</span>
                </div>
                <div class="dms-stat-card">
                    <span class="dms-stat-label">Total Hutang</span>
                    <strong class="dms-stat-value dms-money" id="ap-review-total">Rp {{ number_format($receivedSubtotal + (int) round($receivedSubtotal * ($defaultTaxRate / 100)), 0, ',', '.') }}</strong>
                    <span class="dms-stat-subtitle">Nilai yang diposting ke AP</span>
                </div>
            </div>
        </div>

        <div class="dms-form-actions" style="justify-content: space-between; margin-top: 1rem;">
            <a href="{{ route('ap-invoices.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-journal-check"></i> Terbitkan AP Invoice
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const money = new Intl.NumberFormat('id-ID');
    const form = document.getElementById('ap-invoice-review-form');
    if (!form) return;

    const updateTotals = () => {
        let subtotal = 0;

        form.querySelectorAll('.js-ap-line').forEach((line) => {
            const qty = Number(line.dataset.qty || 0);
            const poPrice = Number(line.dataset.poPrice || 0);
            const priceInput = line.querySelector('.js-ap-price');
            const invoicePrice = Number(priceInput.value || 0);
            const lineTotal = qty * invoicePrice;
            subtotal += lineTotal;

            line.querySelector('.js-ap-line-total').textContent = 'Rp ' + money.format(lineTotal);
            const badge = line.querySelector('.js-ap-variance-badge');
            const matched = invoicePrice === poPrice;
            badge.textContent = matched ? 'Match' : 'Selisih Harga';
            badge.classList.toggle('dms-badge-success', matched);
            badge.classList.toggle('dms-badge-warning', !matched);
        });

        const taxRate = Number(document.getElementById('ap-tax-rate').value || 0);
        const tax = Math.round(subtotal * (taxRate / 100));
        document.getElementById('ap-review-subtotal').textContent = 'Rp ' + money.format(subtotal);
        document.getElementById('ap-review-tax').textContent = 'Rp ' + money.format(tax);
        document.getElementById('ap-review-total').textContent = 'Rp ' + money.format(subtotal + tax);
    };

    form.querySelectorAll('.js-ap-price, #ap-tax-rate').forEach((input) => {
        input.addEventListener('input', updateTotals);
    });

    updateTotals();
});
</script>
@endpush
