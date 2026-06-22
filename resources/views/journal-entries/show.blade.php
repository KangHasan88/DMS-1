@extends('layouts.sidebar')

@section('page-title', 'Detail Jurnal')
@section('breadcrumb', 'Akuntansi / Jurnal Umum / Detail')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $journalEntry->journal_number }}</h3>
            <p class="dms-section-subtitle">{{ $journalEntry->description }}</p>
        </div>
        <a href="{{ route('journal-entries.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
        <div class="stat-card">
            <div class="stat-label">Tanggal</div>
            <div class="stat-value" style="font-size: 1rem;">{{ $journalEntry->journal_date?->format('d M Y') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Cabang</div>
            <div class="stat-value" style="font-size: 1rem;">{{ $journalEntry->companyBranch?->name ?? 'Global' }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Debit</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($journalEntry->debit_total, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Kredit</div>
            <div class="stat-value" style="font-size: 1rem;">Rp {{ number_format($journalEntry->credit_total, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th>Keterangan</th>
                    <th>Debit</th>
                    <th>Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($journalEntry->lines as $line)
                    <tr>
                        <td><strong>{{ $line->account?->code }}</strong></td>
                        <td>{{ $line->account?->name }}</td>
                        <td>{{ $line->description ?: '-' }}</td>
                        <td class="dms-money">Rp {{ number_format($line->debit_amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($line->credit_amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Total</th>
                    <th>Rp {{ number_format($journalEntry->debit_total, 0, ',', '.') }}</th>
                    <th>Rp {{ number_format($journalEntry->credit_total, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
