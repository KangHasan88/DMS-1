@extends('layouts.sidebar')

@section('page-title', 'Lock Periode')
@section('breadcrumb', 'Akuntansi / Lock Periode')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Lock Periode Akuntansi</h3>
            <p class="dms-section-subtitle">Kunci periode agar jurnal dan dokumen finance tidak bisa diposting atau di-void pada tanggal yang sudah closing.</p>
        </div>
    </div>

    @can('manage journal entries')
        <form action="{{ route('accounting-period-locks.store') }}" method="POST" class="dms-form-section" style="padding: 1rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
            @csrf
            <div class="dms-form-grid" style="align-items: end;">
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai <span class="dms-required">*</span></label>
                    <input type="date" name="date_from" value="{{ old('date_from', now()->startOfMonth()->toDateString()) }}" class="form-control" required>
                    @error('date_from') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Akhir <span class="dms-required">*</span></label>
                    <input type="date" name="date_to" value="{{ old('date_to', now()->endOfMonth()->toDateString()) }}" class="form-control" required>
                    @error('date_to') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Cabang</label>
                    <select name="company_branch_id" class="form-control">
                        <option value="">Global / semua cabang</option>
                        @foreach($companyBranches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) old('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_branch_id') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group dms-form-span-2">
                    <label class="form-label">Alasan Lock <span class="dms-required">*</span></label>
                    <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" maxlength="500" required placeholder="Contoh: Closing bulan berjalan sudah final">
                    @error('reason') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-lock"></i> Lock Periode
                    </button>
                </div>
            </div>
        </form>
    @endcan

    <div class="dms-table-wrap" style="margin-top: 1rem;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>Cabang</th>
                    <th>Status</th>
                    <th>Alasan</th>
                    <th>Dikunci Oleh</th>
                    <th>Dibuka Oleh</th>
                    <th style="width: 180px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($locks as $lock)
                    <tr>
                        <td>
                            <strong>{{ $lock->date_from?->format('d M Y') }}</strong>
                            <span class="dms-muted">s/d</span>
                            <strong>{{ $lock->date_to?->format('d M Y') }}</strong>
                        </td>
                        <td>{{ $lock->companyBranch?->name ?? 'Global / semua cabang' }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $lock->status_badge }}">
                                {{ $lock->status_label }}
                            </span>
                        </td>
                        <td>{{ $lock->reason ?: '-' }}</td>
                        <td>{{ $lock->lockedBy?->name ?? '-' }}</td>
                        <td>{{ $lock->unlockedBy?->name ?? '-' }}</td>
                        <td>
                            @can('manage journal entries')
                                @if($lock->status === \App\Models\AccountingPeriodLock::STATUS_LOCKED)
                                    <form action="{{ route('accounting-period-locks.unlock', $lock) }}" method="POST" style="display: flex; gap: 0.4rem;">
                                        @csrf
                                        <input type="text" name="unlock_reason" class="form-control" required placeholder="Alasan buka">
                                        <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm" onclick="return confirm('Buka lock periode ini?')" title="Buka Lock">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="dms-muted">Sudah dibuka</span>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="dms-empty">
                            <i class="bi bi-lock"></i>
                            <p>Belum ada periode yang dikunci</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination" style="margin-top: 1rem;">
        {{ $locks->links() }}
    </div>
</div>
@endsection
