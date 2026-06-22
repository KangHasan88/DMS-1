@extends('layouts.sidebar')

@section('page-title', 'Jurnal Umum')
@section('breadcrumb', 'Akuntansi / Jurnal Umum')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Jurnal Umum</h3>
            <p class="dms-section-subtitle">Catat transaksi debit-kredit yang sudah balance sebelum masuk buku besar.</p>
        </div>
        @can('manage journal entries')
            <button type="button" class="dms-btn dms-btn-primary" onclick="document.getElementById('journal-form').scrollIntoView({ behavior: 'smooth', block: 'start' })">
                <i class="bi bi-plus-circle"></i> Tambah Jurnal
            </button>
        @endcan
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('journal-entries.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari nomor atau keterangan jurnal..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('journal-entries.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('journal-entries.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @if($canFilterBranches)
                <select onchange="window.location.href = this.value" class="form-control">
                    <option value="{{ route('journal-entries.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => null])) }}">Semua Cabang</option>
                    <option value="{{ route('journal-entries.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => 'global'])) }}" {{ request('company_branch_id') === 'global' ? 'selected' : '' }}>Global</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ route('journal-entries.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    @can('manage journal entries')
        <form id="journal-form" action="{{ route('journal-entries.store') }}" method="POST" class="dms-form-section" style="padding: 1rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
            @csrf
            <h4 class="dms-form-section-title"><i class="bi bi-journal-check"></i> Posting Jurnal Manual</h4>
            @error('lines') <span class="dms-error" style="display: block; margin-bottom: 0.75rem;">{{ $message }}</span> @enderror
            <div class="dms-form-grid">
                <div class="form-group">
                    <label class="form-label">Tanggal Jurnal <span class="dms-required">*</span></label>
                    <input type="date" name="journal_date" value="{{ old('journal_date', now()->toDateString()) }}" class="form-control" required>
                    @error('journal_date') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                @if($canFilterBranches)
                    <div class="form-group">
                        <label class="form-label">Cabang</label>
                        <select name="company_branch_id" class="form-control">
                            <option value="">Global</option>
                            @foreach($companyBranches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_branch_id') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                @endif
                <div class="form-group dms-form-span-2">
                    <label class="form-label">Keterangan <span class="dms-required">*</span></label>
                    <textarea name="description" class="form-control" required placeholder="Contoh: Penyesuaian saldo awal kas">{{ old('description') }}</textarea>
                    @error('description') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="dms-table-wrap">
                <table class="dms-table">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th>Keterangan Baris</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 6; $i++)
                            <tr>
                                <td>
                                    <select name="lines[{{ $i }}][chart_account_id]" class="form-control">
                                        <option value="">Pilih akun</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ (string) old("lines.$i.chart_account_id") === (string) $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("lines.$i.chart_account_id") <span class="dms-error">{{ $message }}</span> @enderror
                                </td>
                                <td>
                                    <input type="text" name="lines[{{ $i }}][description]" value="{{ old("lines.$i.description") }}" class="form-control" placeholder="Opsional">
                                </td>
                                <td>
                                    <input type="number" min="0" name="lines[{{ $i }}][debit_amount]" value="{{ old("lines.$i.debit_amount") }}" class="form-control" placeholder="0">
                                    @error("lines.$i.debit_amount") <span class="dms-error">{{ $message }}</span> @enderror
                                </td>
                                <td>
                                    <input type="number" min="0" name="lines[{{ $i }}][credit_amount]" value="{{ old("lines.$i.credit_amount") }}" class="form-control" placeholder="0">
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="dms-form-actions">
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-check2-circle"></i> Posting Jurnal
                </button>
            </div>
        </form>
    @endcan

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Jurnal</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Cabang</th>
                    <th>Total Debit</th>
                    <th>Total Kredit</th>
                    <th>Status</th>
                    <th style="width: 90px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($journals as $journal)
                    <tr>
                        <td><strong>{{ $journal->journal_number }}</strong></td>
                        <td>{{ $journal->journal_date?->format('d M Y') }}</td>
                        <td>{{ $journal->description }}</td>
                        <td>{{ $journal->companyBranch?->name ?? 'Global' }}</td>
                        <td class="dms-money">Rp {{ number_format($journal->debit_total, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($journal->credit_total, 0, ',', '.') }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $journal->status === 'posted' ? 'success' : 'secondary' }}">
                                {{ $journal->status_label }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('journal-entries.show', $journal) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="dms-empty">
                            <i class="bi bi-journal-check"></i>
                            <p>Belum ada jurnal umum</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination" style="margin-top: 1rem;">
        {{ $journals->links() }}
    </div>
</div>
@endsection
