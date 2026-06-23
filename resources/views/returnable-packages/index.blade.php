@extends('layouts.sidebar')

@section('title', 'Kemasan Kembali - DMS KURMIGO')
@section('page-title', 'Kemasan Kembali')
@section('breadcrumb', 'Inventori / Kemasan Kembali')

@section('content')
<style>
    .returnable-page {
        display: grid;
        gap: 1rem;
    }

    .returnable-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .returnable-title {
        margin: 0;
        color: var(--k-navy, #061a3d);
        font-size: 1.25rem;
        font-weight: 800;
    }

    .returnable-subtitle {
        margin: .35rem 0 0;
        max-width: 680px;
        color: var(--k-gray-600, #64748b);
        font-size: .9rem;
    }

    .returnable-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.1rem;
    }

    .returnable-stat {
        display: flex;
        align-items: center;
        gap: .8rem;
        padding: 1rem;
        border: 1px solid var(--k-gray-200, #dbe4f0);
        border-radius: 10px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    .returnable-stat-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        color: #061a3d;
        background: #eaf2ff;
        font-size: 1.15rem;
        flex: 0 0 42px;
    }

    .returnable-stat-icon i,
    .returnable-panel-icon i {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .returnable-stat-icon i::before,
    .returnable-panel-icon i::before {
        margin: 0;
        vertical-align: 0;
    }

    .returnable-stat strong {
        display: block;
        color: #061a3d;
        font-size: 1.45rem;
        line-height: 1;
        margin-bottom: .25rem;
    }

    .returnable-stat span {
        display: block;
        color: var(--k-gray-600, #64748b);
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .returnable-workspace {
        display: grid;
        grid-template-columns: minmax(320px, .75fr) minmax(420px, 1.25fr);
        gap: 1rem;
    }

    .returnable-panel {
        border: 1px solid var(--k-gray-200, #dbe4f0);
        border-radius: 10px;
        background: #fff;
        padding: 1rem;
    }

    .returnable-panel-header {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin-bottom: .9rem;
        padding-bottom: .8rem;
        border-bottom: 1px solid var(--k-gray-100, #edf2f7);
    }

    .returnable-mini-form {
        display: grid;
        grid-template-columns: minmax(0, .8fr) minmax(0, 1fr) auto;
        gap: .65rem;
        align-items: start;
        margin-bottom: .9rem;
        padding: .85rem;
        border: 1px solid var(--k-gray-200, #dbe4f0);
        border-radius: 10px;
        background: #fbfdff;
    }

    .returnable-mini-form .returnable-mini-action {
        padding-top: 1.55rem;
    }

    .returnable-panel-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #fff4e6;
        color: #ff7a00;
        flex: 0 0 34px;
    }

    .returnable-panel-title {
        margin: 0;
        color: #061a3d;
        font-size: 1rem;
        font-weight: 800;
    }

    .returnable-panel-subtitle {
        margin: .15rem 0 0;
        color: var(--k-gray-600, #64748b);
        font-size: .78rem;
    }

    .returnable-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .85rem;
    }

    .returnable-span-2 {
        grid-column: span 2;
    }

    .returnable-data-card {
        border: 1px solid var(--k-gray-200, #dbe4f0);
        border-radius: 10px;
        background: #fff;
        overflow: hidden;
    }

    .returnable-data-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid var(--k-gray-200, #dbe4f0);
        background: #fbfdff;
    }

    .returnable-data-title {
        margin: 0;
        color: #061a3d;
        font-size: 1rem;
        font-weight: 800;
    }

    .returnable-data-subtitle {
        margin: .2rem 0 0;
        color: var(--k-gray-600, #64748b);
        font-size: .8rem;
    }

    .returnable-count-badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: .25rem .65rem;
        border-radius: 999px;
        color: #061a3d;
        background: #eef5ff;
        font-weight: 800;
        font-size: .78rem;
        white-space: nowrap;
    }

    .returnable-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .6rem;
        padding: 2rem 1rem;
        color: var(--k-gray-600, #64748b);
    }

    .returnable-empty i {
        color: var(--k-gray-400, #94a3b8);
        font-size: 1.2rem;
    }

    .returnable-page .form-label {
        color: #0b1f3f;
        font-size: .78rem;
        font-weight: 800;
        margin-bottom: .3rem;
    }

    .returnable-page .form-control {
        min-height: 42px;
        border-radius: 8px;
    }

    .returnable-page textarea.form-control {
        min-height: 76px;
    }

    .returnable-page .dms-btn {
        min-height: 40px;
    }

    @media (max-width: 1100px) {
        .returnable-workspace {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .returnable-summary,
        .returnable-form-grid,
        .returnable-mini-form {
            grid-template-columns: 1fr;
        }

        .returnable-span-2 {
            grid-column: span 1;
        }

        .returnable-hero,
        .returnable-data-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="returnable-page">
    <div class="dms-card">
        <div class="returnable-hero">
            <div>
                <h2 class="returnable-title">Kontrol Kemasan Kembali</h2>
                <p class="returnable-subtitle">Kelola saldo galon, botol, krat, tabung, pallet, atau kemasan lain yang masih berada di customer.</p>
            </div>
            <a href="{{ route('products.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-box-seam"></i> Mapping Produk
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="returnable-summary">
            <div class="returnable-stat">
                <span class="returnable-stat-icon"><i class="bi bi-recycle"></i></span>
                <div>
                    <strong>{{ number_format($packages->count()) }}</strong>
                    <span>Jenis Kemasan</span>
                </div>
            </div>
            <div class="returnable-stat">
                <span class="returnable-stat-icon"><i class="bi bi-shop"></i></span>
                <div>
                    <strong>{{ number_format($balances->sum('outstanding_quantity')) }}</strong>
                    <span>Outstanding Customer</span>
                </div>
            </div>
            <div class="returnable-stat">
                <span class="returnable-stat-icon"><i class="bi bi-journal-text"></i></span>
                <div>
                    <strong>{{ number_format($movements->total()) }}</strong>
                    <span>Mutasi Tercatat</span>
                </div>
            </div>
        </div>

        <div class="returnable-workspace">
            <div class="returnable-panel">
                <div class="returnable-panel-header">
                    <span class="returnable-panel-icon"><i class="bi bi-plus-circle"></i></span>
                    <div>
                        <h3 class="returnable-panel-title">Tambah Master</h3>
                        <p class="returnable-panel-subtitle">Daftarkan tipe kemasan yang bisa dipantau.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('returnable-packages.store') }}" class="returnable-form-grid">
                    @csrf
                    <div>
                        <label class="form-label">Kode</label>
                        <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" placeholder="GAL19">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Kategori</label>
                        <select name="returnable_package_category_id" class="form-control @error('returnable_package_category_id') is-invalid @enderror">
                            @foreach($activeCategories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('returnable_package_category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('returnable_package_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="returnable-span-2">
                        <label class="form-label">Nama Kemasan</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Galon 19L">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Satuan</label>
                        <input type="text" name="unit" value="{{ old('unit', 'pcs') }}" class="form-control @error('unit') is-invalid @enderror">
                        @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Nilai Pengganti</label>
                        <input type="number" name="replacement_value" value="{{ old('replacement_value', 0) }}" class="form-control @error('replacement_value') is-invalid @enderror" min="0">
                        @error('replacement_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="returnable-span-2">
                        <label class="form-label">Catatan</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="Contoh: Galon kosong wajib kembali dari toko">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="returnable-span-2">
                        <label class="dms-check">
                            <input type="checkbox" name="requires_serial_tracking" value="1" @checked(old('requires_serial_tracking'))>
                            <span>Butuh tracking nomor seri</span>
                        </label>
                    </div>
                    <div class="returnable-span-2">
                        <button type="submit" class="dms-btn dms-btn-primary">
                            <i class="bi bi-save"></i> Simpan Master
                        </button>
                    </div>
                </form>
            </div>

            <div class="returnable-panel">
                <div class="returnable-panel-header">
                    <span class="returnable-panel-icon"><i class="bi bi-arrow-left-right"></i></span>
                    <div>
                        <h3 class="returnable-panel-title">Catat Mutasi Customer</h3>
                        <p class="returnable-panel-subtitle">Gunakan untuk retur kemasan, hilang/rusak, atau koreksi saldo manual.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('returnable-packages.movements.store') }}" class="returnable-form-grid">
                    @csrf
                    <div>
                        <label class="form-label">Kemasan</label>
                        <select name="returnable_package_id" class="form-control @error('returnable_package_id') is-invalid @enderror">
                            <option value="">Pilih kemasan</option>
                            @foreach($activePackages as $package)
                                <option value="{{ $package->id }}" data-value="{{ $package->replacement_value }}" @selected((string) old('returnable_package_id') === (string) $package->id)>
                                    {{ $package->code }} - {{ $package->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('returnable_package_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-control @error('customer_id') is-invalid @enderror">
                            <option value="">Pilih customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if($canFilterBranches)
                        <div>
                            <label class="form-label">Cabang</label>
                            <select name="company_branch_id" class="form-control @error('company_branch_id') is-invalid @enderror">
                                <option value="">Global / tanpa cabang</option>
                                @foreach($companyBranches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('company_branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('company_branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div>
                        <label class="form-label">Tipe Mutasi</label>
                        <select name="movement_type" class="form-control @error('movement_type') is-invalid @enderror">
                            @foreach($movementTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('movement_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('movement_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="movement_date" value="{{ old('movement_date', now()->toDateString()) }}" class="form-control @error('movement_date') is-invalid @enderror">
                        @error('movement_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Qty</label>
                        <input type="number" name="quantity" value="{{ old('quantity', 1) }}" class="form-control @error('quantity') is-invalid @enderror" min="1">
                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Nilai / Unit</label>
                        <input type="number" name="unit_value" value="{{ old('unit_value') }}" class="form-control @error('unit_value') is-invalid @enderror" min="0" placeholder="Auto">
                        @error('unit_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">No Referensi</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control @error('reference_number') is-invalid @enderror" placeholder="Invoice / DO / manual">
                        @error('reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="returnable-span-2">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-control @error('notes') is-invalid @enderror" placeholder="Contoh: galon kosong kembali dari toko">
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="returnable-span-2">
                        <button type="submit" class="dms-btn dms-btn-primary">
                            <i class="bi bi-journal-plus"></i> Simpan Mutasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="returnable-data-card">
        <div class="returnable-data-header">
            <div>
                <h2 class="returnable-data-title">Saldo Outstanding</h2>
                <p class="returnable-data-subtitle">Customer yang masih memegang kemasan returnable.</p>
            </div>
            <span class="returnable-count-badge">{{ number_format($balances->count()) }} customer</span>
        </div>

        <div class="table-responsive">
            <table class="table dms-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kemasan</th>
                        <th>Customer</th>
                        <th>Cabang</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-end">Eksposur Nilai</th>
                        <th>Update Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $balance)
                        <tr>
                            <td>
                                <strong>{{ $balance->package->name }}</strong><br>
                                <span class="text-muted">{{ $balance->package->code }}</span>
                            </td>
                            <td>{{ $balance->customer->name }}</td>
                            <td>{{ $balance->companyBranch?->name ?? '-' }}</td>
                            <td class="text-end">{{ number_format($balance->outstanding_quantity) }} {{ $balance->package->unit }}</td>
                            <td class="text-end">Rp {{ number_format($balance->outstanding_quantity * $balance->package->replacement_value, 0, ',', '.') }}</td>
                            <td>{{ $balance->last_movement_at?->format('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="returnable-empty">
                                    <i class="bi bi-inbox"></i>
                                    <span>Belum ada saldo kemasan outstanding.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="returnable-data-card">
        <div class="returnable-data-header">
            <div>
                <h2 class="returnable-data-title">Riwayat Mutasi</h2>
                <p class="returnable-data-subtitle">Jejak keluar, kembali, dijual putus, hilang, rusak, dan penyesuaian.</p>
            </div>
            <span class="returnable-count-badge">{{ number_format($movements->total()) }} mutasi</span>
        </div>

        <div class="table-responsive">
            <table class="table dms-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>No Mutasi</th>
                        <th>Tanggal</th>
                        <th>Kemasan</th>
                        <th>Customer</th>
                        <th>Tipe</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-end">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td>
                                <strong>{{ $movement->movement_number }}</strong><br>
                                <span class="text-muted">{{ $movement->reference_number ?: '-' }}</span>
                            </td>
                            <td>{{ $movement->movement_date->format('d M Y') }}</td>
                            <td>{{ $movement->package->name }}</td>
                            <td>{{ $movement->customer->name }}</td>
                            <td><span class="dms-badge">{{ $movement->type_label }}</span></td>
                            <td class="text-end">{{ number_format($movement->quantity) }}</td>
                            <td class="text-end">{{ number_format($movement->balance_before) }} -> {{ number_format($movement->balance_after) }}</td>
                            <td class="text-end">Rp {{ number_format($movement->total_value, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="returnable-empty">
                                    <i class="bi bi-clock-history"></i>
                                    <span>Belum ada mutasi kemasan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding: 1rem;">
            {{ $movements->links() }}
        </div>
    </div>

    <div class="returnable-data-card">
        <div class="returnable-data-header">
            <div>
                <h2 class="returnable-data-title">Daftar Master Kemasan</h2>
                <p class="returnable-data-subtitle">Jenis kemasan yang bisa ditracking sebagai returnable packaging.</p>
            </div>
            <span class="returnable-count-badge">{{ number_format($packages->count()) }} jenis</span>
        </div>

        <div class="table-responsive">
            <table class="table dms-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th class="text-end">Nilai Pengganti</th>
                        <th>Tracking</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td><strong>{{ $package->code }}</strong></td>
                            <td>{{ $package->name }}</td>
                            <td>{{ $package->category_label }}</td>
                            <td>{{ $package->unit }}</td>
                            <td class="text-end">Rp {{ number_format($package->replacement_value, 0, ',', '.') }}</td>
                            <td>{{ $package->requires_serial_tracking ? 'Serial' : 'Qty' }}</td>
                            <td>
                                <span class="dms-badge {{ $package->is_active ? 'dms-badge-success' : '' }}">{{ $package->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="returnable-empty">
                                    <i class="bi bi-recycle"></i>
                                    <span>Belum ada master kemasan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="returnable-data-card">
        <div class="returnable-data-header">
            <div>
                <h2 class="returnable-data-title">Kategori Kemasan</h2>
                <p class="returnable-data-subtitle">Master kategori untuk pengelompokan galon, botol, tabung, krat, dan kemasan lain.</p>
            </div>
            <span class="returnable-count-badge">{{ number_format($categories->count()) }} kategori</span>
        </div>

        <div style="padding: 1rem 1.15rem 0;">
            <form method="POST" action="{{ route('returnable-packages.categories.store') }}" class="returnable-mini-form">
                @csrf
                <div>
                    <label class="form-label">Kode</label>
                    <input type="text" name="category_code" value="{{ old('category_code') }}" class="form-control @error('category_code') is-invalid @enderror" placeholder="jerigen">
                    @error('category_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="category_name" value="{{ old('category_name') }}" class="form-control @error('category_name') is-invalid @enderror" placeholder="Jerigen">
                    @error('category_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="returnable-mini-action">
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table dms-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th class="text-end">Sort</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td><strong>{{ $category->code }}</strong></td>
                            <td>{{ $category->name }}</td>
                            <td class="text-end">{{ $category->sort_order }}</td>
                            <td>
                                <span class="dms-badge {{ $category->is_active ? 'dms-badge-success' : '' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('returnable-packages.categories.toggle', $category) }}" onsubmit="return confirm('Ubah status kategori {{ $category->name }}?')" style="display: inline-flex;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="dms-btn dms-btn-outline" style="min-height: 32px; padding: .35rem .7rem;">
                                        @if($category->is_active)
                                            <i class="bi bi-pause-circle"></i> Nonaktifkan
                                        @else
                                            <i class="bi bi-play-circle"></i> Aktifkan
                                        @endif
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="returnable-empty">
                                    <i class="bi bi-tags"></i>
                                    <span>Belum ada kategori kemasan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
