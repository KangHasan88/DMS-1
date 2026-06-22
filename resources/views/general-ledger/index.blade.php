@extends('layouts.sidebar')

@section('page-title', 'Buku Besar')
@section('breadcrumb', 'Akuntansi / Buku Besar')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Buku Besar</h3>
            <p class="dms-section-subtitle">Lihat mutasi debit-kredit dan saldo berjalan per akun dari jurnal yang sudah posted.</p>
        </div>
    </div>

    <form action="{{ route('general-ledger.index') }}" method="GET" class="dms-toolbar">
        <div class="dms-toolbar-actions" style="width: 100%;">
            <select name="chart_account_id" class="form-control" required>
                @forelse($accounts as $account)
                    <option value="{{ $account->id }}" {{ $selectedAccount && $selectedAccount->id === $account->id ? 'selected' : '' }}>
                        {{ $account->code }} - {{ $account->name }}
                    </option>
                @empty
                    <option value="">Belum ada akun aktif</option>
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

    @if($selectedAccount)
        @php
            $periodDebit = $entries->sum('debit_amount');
            $periodCredit = $entries->sum('credit_amount');
        @endphp
        <div class="stats-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
            <div class="stat-card">
                <div class="stat-label">Akun</div>
                <div class="stat-value" style="font-size: 1rem;">{{ $selectedAccount->code }}</div>
                <div class="dms-muted">{{ $selectedAccount->name }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Saldo Awal</div>
                <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($openingBalance, 0, ',', '.') }}</div>
                <div class="dms-muted">Saldo normal {{ $selectedAccount->normal_balance_label }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Mutasi Periode</div>
                <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($periodDebit - $periodCredit, 0, ',', '.') }}</div>
                <div class="dms-muted">D {{ number_format($periodDebit, 0, ',', '.') }} | K {{ number_format($periodCredit, 0, ',', '.') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Saldo Akhir</div>
                <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($runningBalance, 0, ',', '.') }}</div>
                <div class="dms-muted">{{ $dateFrom }} s/d {{ $dateTo }}</div>
            </div>
        </div>
    @endif

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. Jurnal</th>
                    <th>Keterangan</th>
                    <th>Cabang</th>
                    <th>Debit</th>
                    <th>Kredit</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                @if($selectedAccount)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($dateFrom)->subDay()->format('d M Y') }}</td>
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
                        <td>{{ $line->description ?: $line->journalEntry?->description }}</td>
                        <td>{{ $line->journalEntry?->companyBranch?->name ?? 'Global' }}</td>
                        <td class="dms-money">Rp {{ number_format($line->debit_amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($line->credit_amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($line->running_balance, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="dms-empty">
                            <i class="bi bi-journal-bookmark"></i>
                            <p>{{ $selectedAccount ? 'Belum ada mutasi buku besar pada periode ini' : 'Belum ada akun aktif untuk ditampilkan' }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
