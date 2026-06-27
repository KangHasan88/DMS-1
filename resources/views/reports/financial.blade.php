@extends('layouts.sidebar')

@section('page-title', 'Laporan Keuangan')
@section('breadcrumb', 'Laporan / Laporan Keuangan')

@section('content')
@php
    $formatMoney = fn ($value) => 'Rp ' . number_format((int) $value, 0, ',', '.');
    $ledgerUrl = function ($row, $from = null) use ($startDate, $endDate, $selectedBranchId) {
        if (empty($row['id'])) {
            return null;
        }

        return route('general-ledger.index', array_filter([
            'chart_account_id' => $row['id'],
            'date_from' => $from ?: $startDate->toDateString(),
            'date_to' => $endDate->toDateString(),
            'company_branch_id' => $selectedBranchId,
        ], fn ($value) => $value !== null && $value !== ''));
    };
    $accountLabel = function ($row, $from = null) use ($ledgerUrl) {
        $label = $row['code'] . ' - ' . $row['name'];
        $url = $ledgerUrl($row, $from);

        return $url
            ? '<a href="' . e($url) . '" style="color: var(--k-blue); font-weight: 700; text-decoration: none;">' . e($label) . '</a>'
            : e($label);
    };
@endphp

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Laporan Keuangan</h3>
            <p class="dms-section-subtitle">Ringkasan laba rugi dan posisi keuangan berdasarkan jurnal yang sudah posted.</p>
        </div>
        <span class="dms-badge {{ $balanceSheet['is_balanced'] ? 'status-paid' : 'status-cancelled' }}">
            {{ $balanceSheet['is_balanced'] ? 'Balance' : 'Tidak Balance' }}
        </span>
    </div>

    <form method="GET" class="dms-toolbar" style="align-items: stretch;">
        <div style="display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 0.75rem; width: 100%; align-items: end;">
            <div style="grid-column: span 2; min-width: 0;">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="form-control">
            </div>
            <div style="grid-column: span 2; min-width: 0;">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="form-control">
            </div>
            @if($canFilterBranches)
                <div style="grid-column: span 3; min-width: 0;">
                    <label class="form-label">Cabang</label>
                    <select name="company_branch_id" class="form-control">
                        <option value="">Semua Cabang</option>
                        <option value="global" {{ $selectedBranchId === 'global' ? 'selected' : '' }}>Global</option>
                        @foreach($companyBranches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div style="grid-column: span {{ $canFilterBranches ? 5 : 8 }}; min-width: 0; display: flex; gap: 0.65rem; flex-wrap: wrap; justify-content: flex-end;">
                <button class="dms-btn dms-btn-primary" type="submit" style="min-width: 96px; justify-content: center;">
                    <i class="bi bi-filter"></i> Terapkan
                </button>
                <a class="dms-btn dms-btn-outline" style="min-width: 126px; justify-content: center;" href="{{ route('reports.export', array_merge(['type' => 'financial'], request()->only(['start_date', 'end_date', 'company_branch_id']))) }}">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
        </div>
    </form>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
        <div class="stat-card">
            <div class="stat-label">Pendapatan</div>
            <div class="stat-value" style="font-size: 1rem;">{{ $formatMoney($profitLoss['revenue_total']) }}</div>
            <div class="dms-muted">{{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Laba Kotor</div>
            <div class="stat-value" style="font-size: 1rem;">{{ $formatMoney($profitLoss['gross_profit']) }}</div>
            <div class="dms-muted">Pendapatan - HPP</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Laba Bersih</div>
            <div class="stat-value" style="font-size: 1rem;">{{ $formatMoney($profitLoss['net_income']) }}</div>
            <div class="dms-muted">Setelah beban periode</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Aset</div>
            <div class="stat-value" style="font-size: 1rem;">{{ $formatMoney($balanceSheet['total_assets']) }}</div>
            <div class="dms-muted">Per {{ $endDate->format('d M Y') }}</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1rem; align-items: start;">
        <div style="background: #fff; border: 1px solid var(--k-border); border-radius: 8px; padding: 1rem; min-width: 0;">
            <div class="dms-section-header">
                <div>
                    <h4 class="dms-section-title" style="font-size: 1.05rem;">Laba Rugi</h4>
                    <p class="dms-section-subtitle">Periode {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}</p>
                </div>
            </div>
            <div class="dms-table-wrap">
            <table class="dms-table" style="min-width: 520px;">
                <tbody>
                    <tr><th colspan="2">Pendapatan</th></tr>
                    @forelse($profitLoss['revenue'] as $row)
                        <tr>
                            <td>{!! $accountLabel($row) !!}</td>
                            <td class="dms-money" style="text-align: right;">{{ $formatMoney($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="dms-muted">Belum ada pendapatan posted.</td></tr>
                    @endforelse
                    <tr>
                        <th>Total Pendapatan</th>
                        <th class="dms-money" style="text-align: right;">{{ $formatMoney($profitLoss['revenue_total']) }}</th>
                    </tr>
                    <tr><th colspan="2">Harga Pokok Penjualan</th></tr>
                    @forelse($profitLoss['cogs'] as $row)
                        <tr>
                            <td>{!! $accountLabel($row) !!}</td>
                            <td class="dms-money" style="text-align: right;">{{ $formatMoney($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="dms-muted">Belum ada HPP posted.</td></tr>
                    @endforelse
                    <tr>
                        <th>Laba Kotor</th>
                        <th class="dms-money" style="text-align: right;">{{ $formatMoney($profitLoss['gross_profit']) }}</th>
                    </tr>
                    <tr><th colspan="2">Beban Operasional</th></tr>
                    @forelse($profitLoss['expenses'] as $row)
                        <tr>
                            <td>{!! $accountLabel($row) !!}</td>
                            <td class="dms-money" style="text-align: right;">{{ $formatMoney($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="dms-muted">Belum ada beban posted.</td></tr>
                    @endforelse
                    <tr>
                        <th>Laba Bersih</th>
                        <th class="dms-money" style="text-align: right;">{{ $formatMoney($profitLoss['net_income']) }}</th>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

        <div style="background: #fff; border: 1px solid var(--k-border); border-radius: 8px; padding: 1rem; min-width: 0;">
            <div class="dms-section-header">
                <div>
                    <h4 class="dms-section-title" style="font-size: 1.05rem;">Neraca</h4>
                    <p class="dms-section-subtitle">Posisi keuangan per {{ $endDate->format('d M Y') }}</p>
                </div>
            </div>
            <div class="dms-table-wrap">
            <table class="dms-table" style="min-width: 520px;">
                <tbody>
                    <tr><th colspan="2">Aset</th></tr>
                    @forelse($balanceSheet['assets'] as $row)
                        <tr>
                            <td>{!! $accountLabel($row, null) !!}</td>
                            <td class="dms-money" style="text-align: right;">{{ $formatMoney($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="dms-muted">Belum ada aset posted.</td></tr>
                    @endforelse
                    <tr>
                        <th>Total Aset</th>
                        <th class="dms-money" style="text-align: right;">{{ $formatMoney($balanceSheet['total_assets']) }}</th>
                    </tr>
                    <tr><th colspan="2">Kewajiban</th></tr>
                    @forelse($balanceSheet['liabilities'] as $row)
                        <tr>
                            <td>{!! $accountLabel($row, null) !!}</td>
                            <td class="dms-money" style="text-align: right;">{{ $formatMoney($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="dms-muted">Belum ada kewajiban posted.</td></tr>
                    @endforelse
                    <tr>
                        <th>Total Kewajiban</th>
                        <th class="dms-money" style="text-align: right;">{{ $formatMoney($balanceSheet['total_liabilities']) }}</th>
                    </tr>
                    <tr><th colspan="2">Ekuitas</th></tr>
                    @forelse($balanceSheet['equity'] as $row)
                        <tr>
                            <td>{!! $accountLabel($row, null) !!}</td>
                            <td class="dms-money" style="text-align: right;">{{ $formatMoney($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="dms-muted">Belum ada ekuitas posted.</td></tr>
                    @endforelse
                    <tr>
                        <th>Total Kewajiban + Ekuitas</th>
                        <th class="dms-money" style="text-align: right;">{{ $formatMoney($balanceSheet['total_liabilities_equity']) }}</th>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection
