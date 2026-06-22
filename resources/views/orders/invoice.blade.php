@extends('layouts.sidebar')

@section('page-title', data_get($invoiceDocument ?? [], 'title', 'Invoice Order'))
@section('breadcrumb', 'Orders / ' . data_get($invoiceDocument ?? [], 'title', 'Invoice'))

@section('content')
@php
    $invoiceAddress = $order->invoice_address_snapshot ?: $order->address;
    $shippingAddress = $order->shipping_address_snapshot ?: $order->address;
    $paymentTimingLabel = $order->payment_timing == 'pre_paid' ? 'Pre-paid' : 'Post-paid';
    $fulfillmentLabel = $order->useStockMode() ? 'Stock' : 'BLJ';
    $companyDisplayName = data_get($invoiceCompany, 'display_name', config('app.name', 'Kurmigo DMS'));
    $companyLegalName = data_get($invoiceCompany, 'legal_name', $companyDisplayName);
    $companyCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', data_get($invoiceCompany, 'code', 'KMG')) ?: 'KMG'), 0, 3);
    $companyNpwp = data_get($invoiceCompany, 'npwp', '-');
    $companyPhone = data_get($invoiceCompany, 'phone', '-');
    $companyEmail = data_get($invoiceCompany, 'email', '-');
    $branchName = data_get($invoiceBranch, 'name', 'Cabang Tangerang');
    $branchCode = data_get($invoiceBranch, 'code', '-');
    $branchAddress = data_get($invoiceBranch, 'address', '-');
    $branchPhone = data_get($invoiceBranch, 'phone', '-');
    $branchEmail = data_get($invoiceBranch, 'email', '-');
    $invoicePhone = ($branchPhone && $branchPhone !== '-') ? $branchPhone : $companyPhone;
    $invoiceEmail = ($branchEmail && $branchEmail !== '-') ? $branchEmail : $companyEmail;
    $documentTitle = data_get($invoiceDocument, 'title', 'Invoice Order');
    $documentSubtitle = data_get($invoiceDocument, 'subtitle', 'Dokumen Invoice Order');
    $isProforma = str_contains(strtolower($documentTitle), 'proforma');
    $isDeliveryOrder = str_contains(strtolower($documentTitle), 'delivery') || strtolower($documentTitle) === 'do';
    $documentDisplayTitle = $isProforma ? 'PROFORMA INVOICE' : ($isDeliveryOrder ? 'DELIVERY ORDER' : 'INVOICE');
    $documentNumberLabel = $isProforma ? 'No. Proforma' : ($isDeliveryOrder ? 'No. DO' : 'No. Invoice');
    $documentPrefix = $isProforma ? 'PI' : ($isDeliveryOrder ? 'DO' : 'INV');
    $documentNumber = $order->documentNumber($documentPrefix, $companyCode, $branchCode);
    $recipientName = $order->shipping_recipient_name ?: ($order->user->name ?? '-');
    $recipientPhone = $order->shipping_recipient_phone ?: ($order->user->phone ?? '-');
@endphp

<div class="invoice-shell dms-card">
    <div class="invoice-toolbar">
        <div>
            <div class="invoice-title">
                <i class="bi bi-receipt"></i>
                {{ $documentTitle }}
            </div>
            <div class="invoice-subtitle">
                {{ $order->order_number }} - {{ $order->status_label }}
            </div>
        </div>
        <div class="invoice-actions">
            <a href="{{ route('orders.show', $order) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="button" onclick="window.print()" class="dms-btn dms-btn-primary">
                <i class="bi bi-printer"></i> Cetak
            </button>
        </div>
    </div>

    <div class="invoice-paper">
        <div class="invoice-head">
            <div class="company-block">
                <div class="company-name">{{ $companyLegalName }}</div>
                <div class="company-note">{{ $companyDisplayName }}</div>
                @if($branchName && $branchName !== '-')
                    <div class="company-branch">{{ $branchName }}</div>
                @endif
                @if(($companyNpwp && $companyNpwp !== '-') || ($invoicePhone && $invoicePhone !== '-') || ($invoiceEmail && $invoiceEmail !== '-'))
                    <div class="company-mini">
                        @if($companyNpwp && $companyNpwp !== '-')
                            <span>NPWP: {{ $companyNpwp }}</span>
                        @endif
                        @if($invoicePhone && $invoicePhone !== '-')
                            <span>Telp: {{ $invoicePhone }}</span>
                        @endif
                        @if($invoiceEmail && $invoiceEmail !== '-')
                            <span>Email: {{ $invoiceEmail }}</span>
                        @endif
                    </div>
                @endif
                @if($branchAddress && $branchAddress !== '-')
                    <div class="company-address">{{ $branchAddress }}</div>
                @endif
                @if($companyAddress = data_get($invoiceCompany, 'address'))
                    <div class="company-address">Kantor pusat: {{ $companyAddress }}</div>
                @endif
            </div>
            <div class="document-panel">
                <div class="document-type">{{ $documentDisplayTitle }}</div>
                <div class="document-subtitle">{{ $documentSubtitle }}</div>
                <div class="document-number">
                    <span>{{ $documentNumberLabel }}</span>
                    <strong>{{ $documentNumber }}</strong>
                </div>
            </div>
        </div>

        <div class="invoice-meta">
            <div><span>No. Order</span><strong>{{ $order->order_number }}</strong></div>
            <div><span>Tanggal Order</span><strong>{{ optional($order->created_at)->format('d M Y H:i') }}</strong></div>
            <div><span>Tanggal Kirim</span><strong>{{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('d M Y') : '-' }} {{ $order->delivery_time_slot ? '('.$order->delivery_time_slot.')' : '' }}</strong></div>
            <div><span>No Resi</span><strong>{{ $order->tracking_code ?? '-' }}</strong></div>
        </div>

        <div class="invoice-strip">
            <div><span>Pelanggan</span><strong>{{ $order->user->name ?? '-' }}</strong></div>
            <div><span>Telepon</span><strong>{{ $order->user->phone ?? '-' }}</strong></div>
            @if($isDeliveryOrder)
                <div><span>Penerima</span><strong>{{ $recipientName }}</strong></div>
                <div><span>Kontak Penerima</span><strong>{{ $recipientPhone }}</strong></div>
            @else
                <div><span>Skema</span><strong>{{ $paymentTimingLabel }}</strong></div>
                <div><span>Fulfillment</span><strong>{{ $fulfillmentLabel }}</strong></div>
            @endif
        </div>

        <div class="invoice-grid">
            <div class="invoice-block">
                <div class="block-title">Alamat Invoice / Dokumen</div>
                <div class="block-body">{{ $invoiceAddress }}</div>
            </div>
            <div class="invoice-block">
                <div class="block-title">Alamat Pengiriman</div>
                <div class="block-body">{{ $shippingAddress }}</div>
                @if($order->shipping_same_as_invoice)
                    <div class="mini-badge">Sama dengan invoice</div>
                @endif
            </div>
        </div>

        <div class="section-title">Rincian Produk</div>
        <table class="invoice-table">
            <thead>
                <tr>
                    @if($isDeliveryOrder)
                        <th style="width: 76%;">Produk</th>
                        <th style="width: 12%; text-align:center;">Qty</th>
                        <th style="width: 12%; text-align:center;">Status</th>
                    @else
                        <th style="width: 48%;">Produk</th>
                        <th style="width: 8%; text-align:center;">Qty</th>
                        <th style="width: 16%; text-align:right;">Harga</th>
                        <th style="width: 14%; text-align:right;">Diskon</th>
                        <th style="width: 14%; text-align:right;">Subtotal</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td style="text-align:center;">{{ number_format($item->quantity) }}</td>
                        @if($isDeliveryOrder)
                            <td style="text-align:center;">Dikirim</td>
                        @else
                            <td style="text-align:right;">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td style="text-align:right;">{{ $item->discount > 0 ? 'Rp '.number_format($item->discount, 0, ',', '.') : '-' }}</td>
                            <td style="text-align:right; font-weight:700;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        @unless($isDeliveryOrder)
            <div class="totals-wrap">
                <table class="totals-table">
                    <tr><td>Subtotal</td><td>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
                    <tr><td>Diskon Order</td><td>- Rp {{ number_format($order->discount_amount ?? 0, 0, ',', '.') }}</td></tr>
                    <tr><td>Ongkos Kirim</td><td>Rp {{ number_format($order->delivery_fee ?? 0, 0, ',', '.') }}</td></tr>
                    @if($order->requiresPacking())
                        <tr><td>Biaya Packing</td><td>Rp {{ number_format($order->packing_fee ?? 0, 0, ',', '.') }}</td></tr>
                    @endif
                    @if(($order->ppn_amount ?? 0) > 0)
                        <tr><td>PPN ({{ $order->ppn_rate }}%)</td><td>Rp {{ number_format($order->ppn_amount, 0, ',', '.') }}</td></tr>
                    @endif
                    <tr class="grand-total"><td>Grand Total</td><td>Rp {{ number_format($order->grand_total ?? $order->total, 0, ',', '.') }}</td></tr>
                </table>
            </div>
        @endunless

        @if($order->notes)
            <div class="section-title">Catatan</div>
            <div class="notes-grid">
                <div class="note-box">
                    <div class="note-label">Catatan</div>
                    <div class="note-body">{{ $order->notes }}</div>
                </div>
            </div>
        @endif

        <div class="approval-section">
            <div class="approval-box">
                <div class="approval-title">{{ $isDeliveryOrder ? 'Disiapkan oleh' : 'Dibuat oleh' }}</div>
                <div class="signature-space"></div>
                <div class="signature-line"></div>
                <div class="signature-caption">Admin</div>
            </div>
            <div class="approval-box stamp-box">
                <div class="approval-title">{{ $isDeliveryOrder ? 'Driver / Pengirim' : ($isProforma ? 'Disetujui' : 'Materai / Stempel') }}</div>
                <div class="signature-space">
                    @unless($isProforma || $isDeliveryOrder)
                        <span>Materai bila diperlukan</span>
                    @endunless
                </div>
                <div class="signature-line"></div>
                <div class="signature-caption">{{ $companyLegalName }}</div>
            </div>
            <div class="approval-box">
                <div class="approval-title">Diterima oleh</div>
                <div class="signature-space"></div>
                <div class="signature-line"></div>
                <div class="signature-caption">Pelanggan</div>
            </div>
        </div>
    </div>
</div>

<style>
.invoice-shell {
    background: #fff;
    border: 1px solid var(--k-gray-200);
}

.invoice-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.invoice-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--k-gray-800);
}

.invoice-subtitle {
    margin-top: 0.15rem;
    color: var(--k-gray-500);
    font-size: 0.78rem;
}

.invoice-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.invoice-paper {
    border: 1px solid var(--k-gray-200);
    border-radius: 10px;
    padding: 0.95rem;
}

.invoice-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    border-bottom: 1px solid var(--k-gray-200);
    padding-bottom: 0.7rem;
    margin-bottom: 0.7rem;
}

.company-block {
    min-width: 0;
    max-width: 58%;
}

.company-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--k-gray-800);
    line-height: 1.2;
}

.company-note {
    font-size: 0.75rem;
    color: var(--k-gray-500);
    margin-top: 0.1rem;
}

.company-branch {
    margin-top: 0.16rem;
    font-size: 0.72rem;
    color: var(--k-gray-600);
    line-height: 1.3;
}

.company-mini,
.company-address {
    margin-top: 0.18rem;
    font-size: 0.68rem;
    color: var(--k-gray-500);
    line-height: 1.35;
}

.company-mini {
    display: flex;
    gap: 0.35rem 0.6rem;
    flex-wrap: wrap;
}

.company-mini span {
    white-space: nowrap;
}

.document-panel {
    min-width: 240px;
    max-width: 42%;
    text-align: right;
}

.document-type {
    color: var(--k-gray-900);
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: 0.02em;
}

.document-subtitle {
    margin-top: 0.25rem;
    color: var(--k-gray-500);
    font-size: 0.72rem;
}

.document-number {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.1rem;
    margin-top: 0.55rem;
    padding: 0.4rem 0.55rem;
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    background: var(--k-gray-50);
}

.document-number span {
    color: var(--k-gray-500);
    font-size: 0.68rem;
}

.document-number strong {
    color: var(--k-gray-900);
    font-size: 0.82rem;
}

.invoice-meta {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.35rem 0.75rem;
    padding: 0.5rem 0;
    margin-bottom: 0.55rem;
    border-bottom: 1px solid var(--k-gray-200);
}

.invoice-meta div,
.invoice-strip div {
    display: flex;
    gap: 0.45rem;
    justify-content: space-between;
    align-items: baseline;
    font-size: 0.78rem;
}

.invoice-meta span,
.invoice-strip span,
.block-title,
.note-label {
    color: var(--k-gray-500);
    font-size: 0.72rem;
}

.invoice-meta strong,
.invoice-strip strong {
    color: var(--k-gray-800);
    font-weight: 600;
}

.invoice-meta div {
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    gap: 0.08rem;
}

.invoice-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.35rem 0.75rem;
    background: var(--k-gray-50);
    border-radius: 8px;
    padding: 0.5rem 0.65rem;
    margin-bottom: 0.7rem;
}

.invoice-strip div {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.08rem;
    min-width: 0;
}

.invoice-strip strong {
    text-align: left;
    line-height: 1.2;
}

.invoice-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.invoice-block {
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    padding: 0.65rem 0.75rem;
}

.block-title {
    font-weight: 700;
    margin-bottom: 0.35rem;
    color: var(--k-gray-600);
}

.block-body {
    font-size: 0.8rem;
    line-height: 1.45;
    color: var(--k-gray-700);
}

.mini-badge {
    display: inline-flex;
    align-items: center;
    margin-top: 0.4rem;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    background: var(--k-gray-100);
    color: var(--k-gray-600);
    font-size: 0.68rem;
}

.section-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--k-gray-800);
    margin: 0.9rem 0 0.45rem;
    padding-bottom: 0.3rem;
    border-bottom: 1px solid var(--k-gray-200);
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.76rem;
}

.invoice-table thead th {
    text-align: left;
    padding: 0.45rem 0.4rem;
    background: var(--k-gray-50);
    color: var(--k-gray-600);
    border-bottom: 1px solid var(--k-gray-200);
}

.invoice-table tbody td {
    padding: 0.38rem 0.4rem;
    border-bottom: 1px solid var(--k-gray-200);
    vertical-align: top;
}

.totals-wrap {
    display: flex;
    justify-content: flex-end;
    margin-top: 0.65rem;
}

.totals-table {
    width: 320px;
    border-collapse: collapse;
    font-size: 0.78rem;
}

.totals-table td {
    padding: 0.22rem 0;
}

.totals-table td:first-child {
    color: var(--k-gray-600);
}

.totals-table td:last-child {
    text-align: right;
    font-weight: 600;
    color: var(--k-gray-800);
}

.totals-table .grand-total td {
    padding-top: 0.4rem;
    border-top: 1px solid var(--k-gray-200);
    font-weight: 700;
}

.totals-table .grand-total td:last-child {
    color: var(--k-green);
    font-size: 0.95rem;
}

.notes-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.6rem;
}

.note-box {
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    padding: 0.65rem 0.75rem;
}

.note-body {
    font-size: 0.78rem;
    line-height: 1.45;
    color: var(--k-gray-700);
    white-space: pre-line;
}

.approval-section {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
    padding-top: 0.7rem;
    border-top: 1px solid var(--k-gray-200);
}

.approval-box {
    min-height: 112px;
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    padding: 0.6rem;
    display: flex;
    flex-direction: column;
}

.approval-title {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--k-gray-700);
}

.signature-space {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--k-gray-400);
    font-size: 0.68rem;
}

.signature-line {
    border-top: 1px solid var(--k-gray-300);
    margin-top: 0.4rem;
}

.signature-caption {
    text-align: center;
    margin-top: 0.25rem;
    font-size: 0.7rem;
    color: var(--k-gray-600);
    font-weight: 600;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    body {
        background: #fff !important;
    }

    .sidebar,
    .navbar,
    .top-bar,
    .breadcrumb,
    .page-title,
    .invoice-toolbar {
        display: none !important;
    }

    .main-content,
    .content-area {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .dms-card {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .invoice-shell {
        border: none !important;
    }

    .invoice-paper {
        border: none !important;
        padding: 0 !important;
    }

    .document-type {
        font-size: 1.5rem;
    }

    .invoice-grid,
    .notes-grid,
    .approval-section {
        gap: 0.45rem;
    }

    .invoice-block,
    .note-box,
    .approval-box,
    .invoice-strip {
        break-inside: avoid;
    }
}

@media screen and (max-width: 900px) {
    .invoice-head {
        flex-direction: column;
    }

    .company-block,
    .document-panel {
        max-width: 100%;
        width: 100%;
        text-align: left;
    }

    .document-number {
        align-items: flex-start;
    }

    .invoice-meta,
    .invoice-strip,
    .invoice-grid,
    .approval-section {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
