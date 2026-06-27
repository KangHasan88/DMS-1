@extends('layouts.sidebar')

@section('page-title', 'Gudang')
@section('breadcrumb', 'Inventori / Gudang')

@section('content')
<style>
    .warehouse-form-panel {
        margin-bottom: 1rem;
        padding: 0.9rem 1rem 1rem;
        border: 1px solid var(--k-gray-200);
        border-radius: 8px;
        background: var(--k-gray-50);
    }

    .warehouse-form-heading {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 0.85rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--k-gray-200);
    }

    .warehouse-form-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
    }

    .warehouse-form-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--k-blue-darker);
    }

    .warehouse-form-copy {
        margin: 0.25rem 0 0;
        font-size: 0.82rem;
        color: var(--k-gray-600);
    }

    .warehouse-form-grid {
        display: grid;
        grid-template-columns: minmax(140px, 0.7fr) minmax(240px, 1.2fr) minmax(190px, 0.9fr) minmax(260px, 1.4fr) minmax(90px, 0.45fr) auto;
        gap: 0.75rem;
        align-items: end;
    }

    .warehouse-row-actions {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        flex-wrap: wrap;
    }

    .warehouse-row-actions .dms-btn-sm {
        width: auto;
        padding: 0 0.75rem;
    }

    @media (max-width: 1100px) {
        .warehouse-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .warehouse-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Master Gudang</h3>
            <p class="dms-section-subtitle">Kelola lokasi gudang sebagai dasar BTB, BKB, opname, dan audit mutasi stok.</p>
        </div>
        <span class="dms-badge dms-badge-primary">{{ $warehouses->total() }} gudang</span>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('warehouses.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari kode, nama, alamat gudang...">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('warehouses.index', request()->except('status')) }}">Semua Status</option>
                <option value="{{ route('warehouses.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('warehouses.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
    </div>

    @if ($errors->any())
        <div style="margin-bottom: 1rem; padding: 0.75rem 0.9rem; border: 1px solid #fecaca; border-radius: 8px; background: #fff1f2; color: #b91c1c; font-weight: 700;">
            {{ $errors->first() }}
        </div>
    @endif

    @can('manage warehouse')
    <div class="warehouse-form-panel">
        <div class="warehouse-form-heading">
            <div class="dms-avatar-soft warehouse-form-icon">
                <i class="bi bi-buildings"></i>
            </div>
            <div>
                <h4 class="warehouse-form-title">Tambah Gudang</h4>
                <p class="warehouse-form-copy">Daftarkan gudang operasional, transit, retur, atau karantina barang rusak.</p>
            </div>
        </div>

        <form action="{{ route('warehouses.store') }}" method="POST">
            @csrf
            <div class="warehouse-form-grid">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Kode <span class="dms-required">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control" placeholder="MAIN" required>
                    @error('code') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Nama Gudang <span class="dms-required">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Gudang Utama" required>
                    @error('name') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Tipe <span class="dms-required">*</span></label>
                    <select name="type" class="form-control" required>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" {{ old('type', 'main') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Alamat / Lokasi</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="form-control" placeholder="Area, kota, atau catatan lokasi">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Urutan</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control" min="0">
                </div>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="dms-btn dms-btn-primary" style="white-space: nowrap;">
                        <i class="bi bi-plus-circle"></i> Tambah
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endcan

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Gudang</th>
                    <th>Tipe</th>
                    <th>Lokasi</th>
                    <th>Mutasi</th>
                    <th>Status</th>
                    <th style="width: 240px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $warehouse)
                    <tr>
                        <td class="dms-strong">{{ $warehouse->code }}</td>
                        <td>
                            <div class="dms-strong">{{ $warehouse->name }}</div>
                            @if($warehouse->is_default)
                                <span class="dms-badge dms-badge-primary">Default</span>
                            @endif
                        </td>
                        <td>{{ $warehouse->type_label }}</td>
                        <td>{{ $warehouse->address ?: '-' }}</td>
                        <td>{{ number_format($warehouse->stock_movements_count) }} movement</td>
                        <td>
                            <span class="dms-badge {{ $warehouse->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                                {{ $warehouse->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            @can('manage warehouse')
                            <div class="warehouse-row-actions">
                                @if(! $warehouse->is_default && $warehouse->is_active)
                                    <form action="{{ route('warehouses.default', $warehouse) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm" style="white-space: nowrap;">
                                            <i class="bi bi-star"></i> Default
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('warehouses.toggle-status', $warehouse) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm" style="white-space: nowrap; min-width: 122px; justify-content: center;">
                                        <i class="bi {{ $warehouse->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                        {{ $warehouse->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--k-gray-500);">Belum ada gudang</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $warehouses->firstItem() ?? 0 }} - {{ $warehouses->lastItem() ?? 0 }} dari {{ $warehouses->total() }} gudang
        </div>
        <div>{{ $warehouses->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
