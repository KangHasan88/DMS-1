@extends('layouts.sidebar')

@section('page-title', 'Kas & Bank')
@section('breadcrumb', 'Akuntansi / Kas & Bank')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Kas & Bank</h3>
            <p class="dms-section-subtitle">Pantau saldo dan mutasi akun kas/bank dari jurnal yang sudah posted.</p>
        </div>
        <a href="{{ route('chart-accounts.index', ['account_type' => \App\Models\ChartAccount::TYPE_ASSET]) }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-list-check"></i> Kelola Akun
        </a>
    </div>

    <form action="{{ route('cash-bank.index') }}" method="GET" class="dms-toolbar">
        <div class="dms-toolbar-actions" style="width: 100%;">
            <select name="chart_account_id" class="form-control" required>
                @forelse($cashAccounts as $account)
                    <option value="{{ $account->id }}" {{ $selectedAccount && $selectedAccount->id === $account->id ? 'selected' : '' }}>
                        {{ $account->code }} - {{ $account->name }}
                    </option>
                @empty
                    <option value="">Belum ada akun kas/bank aktif</option>
                @endforelse
            </select>
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
            <div class="stat-label">Total Saldo Awal</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($totalOpeningBalance, 0, ',', '.') }}</div>
            <div class="dms-muted">{{ $dateFrom }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Kas/Bank Masuk</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($totalDebit, 0, ',', '.') }}</div>
            <div class="dms-muted">Total debit periode</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Kas/Bank Keluar</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($totalCredit, 0, ',', '.') }}</div>
            <div class="dms-muted">Total kredit periode</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Saldo Akhir</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($totalEndingBalance, 0, ',', '.') }}</div>
            <div class="dms-muted">{{ $dateTo }}</div>
        </div>
    </div>

    <div class="dms-table-wrap" style="margin-top: 1rem;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Akun</th>
                    <th>Cabang</th>
                    <th>Saldo Awal</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accountSummaries as $summary)
                    <tr>
                        <td>
                            <a href="{{ route('cash-bank.index', array_merge(request()->except('chart_account_id'), ['chart_account_id' => $summary['account']->id])) }}" style="color: var(--k-blue); font-weight: 700; text-decoration: none;">
                                {{ $summary['account']->code }} - {{ $summary['account']->name }}
                            </a>
                        </td>
                        <td>{{ $summary['account']->companyBranch?->name ?? 'Global' }}</td>
                        <td class="dms-money">Rp {{ number_format($summary['opening_balance'], 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($summary['period_debit'], 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($summary['period_credit'], 0, ',', '.') }}</td>
                        <td class="dms-money"><strong>Rp {{ number_format($summary['ending_balance'], 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="dms-empty">
                            <i class="bi bi-bank"></i>
                            <p>Belum ada akun kas/bank aktif. Tandai akun aset sebagai akun kas/bank di Daftar Akun.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="dms-card" style="margin-top: 1rem;">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Mutasi {{ $selectedAccount?->code ? $selectedAccount->code . ' - ' . $selectedAccount->name : 'Kas & Bank' }}</h3>
            <p class="dms-section-subtitle">Saldo awal Rp {{ number_format($openingBalance, 0, ',', '.') }} sampai saldo akhir Rp {{ number_format($runningBalance, 0, ',', '.') }}.</p>
        </div>
        @if($selectedAccount)
            <a href="{{ route('general-ledger.index', [
                'chart_account_id' => $selectedAccount->id,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'company_branch_id' => $selectedBranchId,
            ]) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-journal-bookmark"></i> Buku Besar
            </a>
        @endif
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. Jurnal</th>
                    <th>Sumber</th>
                    <th>Keterangan</th>
                    <th>Cabang</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @if($selectedAccount)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($dateFrom)->subDay()->format('d M Y') }}</td>
                        <td>-</td>
                        <td>-</td>
                        <td><strong>Saldo Awal</strong></td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td class="dms-money">Rp {{ number_format($openingBalance, 0, ',', '.') }}</td>
                    </tr>
                @endif
                @forelse($entries as $line)
                    <tr>
                        <td>{{ $line->journalEntry?->journal_date?->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('journal-entries.show', $line->journalEntry) }}" style="color: var(--k-blue); font-weight: 700; text-decoration: none;">
                                {{ $line->journalEntry?->journal_number }}
                            </a>
                        </td>
                        <td>
                            @if($line->journalEntry?->source_document_url)
                                <a href="{{ $line->journalEntry->source_document_url }}" style="color: var(--k-blue); font-weight: 700; text-decoration: none;">
                                    {{ $line->journalEntry->source_document_label }}
                                    <span style="display: block; color: var(--k-gray-500); font-size: 0.72rem; font-weight: 600;">{{ $line->journalEntry->source_document_number }}</span>
                                </a>
                            @else
                                {{ $line->journalEntry?->source_document_label ?? '-' }}
                            @endif
                        </td>
                        <td>{{ $line->description ?: $line->journalEntry?->description }}</td>
                        <td>{{ $line->journalEntry?->companyBranch?->name ?? 'Global' }}</td>
                        <td class="dms-money">Rp {{ number_format($line->debit_amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($line->credit_amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($line->running_balance, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="dms-empty">
                            <i class="bi bi-bank"></i>
                            <p>{{ $selectedAccount ? 'Belum ada mutasi kas/bank pada periode ini' : 'Belum ada akun kas/bank untuk ditampilkan' }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
