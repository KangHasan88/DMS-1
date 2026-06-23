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
        <div class="dms-toolbar-actions">
            @can('manage journal entries')
                @if($journalEntry->status === \App\Models\JournalEntry::STATUS_POSTED && !$journalEntry->source_type)
                    <button type="button" class="dms-btn dms-btn-outline" onclick="document.getElementById('void-journal-form').classList.toggle('d-none')">
                        <i class="bi bi-x-circle"></i> Void
                    </button>
                @endif
            @endcan
            <a href="{{ route('journal-entries.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if($journalEntry->status === \App\Models\JournalEntry::STATUS_VOID)
        <div class="dms-alert dms-alert-warning">
            <strong>Jurnal void.</strong> {{ $journalEntry->void_reason ?: 'Tanpa alasan.' }}
        </div>
    @endif

    @can('manage journal entries')
        @if($journalEntry->status === \App\Models\JournalEntry::STATUS_POSTED && !$journalEntry->source_type)
            <form id="void-journal-form" action="{{ route('journal-entries.void', $journalEntry) }}" method="POST" class="dms-form-section d-none" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
                @csrf
                <div class="dms-form-grid" style="align-items: end;">
                    <div class="form-group dms-form-span-2">
                        <label class="form-label">Alasan Void <span class="dms-required">*</span></label>
                        <input type="text" name="void_reason" class="form-control" maxlength="500" required placeholder="Contoh: Salah input akun atau nominal">
                        @error('void_reason') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <button type="submit" class="dms-btn dms-btn-primary" onclick="return confirm('Void jurnal ini dan buat jurnal reversal?')">
                            <i class="bi bi-check2-circle"></i> Proses Void
                        </button>
                    </div>
                </div>
            </form>
        @endif
    @endcan

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
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
        <div class="stat-card">
            <div class="stat-label">Status</div>
            <div class="stat-value" style="font-size: 1rem;">{{ $journalEntry->status_label }}</div>
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
