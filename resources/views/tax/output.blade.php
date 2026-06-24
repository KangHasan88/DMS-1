@extends('layouts.sidebar')

@section('page-title', 'Pajak Keluaran')
@section('breadcrumb', 'Pajak / Pajak Keluaran')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Pajak Keluaran</h3>
            <p class="dms-section-subtitle">Pantau PPN keluaran dari invoice penjualan sebelum masuk proses Coretax.</p>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
        <div class="stat-card">
            <div class="stat-label">Dokumen Pajak</div>
            <div class="stat-value" style="font-size: 1rem;">{{ number_format($summary['count']) }}</div>
            <div class="dms-muted">AR invoice taxable</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total DPP</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($summary['tax_base_amount'], 0, ',', '.') }}</div>
            <div class="dms-muted">Dasar pengenaan pajak</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total PPN Keluaran</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($summary['ppn_amount'], 0, ',', '.') }}</div>
            <div class="dms-muted">Kredit pajak keluaran</div>
        </div>
    </div>

    <form action="{{ route('tax.output') }}" method="GET" class="dms-toolbar">
        <div class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari invoice, faktur pajak, customer...">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </div>
        <div class="dms-toolbar-actions">
            <select name="tax_status" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" {{ request('tax_status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @if($canFilterBranches)
                <select name="company_branch_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Semua Cabang</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </form>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Tanggal</th>
                    <th>DPP</th>
                    <th>PPN</th>
                    <th>Status Pajak</th>
                    <th>No. Faktur Pajak</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->customer?->name ?? $invoice->customerUser?->name ?? '-' }}</td>
                        <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td class="dms-money">Rp {{ number_format($invoice->tax_base_amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($invoice->ppn_amount, 0, ',', '.') }}</td>
                        <td><span class="dms-badge dms-badge-info">{{ $invoice->tax_status_label }}</span></td>
                        <td>{{ $invoice->tax_invoice_number ?: '-' }}</td>
                        <td>
                            <a href="{{ route('ar-invoices.show', $invoice) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail Invoice">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="dms-empty">
                            <i class="bi bi-receipt"></i>
                            <p>Belum ada pajak keluaran pada filter ini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->links() }}
</div>
@endsection
