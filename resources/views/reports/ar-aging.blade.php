@extends('layouts.sidebar')

@section('page-title', 'Umur Piutang')
@section('breadcrumb', 'Laporan / Umur Piutang')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Analisis Umur Piutang</h3>
            <p class="dms-section-subtitle">Pantau outstanding invoice berdasarkan jatuh tempo dan prioritas penagihan.</p>
        </div>
    </div>

    <form method="GET" class="dms-toolbar" style="align-items: end;">
        <div class="dms-search-form">
            <div>
                <label class="form-label">Tanggal Analisis</label>
                <input type="date" name="as_of_date" value="{{ $asOfDate->toDateString() }}" class="form-control">
            </div>
            @if($canFilterBranches)
                <div>
                    <label class="form-label">Cabang</label>
                    <select name="company_branch_id" class="form-control">
                        <option value="">Semua Cabang</option>
                        @foreach($companyBranches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button class="dms-btn dms-btn-primary" type="submit">Filter</button>
            <a class="dms-btn dms-btn-outline" href="{{ route('reports.export', array_merge(['type' => 'ar-aging'], request()->only(['as_of_date', 'company_branch_id']))) }}">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </form>

    @include('reports._summary', ['items' => [
        ['label' => 'Open Invoice', 'value' => number_format($summary['open_invoices']), 'icon' => 'bi-receipt'],
        ['label' => 'Total Outstanding', 'value' => 'Rp ' . number_format($summary['total_outstanding'], 0, ',', '.'), 'icon' => 'bi-cash-stack'],
        ['label' => 'Invoice Overdue', 'value' => number_format($summary['overdue_invoices']), 'icon' => 'bi-exclamation-triangle', 'bg' => '#fff1df', 'color' => 'var(--k-orange)'],
        ['label' => 'Outstanding Overdue', 'value' => 'Rp ' . number_format($summary['overdue_amount'], 0, ',', '.'), 'icon' => 'bi-hourglass-split', 'bg' => '#fee2e2', 'color' => '#dc2626'],
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
        <table class="dms-table" style="min-width: 1120px;">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Pelanggan</th>
                    <th>Order</th>
                    <th style="text-align: right;">Jatuh Tempo</th>
                    <th style="text-align: right;">Hari Terlambat</th>
                    <th style="text-align: center;">Bucket</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: right;">Terbayar</th>
                    <th style="text-align: right;">Credit Note</th>
                    <th style="text-align: right;">Outstanding</th>
                    <th style="width: 110px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->customer?->name ?? $invoice->customerUser?->name ?? '-' }}</td>
                        <td>{{ $invoice->order?->order_number ?? '-' }}</td>
                        <td style="text-align: right; white-space: nowrap;">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                        <td style="text-align: right; white-space: nowrap;">{{ $invoice->days_overdue > 0 ? number_format($invoice->days_overdue) . ' hari' : '-' }}</td>
                        <td style="text-align: center;"><span class="dms-badge dms-badge-{{ $invoice->aging_badge }}">{{ $invoice->aging_bucket }}</span></td>
                        <td class="dms-money" style="text-align: right;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        <td class="dms-money" style="text-align: right;">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                        <td class="dms-money" style="text-align: right;">Rp {{ number_format($invoice->credit_note_amount, 0, ',', '.') }}</td>
                        <td class="dms-money" style="text-align: right;">Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</td>
                        <td style="text-align: center;">
                            <a href="{{ route('ar-invoices.show', $invoice) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="dms-empty">
                            <i class="bi bi-check2-circle"></i>
                            <p>Tidak ada piutang terbuka</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination" style="margin-top: 1rem;">
        <div class="dms-pagination-summary">
            Menampilkan {{ $invoices->firstItem() ?? 0 }} - {{ $invoices->lastItem() ?? 0 }} dari {{ $invoices->total() }} invoice
        </div>
        {{ $invoices->links() }}
    </div>
</div>
@endsection
