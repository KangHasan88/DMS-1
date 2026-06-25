@extends('layouts.sidebar')

@section('page-title', 'Rekap Pajak')
@section('breadcrumb', 'Pajak / Rekap Pajak')

@section('content')
<style>
    .tax-summary-page {
        display: grid;
        gap: 1rem;
    }

    .tax-summary-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: .9rem;
        border-bottom: 1px solid #edf2f7;
    }

    .tax-summary-title {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
    }

    .tax-summary-icon {
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

    .tax-summary-title h3 {
        margin: 0;
    }

    .tax-summary-title p {
        margin: .25rem 0 0;
    }

    .tax-period-form {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: .55rem;
        margin: 0;
    }

    .tax-period-form .form-control {
        min-height: 38px;
        min-width: 180px;
    }

    .tax-net-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: center;
        padding: 1rem;
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        background: linear-gradient(135deg, #f8fbff 0%, #fff 100%);
    }

    .tax-net-label {
        color: #64748b;
        font-size: .75rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .tax-net-value {
        margin-top: .25rem;
        color: #061a3d;
        font-size: 2rem;
        font-weight: 900;
        line-height: 1;
    }

    .tax-net-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 120px;
        padding: .55rem .8rem;
        border-radius: 999px;
        color: #061a3d;
        background: #eaf2ff;
        font-weight: 900;
    }

    .tax-net-badge.is-payable {
        color: #7c2d12;
        background: #ffedd5;
    }

    .tax-net-badge.is-overpaid {
        color: #065f46;
        background: #d1fae5;
    }

    .tax-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
    }

    .tax-summary-card {
        padding: .9rem;
        border: 1px solid #dbe4f0;
        border-radius: 10px;
        background: #fff;
    }

    .tax-summary-card-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        color: #315076;
        font-size: .8rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .tax-summary-card-value {
        margin-top: .55rem;
        color: #061a3d;
        font-size: 1.2rem;
        font-weight: 900;
    }

    .tax-summary-card-note {
        margin-top: .25rem;
        color: #7890ad;
        font-size: .85rem;
    }

    .tax-readiness-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .75rem;
    }

    .tax-readiness-card {
        min-height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .8rem .9rem;
        border: 1px solid #dbe4f0;
        border-radius: 10px;
        background: #fbfdff;
    }

    .tax-readiness-label {
        display: block;
        color: #64748b;
        font-size: .72rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .tax-readiness-help {
        display: block;
        margin-top: .18rem;
        color: #7890ad;
        font-size: .78rem;
    }

    .tax-readiness-count {
        color: #061a3d;
        font-size: 1.35rem;
        font-weight: 900;
    }

    .tax-summary-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    @media (max-width: 1100px) {
        .tax-summary-grid,
        .tax-readiness-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .tax-summary-header,
        .tax-net-card {
            grid-template-columns: 1fr;
            flex-direction: column;
        }

        .tax-period-form {
            justify-content: flex-start;
            width: 100%;
        }

        .tax-summary-grid,
        .tax-readiness-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dms-card tax-summary-page">
    <div class="tax-summary-header">
        <div class="tax-summary-title">
            <div class="tax-summary-icon"><i class="bi bi-calculator"></i></div>
            <div>
                <h3 class="dms-section-title">Rekap Pajak</h3>
                <p class="dms-section-subtitle">Pantau posisi PPN keluaran, PPN masukan, dan estimasi kurang/lebih bayar per periode.</p>
            </div>
        </div>
        <form action="{{ route('tax.summary') }}" method="GET" class="tax-period-form">
            <input type="month" name="period" value="{{ request('period', $summary['period']) }}" class="form-control">
            @if($canFilterBranches)
                <select name="company_branch_id" class="form-control">
                    <option value="">Semua Cabang</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif
            <button type="submit" class="dms-btn dms-btn-primary"><i class="bi bi-funnel"></i> Terapkan</button>
            <a href="{{ route('tax.summary.export', request()->query()) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </form>
    </div>

    <div class="tax-net-card">
        <div>
            <span class="tax-net-label">Estimasi posisi PPN {{ $summary['period_label'] }}</span>
            <div class="tax-net-value">Rp {{ number_format(abs($summary['net_ppn']), 0, ',', '.') }}</div>
            <div class="dms-muted">PPN Keluaran dikurangi PPN Masukan yang dapat dikreditkan.</div>
        </div>
        <span class="tax-net-badge {{ $summary['net_ppn'] > 0 ? 'is-payable' : ($summary['net_ppn'] < 0 ? 'is-overpaid' : '') }}">
            {{ $summary['net_label'] }}
        </span>
    </div>

    <div class="tax-summary-grid">
        <div class="tax-summary-card">
            <div class="tax-summary-card-title"><i class="bi bi-receipt"></i> Pajak Keluaran</div>
            <div class="tax-summary-card-value">Rp {{ number_format($summary['output_ppn'], 0, ',', '.') }}</div>
            <div class="tax-summary-card-note">{{ number_format($summary['output_count']) }} dokumen, DPP Rp {{ number_format($summary['output_dpp'], 0, ',', '.') }}</div>
        </div>
        <div class="tax-summary-card">
            <div class="tax-summary-card-title"><i class="bi bi-receipt-cutoff"></i> Pajak Masukan</div>
            <div class="tax-summary-card-value">Rp {{ number_format($summary['input_ppn'], 0, ',', '.') }}</div>
            <div class="tax-summary-card-note">{{ number_format($summary['input_count']) }} dokumen, DPP kredit Rp {{ number_format($summary['input_dpp'], 0, ',', '.') }}</div>
        </div>
        <div class="tax-summary-card">
            <div class="tax-summary-card-title"><i class="bi bi-calendar2-check"></i> Periode</div>
            <div class="tax-summary-card-value">{{ $summary['period_label'] }}</div>
            <div class="tax-summary-card-note">Berdasarkan tanggal faktur pajak, fallback ke tanggal invoice jika faktur belum ada.</div>
        </div>
    </div>

    <div class="tax-readiness-grid">
        <div class="tax-readiness-card">
            <div>
                <span class="tax-readiness-label">Keluaran Siap</span>
                <span class="tax-readiness-help">Nomor & tanggal lengkap</span>
            </div>
            <strong class="tax-readiness-count">{{ number_format($summary['output_ready']) }}</strong>
        </div>
        <div class="tax-readiness-card">
            <div>
                <span class="tax-readiness-label">Keluaran Belum Lengkap</span>
                <span class="tax-readiness-help">Perlu koreksi data</span>
            </div>
            <strong class="tax-readiness-count">{{ number_format($summary['output_incomplete']) }}</strong>
        </div>
        <div class="tax-readiness-card">
            <div>
                <span class="tax-readiness-label">Masukan Siap</span>
                <span class="tax-readiness-help">Siap dikreditkan</span>
            </div>
            <strong class="tax-readiness-count">{{ number_format($summary['input_ready']) }}</strong>
        </div>
        <div class="tax-readiness-card">
            <div>
                <span class="tax-readiness-label">Masukan Belum Lengkap</span>
                <span class="tax-readiness-help">Faktur supplier belum lengkap</span>
            </div>
            <strong class="tax-readiness-count">{{ number_format($summary['input_incomplete']) }}</strong>
        </div>
    </div>

    <div class="tax-summary-actions">
        <a href="{{ route('tax.output', request()->only('company_branch_id')) }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-receipt"></i> Detail Pajak Keluaran
        </a>
        <a href="{{ route('tax.input', request()->only('company_branch_id')) }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-receipt-cutoff"></i> Detail Pajak Masukan
        </a>
    </div>
</div>
@endsection
