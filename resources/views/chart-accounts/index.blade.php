@extends('layouts.sidebar')

@section('page-title', 'Daftar Akun')
@section('breadcrumb', 'Akuntansi / Daftar Akun')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Daftar Akun</h3>
            <p class="dms-section-subtitle">Fondasi akuntansi untuk jurnal, buku besar, laba rugi, dan neraca.</p>
        </div>
        @can('manage chart of accounts')
            <button type="button" class="dms-btn dms-btn-primary" onclick="document.getElementById('account-form').scrollIntoView({ behavior: 'smooth', block: 'start' })">
                <i class="bi bi-plus-circle"></i> Tambah Akun
            </button>
        @endcan
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('chart-accounts.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari kode atau nama akun..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('chart-accounts.index', array_merge(request()->except('account_type'), ['account_type' => null])) }}">Semua Tipe</option>
                @foreach($accountTypes as $key => $label)
                    <option value="{{ route('chart-accounts.index', array_merge(request()->except('account_type'), ['account_type' => $key])) }}" {{ request('account_type') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <select onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('chart-accounts.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('chart-accounts.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('chart-accounts.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            @if($canFilterBranches)
                <select onchange="window.location.href = this.value" class="form-control">
                    <option value="{{ route('chart-accounts.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => null])) }}">Semua Cabang</option>
                    <option value="{{ route('chart-accounts.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => 'global'])) }}" {{ request('company_branch_id') === 'global' ? 'selected' : '' }}>Global</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ route('chart-accounts.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    @can('manage chart of accounts')
        <form id="account-form" action="{{ route('chart-accounts.store') }}" method="POST" class="dms-form-section" style="padding: 1rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
            @csrf
            <h4 class="dms-form-section-title"><i class="bi bi-plus-circle"></i> Tambah Akun Baru</h4>
            <div class="dms-form-grid">
                <div class="form-group">
                    <label class="form-label">Kode Akun <span class="dms-required">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control" placeholder="Contoh: 1101" required>
                    @error('code') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Akun <span class="dms-required">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Contoh: Kas Operasional" required>
                    @error('name') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Akun <span class="dms-required">*</span></label>
                    <select name="account_type" class="form-control" required>
                        @foreach($accountTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('account_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('account_type') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Saldo Normal</label>
                    <select name="normal_balance" class="form-control">
                        <option value="">Otomatis dari tipe akun</option>
                        @foreach($normalBalances as $key => $label)
                            <option value="{{ $key }}" {{ old('normal_balance') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('normal_balance') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Parent Akun</label>
                    <select name="parent_id" class="form-control">
                        <option value="">Tidak ada</option>
                        @foreach($parentAccounts as $parent)
                            <option value="{{ $parent->id }}" {{ (string) old('parent_id') === (string) $parent->id ? 'selected' : '' }}>
                                {{ $parent->code }} - {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <span class="dms-error">{{ $message }}</span> @enderror
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
                <div class="form-group">
                    <label class="dms-check" style="margin-top: 1.9rem;">
                        <input type="checkbox" name="is_cash_account" value="1" {{ old('is_cash_account') ? 'checked' : '' }}>
                        Tandai sebagai akun kas/bank
                    </label>
                </div>
                <div class="form-group dms-form-span-2">
                    <label class="form-label">Catatan</label>
                    <textarea name="description" class="form-control" placeholder="Opsional">{{ old('description') }}</textarea>
                    @error('description') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="dms-form-actions">
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-save"></i> Simpan Akun
                </button>
            </div>
        </form>
    @endcan

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Akun</th>
                    <th>Tipe</th>
                    <th>Saldo Normal</th>
                    <th>Cabang</th>
                    <th>Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td><strong>{{ $account->code }}</strong></td>
                        <td>
                            <div class="dms-strong">{{ $account->name }}</div>
                            @if($account->parent)
                                <div class="dms-muted">Parent: {{ $account->parent->code }} - {{ $account->parent->name }}</div>
                            @endif
                            @if($account->is_cash_account)
                                <span class="dms-badge dms-badge-info" style="margin-top: 0.35rem;">Kas/Bank</span>
                            @endif
                        </td>
                        <td>{{ $account->type_label }}</td>
                        <td>{{ $account->normal_balance_label }}</td>
                        <td>{{ $account->companyBranch?->name ?? 'Global' }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $account->is_active ? 'success' : 'secondary' }}">
                                {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            @can('manage chart of accounts')
                                <form action="{{ route('chart-accounts.toggle', $account) }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm" title="{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="bi bi-power"></i>
                                    </button>
                                </form>
                            @else
                                -
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="dms-empty">
                            <i class="bi bi-diagram-3"></i>
                            <p>Belum ada daftar akun</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination" style="margin-top: 1rem;">
        {{ $accounts->links() }}
    </div>
</div>
@endsection
