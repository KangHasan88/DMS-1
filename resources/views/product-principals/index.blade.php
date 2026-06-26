@extends('layouts.sidebar')

@section('page-title', 'Principal')
@section('breadcrumb', 'Katalog / Principal')

@section('content')
<style>
    .principal-form-panel {
        margin-bottom: 1.25rem;
        padding: 1rem 1rem 1.1rem;
        border: 1px solid var(--k-gray-200);
        border-radius: 8px;
        background: var(--k-gray-50);
    }

    .principal-form-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 0.875rem;
        border-bottom: 1px solid var(--k-gray-200);
    }

    .principal-form-title-wrap {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .principal-form-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
    }

    .principal-form-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--k-navy);
    }

    .principal-form-copy {
        margin: 0.25rem 0 0;
        font-size: 0.82rem;
        color: var(--k-gray-600);
    }

    .principal-form-primary-grid {
        display: grid;
        grid-template-columns: minmax(180px, 0.75fr) minmax(280px, 1.25fr);
        gap: 1rem;
    }

    .principal-form-secondary-grid {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) minmax(180px, 0.8fr) minmax(120px, 0.45fr);
        gap: 1rem;
        margin-top: 1rem;
    }

    .principal-form-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--k-gray-200);
    }

    @media (max-width: 900px) {
        .principal-form-primary-grid,
        .principal-form-secondary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Master Principal</h3>
            <p class="dms-section-subtitle">Kelola pemilik brand atau principal produk untuk kebutuhan multi-principal distributor.</p>
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('product-principals.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari kode atau nama principal...">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('product-principals.index', request()->except('status')) }}">Semua Status</option>
                <option value="{{ route('product-principals.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('product-principals.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
    </div>

    @can('edit products')
    <div class="principal-form-panel">
        <div class="principal-form-heading">
            <div class="principal-form-title-wrap">
                <div class="dms-avatar-soft principal-form-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <h4 class="principal-form-title">Tambah Principal</h4>
                    <p class="principal-form-copy">Gunakan untuk mengelompokkan produk berdasarkan pemilik brand atau principal.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('product-principals.store') }}" method="POST">
            @csrf
            <div class="principal-form-primary-grid">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Kode <span class="dms-required">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control" placeholder="UNILEVER" required>
                    @error('code') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Nama Principal <span class="dms-required">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Unilever Indonesia" required>
                    @error('name') <span class="dms-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="principal-form-secondary-grid">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="form-control" placeholder="Nama kontak">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="08xx">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Urutan</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control" min="0">
                </div>
            </div>

            <input type="hidden" name="is_active" value="1">
            <div class="principal-form-actions">
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-plus-circle"></i> Tambah Principal
                </button>
            </div>
        </form>
    </div>
    @endcan

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Principal</th>
                    <th>Kontak</th>
                    <th>Produk</th>
                    <th>Status</th>
                    <th style="width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($principals as $principal)
                    <tr>
                        <td class="dms-strong">{{ $principal->code }}</td>
                        <td>{{ $principal->name }}</td>
                        <td>
                            <div>{{ $principal->contact_person ?: '-' }}</div>
                            @if($principal->phone)
                                <small style="color: var(--k-gray-500);">{{ $principal->phone }}</small>
                            @endif
                        </td>
                        <td>{{ $principal->products_count }} produk</td>
                        <td>
                            <span class="dms-badge {{ $principal->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                                {{ $principal->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            @can('edit products')
                            <form action="{{ route('product-principals.toggle-status', $principal) }}" method="POST">
                                @csrf
                                <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm">
                                    <i class="bi {{ $principal->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                    {{ $principal->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--k-gray-500);">Belum ada principal</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $principals->firstItem() ?? 0 }} - {{ $principals->lastItem() ?? 0 }} dari {{ $principals->total() }} principal
        </div>
        <div>{{ $principals->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
