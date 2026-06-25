@extends('layouts.sidebar')

@section('page-title', 'Pajak Masukan')
@section('breadcrumb', 'Pajak / Pajak Masukan')

@section('content')
<style>
    .dms-inline-editor {
        position: relative;
    }

    .dms-inline-editor summary {
        list-style: none;
        cursor: pointer;
    }

    .dms-inline-editor summary::-webkit-details-marker {
        display: none;
    }

    .dms-inline-editor-panel {
        display: grid;
        gap: .5rem;
        min-width: 260px;
        margin-top: .5rem;
        padding: .8rem;
        border: 1px solid #dbe4f0;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 14px 32px rgba(6, 26, 61, .14);
    }

    .dms-inline-editor-panel label {
        margin: 0;
        color: #0b1f3f;
        font-size: .75rem;
        font-weight: 800;
    }

    .tax-page {
        display: grid;
        gap: 1rem;
    }

    .tax-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: .9rem;
        border-bottom: 1px solid #edf2f7;
    }

    .tax-page-title {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
    }

    .tax-page-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 38px;
        border-radius: 10px;
        color: #061a3d;
        background: #eaf2ff;
    }

    .tax-page-title h3 {
        margin: 0;
    }

    .tax-page-title p {
        margin: .25rem 0 0;
    }

    .tax-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .tax-actions form {
        margin: 0;
    }

    .tax-actions .dms-btn {
        min-height: 38px;
        white-space: nowrap;
    }

    .tax-readiness-note {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin: 0;
        padding: .7rem .85rem;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        color: #315076;
        background: #f7fbff;
        font-size: .9rem;
    }

    .tax-readiness-note i {
        color: #0b1f3f;
    }

    .tax-stats {
        margin: 0;
    }

    .tax-toolbar {
        margin: 0;
    }

    .tax-import-panel {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .75rem .85rem;
        border: 1px solid #dbe4f0;
        border-radius: 10px;
        background: #fbfdff;
    }

    .tax-import-copy {
        display: flex;
        align-items: center;
        gap: .55rem;
        color: #315076;
        font-size: .9rem;
    }

    .tax-import-form {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: 0;
    }

    .tax-import-form .form-control {
        max-width: 260px;
        min-height: 38px;
    }

    @media (max-width: 900px) {
        .tax-page-header {
            flex-direction: column;
        }

        .tax-actions {
            justify-content: flex-start;
            width: 100%;
        }
    }
</style>

<div class="dms-card tax-page">
    <div class="tax-page-header">
        <div class="tax-page-title">
            <div class="tax-page-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div>
                <h3 class="dms-section-title">Pajak Masukan</h3>
                <p class="dms-section-subtitle">Pantau faktur pajak supplier dan PPN masukan dari invoice pembelian.</p>
            </div>
        </div>
        <div class="tax-actions">
            <a href="{{ route('tax.input.export', request()->query()) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-download"></i> Export CSV
            </a>
            @can('create invoice')
                <form action="{{ route('tax.input.mark-exported', request()->query()) }}" method="POST" onsubmit="return confirm('Tandai pajak masukan sesuai filter saat ini sebagai exported?');">
                    @csrf
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-check2-circle"></i> Tandai Exported
                    </button>
                </form>
                <form action="{{ route('tax.input.mark-approved', request()->query()) }}" method="POST" onsubmit="return confirm('Tandai pajak masukan exported sesuai filter saat ini sebagai approved?');">
                    @csrf
                    <button type="submit" class="dms-btn dms-btn-outline">
                        <i class="bi bi-patch-check"></i> Tandai Approved
                    </button>
                </form>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">Periksa kembali input pajak yang wajib diisi.</div>
    @endif
    <div class="tax-readiness-note">
        <i class="bi bi-info-circle"></i>
        <span>Dokumen hanya bisa ditandai exported jika nomor dan tanggal faktur pajak supplier sudah lengkap.</span>
    </div>

    @can('create invoice')
        <div class="tax-import-panel">
            <div class="tax-import-copy">
                <i class="bi bi-upload"></i>
                <span>Import hasil Coretax CSV: invoice_number, tax_status, supplier_tax_invoice_number, supplier_tax_invoice_date, tax_error_message.</span>
            </div>
            <form action="{{ route('tax.input.import-results') }}" method="POST" enctype="multipart/form-data" class="tax-import-form">
                @csrf
                <input type="file" name="result_file" class="form-control" accept=".csv,text/csv,text/plain" required>
                <button type="submit" class="dms-btn dms-btn-outline"><i class="bi bi-upload"></i> Import</button>
            </form>
        </div>
    @endcan

    <div class="stats-grid tax-stats" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
        <div class="stat-card">
            <div class="stat-label">Dokumen Pajak</div>
            <div class="stat-value" style="font-size: 1rem;">{{ number_format($summary['count']) }}</div>
            <div class="dms-muted">AP invoice taxable</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total DPP</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($summary['tax_base_amount'], 0, ',', '.') }}</div>
            <div class="dms-muted">Dasar pengenaan pajak</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total PPN Masukan</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($summary['ppn_amount'], 0, ',', '.') }}</div>
            <div class="dms-muted">Pajak masukan claimable</div>
        </div>
    </div>

    <form action="{{ route('tax.input') }}" method="GET" class="dms-toolbar tax-toolbar">
        <div class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari AP invoice, faktur pajak, supplier...">
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
                    <th>AP Invoice</th>
                    <th>Supplier</th>
                    <th>Tanggal</th>
                    <th>DPP</th>
                    <th>PPN</th>
                    <th>Status Pajak</th>
                    <th>No. Faktur Supplier</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        <td>{{ $invoice->supplier?->name ?? '-' }}</td>
                        <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td class="dms-money">Rp {{ number_format($invoice->tax_base_amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($invoice->ppn_amount, 0, ',', '.') }}</td>
                        <td><span class="dms-badge dms-badge-info">{{ $invoice->tax_status_label }}</span></td>
                        <td>{{ $invoice->supplier_tax_invoice_number ?: '-' }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('ap-invoices.show', $invoice) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail Invoice">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('create invoice')
                                    <details class="dms-inline-editor">
                                        <summary class="dms-btn dms-btn-outline dms-btn-sm">Update</summary>
                                        <form action="{{ route('tax.input.update', $invoice) }}" method="POST" class="dms-inline-editor-panel">
                                            @csrf
                                            @method('PUT')
                                            <label>Status Pajak</label>
                                            <select name="tax_status" class="form-control">
                                                @foreach($statuses as $key => $label)
                                                    <option value="{{ $key }}" {{ old('tax_status', $invoice->tax_status) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <label>No. Faktur Supplier</label>
                                            <input type="text" name="supplier_tax_invoice_number" value="{{ old('supplier_tax_invoice_number', $invoice->supplier_tax_invoice_number) }}" class="form-control" placeholder="010.000-26.00000001">
                                            <label>Tanggal Faktur Supplier</label>
                                            <input type="date" name="supplier_tax_invoice_date" value="{{ old('supplier_tax_invoice_date', optional($invoice->supplier_tax_invoice_date)->format('Y-m-d')) }}" class="form-control">
                                            <label>Catatan Error</label>
                                            <textarea name="tax_error_message" class="form-control" rows="2" placeholder="Isi jika Coretax reject">{{ old('tax_error_message', $invoice->tax_error_message) }}</textarea>
                                            <button type="submit" class="dms-btn dms-btn-primary dms-btn-sm w-100">Simpan Pajak</button>
                                        </form>
                                    </details>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="dms-empty">
                            <i class="bi bi-receipt"></i>
                            <p>Belum ada pajak masukan pada filter ini</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->links() }}
</div>
@endsection
