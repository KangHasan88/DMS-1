@extends('layouts.sidebar')

@section('page-title', 'Neraca Saldo')
@section('breadcrumb', 'Akuntansi / Neraca Saldo')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Neraca Saldo</h3>
            <p class="dms-section-subtitle">Ringkasan saldo debit-kredit seluruh akun dari jurnal yang sudah posted.</p>
        </div>
        <span class="dms-badge {{ $isBalanced ? 'status-paid' : 'status-cancelled' }}">
            {{ $isBalanced ? 'Balance' : 'Tidak Balance' }}
        </span>
    </div>

    <form action="{{ route('trial-balance.index') }}" method="GET" class="dms-toolbar">
        <div class="dms-toolbar-actions" style="width: 100%;">
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
            @if($canFilterBranches)
                <select name="company_branch_id" class="form-control">
                    <option value="">Semua Cabang</option>
                    <option value="global" {{ $selectedBranchId === 'global' ? 'selected' : '' }}>Global</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @endif
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-filter"></i> Terapkan
            </button>
        </div>
    </form>

    <div class="stats-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
        <div class="stat-card">
            <div class="stat-label">Periode</div>
            <div class="stat-value" style="font-size: 1rem;">{{ $dateFrom }}</div>
            <div class="dms-muted">s/d {{ $dateTo }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Saldo Awal</div>
            <div class="stat-value" style="font-size: 1rem;">D {{ number_format($totals['opening_debit'], 0, ',', '.') }}</div>
            <div class="dms-muted">K {{ number_format($totals['opening_credit'], 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Mutasi Periode</div>
            <div class="stat-value" style="font-size: 1rem;">D {{ number_format($totals['period_debit'], 0, ',', '.') }}</div>
            <div class="dms-muted">K {{ number_format($totals['period_credit'], 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Saldo Akhir</div>
            <div class="stat-value" style="font-size: 1rem;">D {{ number_format($totals['ending_debit'], 0, ',', '.') }}</div>
            <div class="dms-muted">K {{ number_format($totals['ending_credit'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th rowspan="2">Kode</th>
                    <th rowspan="2">Nama Akun</th>
                    <th rowspan="2">Tipe</th>
                    <th colspan="2">Saldo Awal</th>
                    <th colspan="2">Mutasi Periode</th>
                    <th colspan="2">Saldo Akhir</th>
                </tr>
                <tr>
                    <th>Debit</th>
                    <th>Kredit</th>
                    <th>Debit</th>
                    <th>Kredit</th>
                    <th>Debit</th>
                    <th>Kredit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><strong>{{ $row['account']->code }}</strong></td>
                        <td>{{ $row['account']->name }}</td>
                        <td>{{ $row['account']->type_label }}</td>
                        <td class="dms-money">Rp {{ number_format($row['opening_debit'], 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($row['opening_credit'], 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($row['period_debit'], 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($row['period_credit'], 0, ',', '.') }}</td>
                        <td class="dms-money"><strong>Rp {{ number_format($row['ending_debit'], 0, ',', '.') }}</strong></td>
                        <td class="dms-money"><strong>Rp {{ number_format($row['ending_credit'], 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="dms-empty">
                            <i class="bi bi-columns-gap"></i>
                            <p>Belum ada akun aktif untuk ditampilkan</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Total</th>
                    <th class="dms-money">Rp {{ number_format($totals['opening_debit'], 0, ',', '.') }}</th>
                    <th class="dms-money">Rp {{ number_format($totals['opening_credit'], 0, ',', '.') }}</th>
                    <th class="dms-money">Rp {{ number_format($totals['period_debit'], 0, ',', '.') }}</th>
                    <th class="dms-money">Rp {{ number_format($totals['period_credit'], 0, ',', '.') }}</th>
                    <th class="dms-money">Rp {{ number_format($totals['ending_debit'], 0, ',', '.') }}</th>
                    <th class="dms-money">Rp {{ number_format($totals['ending_credit'], 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
