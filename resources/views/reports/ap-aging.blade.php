@extends('layouts.sidebar')

@section('page-title', 'Umur Hutang')
@section('breadcrumb', 'Laporan / Umur Hutang')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Analisis Umur Hutang</h3>
            <p class="dms-section-subtitle">Pantau outstanding invoice supplier berdasarkan jatuh tempo dan prioritas pembayaran.</p>
        </div>
    </div>

    <form method="GET" class="dms-toolbar" style="align-items: end;">
        <div class="dms-search-form">
            <div>
                <label class="form-label">Tanggal Analisis</label>
                <input type="date" name="as_of_date" value="{{ $asOfDate->toDateString() }}" class="form-control">
            </div>
            <div>
                <label class="form-label">Pemasok</label>
                <select name="supplier_id" class="form-control">
                    <option value="">Semua Pemasok</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="dms-btn dms-btn-primary" type="submit">Filter</button>
        </div>
    </form>

    @include('reports._summary', ['items' => [
        ['label' => 'Open Invoice', 'value' => number_format($summary['open_invoices']), 'icon' => 'bi-journal-text'],
        ['label' => 'Total Outstanding', 'value' => 'Rp ' . number_format($summary['total_outstanding'], 0, ',', '.'), 'icon' => 'bi-cash-stack'],
        ['label' => 'Invoice Overdue', 'value' => number_format($summary['overdue_invoices']), 'icon' => 'bi-exclamation-triangle', 'bg' => '#fff1df', 'color' => 'var(--k-orange)'],
        ['label' => 'Outstanding Overdue', 'value' => 'Rp ' . number_format($summary['overdue_amount'], 0, ',', '.'), 'icon' => 'bi-hourglass-bottom', 'bg' => '#fee2e2', 'color' => '#dc2626'],
    ]])

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
        @foreach($bucketSummary as $bucket)
            <div style="border: 1px solid var(--k-border); border-radius: 8px; padding: 0.875rem; background: #fff;">
                <span class="dms-badge dms-badge-{{ $bucket['badge'] }}">{{ $bucket['label'] }}</span>
                <div style="font-weight: 800; margin-top: 0.65rem;">Rp {{ number_format($bucket['amount'], 0, ',', '.') }}</div>
                <div style="color: var(--k-gray-500); font-size: 0.78rem;">{{ number_format($bucket['count']) }} invoice</div>
            </div>
        @endforeach
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Pemasok</th>
                    <th>PO</th>
                    <th>Jatuh Tempo</th>
                    <th>Hari Terlambat</th>
                    <th>Bucket</th>
                    <th>Outstanding</th>
                    <th style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->supplier?->name ?? '-' }}</td>
                        <td>{{ $invoice->purchaseOrder?->po_number ?? '-' }}</td>
                        <td>{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $invoice->days_overdue > 0 ? number_format($invoice->days_overdue) . ' hari' : '-' }}</td>
                        <td><span class="dms-badge dms-badge-{{ $invoice->aging_badge }}">{{ $invoice->aging_bucket }}</span></td>
                        <td class="dms-money">Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('ap-invoices.show', $invoice) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="dms-empty">
                            <i class="bi bi-check2-circle"></i>
                            <p>Tidak ada hutang terbuka</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination" style="margin-top: 1rem;">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
