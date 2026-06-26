@extends('layouts.sidebar')

@section('page-title', 'Principal')
@section('breadcrumb', 'Katalog / Principal')

@section('content')
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
    <div style="margin-bottom: 1rem; padding: 1rem; border: 1px solid var(--k-gray-200); border-radius: 8px; background: var(--k-gray-50);">
        <form action="{{ route('product-principals.store') }}" method="POST" class="dms-form-grid" style="align-items: end;">
            @csrf
            <div class="form-group">
                <label class="form-label">Kode <span class="dms-required">*</span></label>
                <input type="text" name="code" value="{{ old('code') }}" class="form-control" placeholder="UNILEVER" required>
                @error('code') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nama Principal <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Unilever" required>
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="form-control" placeholder="Nama kontak">
            </div>
            <div class="form-group">
                <label class="form-label">Telepon</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="08xx">
            </div>
            <div class="form-group">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control" min="0">
            </div>
            <input type="hidden" name="is_active" value="1">
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Principal
            </button>
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
