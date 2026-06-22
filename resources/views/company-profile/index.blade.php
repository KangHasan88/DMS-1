@extends('layouts.sidebar')

@section('page-title', 'Perusahaan & Cabang')
@section('breadcrumb', 'Administrasi / Perusahaan & Cabang')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Perusahaan & Cabang</h3>
            <p class="dms-section-subtitle">Kelola identitas perusahaan dan cabang yang tampil di invoice.</p>
        </div>
    </div>

    <div class="company-layout">
        <form method="POST" action="{{ route('company-profile.update-company') }}" class="company-panel">
            @csrf
            @method('PUT')

            <div class="panel-heading">
                <div>
                    <h4>Profil Perusahaan</h4>
                    <p>Data legal dan kontak utama perusahaan.</p>
                </div>
                <i class="bi bi-building"></i>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Kode Perusahaan *</label>
                    <input type="text" name="code" maxlength="3" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $company->code ?? 'KMG') }}" placeholder="KMG" required>
                    @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    <small class="form-help">Maksimal 3 karakter. Dipakai untuk nomor dokumen, contoh: PI-KMGTNG202606050001.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Brand / Sistem *</label>
                    <input type="text" name="display_name" class="form-control @error('display_name') is-invalid @enderror" value="{{ old('display_name', $company->display_name) }}" required>
                    @error('display_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Legal PT *</label>
                    <input type="text" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror" value="{{ old('legal_name', $company->legal_name) }}" required>
                    @error('legal_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" class="form-control @error('npwp') is-invalid @enderror" value="{{ old('npwp', $company->npwp) }}">
                    @error('npwp')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $company->phone) }}">
                    @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $company->email) }}">
                    @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group form-span">
                    <label class="form-label">Alamat Kantor Pusat</label>
                    <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $company->address) }}</textarea>
                    @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
            </div>

            @can('edit company profile')
            <div class="form-actions">
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-save"></i> Simpan Profil
                </button>
            </div>
            @endcan
        </form>

        <div class="company-panel">
            <div class="panel-heading">
                <div>
                    <h4>{{ $editingBranch ? 'Edit Cabang' : 'Tambah Cabang' }}</h4>
                    <p>Cabang default dipakai sebagai pengirim di invoice.</p>
                </div>
                <i class="bi bi-diagram-3"></i>
            </div>

            @can('edit company profile')
            <form method="POST" action="{{ $editingBranch ? route('company-profile.branches.update', $editingBranch) : route('company-profile.branches.store') }}">
                @csrf
                @if($editingBranch)
                    @method('PUT')
                @endif

                <div class="form-grid single">
                    <div class="form-group">
                        <label class="form-label">Nama Cabang *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $editingBranch->name ?? '') }}" placeholder="Contoh: Cabang Tangerang" required>
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kode Cabang</label>
                        <input type="text" name="code" maxlength="3" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $editingBranch->code ?? '') }}" placeholder="TNG">
                        @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        <small class="form-help">Maksimal 3 karakter, contoh: TNG, JKT, BDG.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon Cabang</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $editingBranch->phone ?? '') }}">
                        @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Cabang</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $editingBranch->email ?? '') }}">
                        @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="sort_order" min="0" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $editingBranch->sort_order ?? 0) }}">
                        @error('sort_order')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <div class="check-row">
                            <label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $editingBranch->is_active ?? true) ? 'checked' : '' }}> Aktif</label>
                            <label><input type="checkbox" name="is_invoice_default" value="1" {{ old('is_invoice_default', $editingBranch->is_invoice_default ?? false) ? 'checked' : '' }}> Default invoice</label>
                        </div>
                    </div>
                    <div class="form-group form-span">
                        <label class="form-label">Alamat Cabang</label>
                        <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Alamat yang tampil sebagai cabang pengirim invoice">{{ old('address', $editingBranch->address ?? '') }}</textarea>
                        @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-save"></i> {{ $editingBranch ? 'Simpan Cabang' : 'Tambah Cabang' }}
                    </button>
                    @if($editingBranch)
                        <a href="{{ route('company-profile.index') }}" class="dms-btn dms-btn-outline">
                            <i class="bi bi-x-circle"></i> Batal Edit
                        </a>
                    @endif
                </div>
            </form>
            @else
                <div class="empty-note">Anda hanya memiliki akses lihat profil perusahaan.</div>
            @endcan
        </div>
    </div>

    <div class="branch-list">
        <div class="panel-heading compact">
            <div>
                <h4>Daftar Cabang</h4>
                <p>Cabang default invoice akan dipakai pada cetakan invoice saat order belum punya cabang khusus.</p>
            </div>
        </div>

        <div class="dms-table-wrap">
            <table class="dms-table">
                <thead>
                    <tr>
                        <th>Cabang</th>
                        <th>Kode</th>
                        <th>Kontak</th>
                        <th>Alamat</th>
                        <th>Default Invoice</th>
                        <th>Status</th>
                        <th style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($company->branches as $branch)
                        <tr>
                            <td><div class="dms-strong">{{ $branch->name }}</div></td>
                            <td>{{ $branch->code ?: '-' }}</td>
                            <td>
                                <div>{{ $branch->phone ?: '-' }}</div>
                                @if($branch->email)
                                    <div style="font-size: 0.68rem; color: var(--k-gray-500);">{{ $branch->email }}</div>
                                @endif
                            </td>
                            <td>{{ $branch->address ?: '-' }}</td>
                            <td>
                                @if($branch->is_invoice_default)
                                    <span class="dms-badge dms-badge-success">Default</span>
                                @else
                                    <span style="color: var(--k-gray-500);">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="dms-badge {{ $branch->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                                    {{ $branch->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="dms-actions">
                                    @can('edit company profile')
                                    <a href="{{ route('company-profile.index', ['edit_branch' => $branch->id]) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @unless($branch->is_invoice_default)
                                        <form method="POST" action="{{ route('company-profile.branches.default', $branch) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm" title="Jadikan default invoice">
                                                <i class="bi bi-star"></i>
                                            </button>
                                        </form>
                                    @endunless
                                    <button type="button" onclick="toggleBranch({{ $branch->id }})" class="dms-btn dms-btn-outline dms-btn-sm" title="Toggle Status">
                                        <i class="bi bi-power"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--k-gray-500);">
                                Belum ada cabang
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.company-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(360px, 0.85fr);
    gap: 1rem;
    align-items: start;
}

.company-panel,
.branch-list {
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    padding: 1rem;
    background: #fff;
}

.branch-list {
    margin-top: 1rem;
}

.panel-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.panel-heading.compact {
    margin-bottom: 0.75rem;
}

.panel-heading h4 {
    margin: 0;
    font-size: 0.95rem;
    color: var(--k-gray-900);
}

.panel-heading p {
    margin: 0.15rem 0 0;
    color: var(--k-gray-500);
    font-size: 0.75rem;
}

.panel-heading i {
    color: var(--k-blue);
    background: var(--k-blue-light);
    border-radius: 8px;
    padding: 0.55rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.form-grid.single {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.form-span {
    grid-column: 1 / -1;
}

.form-help {
    display: block;
    margin-top: 0.35rem;
    color: var(--k-gray-500);
    font-size: 0.7rem;
    line-height: 1.35;
}

.form-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.check-row {
    min-height: 42px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    color: var(--k-gray-700);
    font-size: 0.75rem;
}

.check-row label {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.empty-note {
    padding: 0.8rem;
    border: 1px dashed var(--k-gray-300);
    border-radius: 8px;
    color: var(--k-gray-500);
    background: var(--k-gray-50);
}

@media (max-width: 1100px) {
    .company-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .form-grid,
    .form-grid.single {
        grid-template-columns: 1fr;
    }
}
</style>

@can('edit company profile')
<script>
function toggleBranch(branchId) {
    if (!confirm('Ubah status cabang ini?')) {
        return;
    }

    fetch(`/company-profile/branches/${branchId}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Gagal mengubah status cabang');
        }
    })
    .catch(() => alert('Terjadi kesalahan'));
}
</script>
@endcan
@endsection
