@extends('layouts.sidebar')

@section('page-title', 'Daftar Harga')
@section('breadcrumb', 'Katalog / Daftar Harga')

@section('content')
<style>
    .pricing-page {
        display: grid;
        gap: 1rem;
    }

    .pricing-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: .9rem;
        border-bottom: 1px solid #edf2f7;
    }

    .pricing-title {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
    }

    .pricing-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 38px;
        border-radius: 10px;
        color: #061a3d;
        background: #eaf2ff;
    }

    .pricing-form {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        padding: .9rem;
        border: 1px solid #dbe4f0;
        border-radius: 10px;
        background: #fbfdff;
    }

    .pricing-form .span-2 {
        grid-column: span 2;
    }

    .pricing-actions {
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
    }

    .pricing-scope {
        color: #315076;
        font-size: .82rem;
        font-weight: 700;
    }

    @media (max-width: 1100px) {
        .pricing-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .pricing-header {
            flex-direction: column;
        }

        .pricing-form {
            grid-template-columns: 1fr;
        }

        .pricing-form .span-2 {
            grid-column: auto;
        }
    }
</style>

<div class="dms-card pricing-page">
    <div class="dms-section-header pricing-header">
        <div class="pricing-title">
            <div class="pricing-icon"><i class="bi bi-tags"></i></div>
            <div>
                <h3 class="dms-section-title">Daftar Harga</h3>
                <p class="dms-section-subtitle">Atur harga produk per customer, segment customer, cabang, dan periode berlaku.</p>
            </div>
        </div>
        <form action="{{ route('product-price-rules.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari produk, customer, segment...">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">Periksa kembali data daftar harga yang wajib diisi.</div>
    @endif

    @can('edit products')
        <form action="{{ route('product-price-rules.store') }}" method="POST" class="pricing-form">
            @csrf
            <div class="form-group span-2">
                <label class="form-label">Produk <span class="text-danger">*</span></label>
                <select name="product_id" class="form-control" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ (string) old('product_id') === (string) $product->id ? 'selected' : '' }}>
                            {{ $product->name }} - default Rp {{ number_format($product->price, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                @error('product_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Harga <span class="text-danger">*</span></label>
                <input type="number" name="price" value="{{ old('price') }}" class="form-control" min="0" required>
                @error('price') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Cabang</label>
                <select name="company_branch_id" class="form-control">
                    <option value="">Semua cabang</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Customer Khusus</label>
                <select name="customer_id" class="form-control">
                    <option value="">Tidak spesifik</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
                <small class="dms-form-help">Prioritas tertinggi.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Segment Customer</label>
                <select name="customer_type" class="form-control">
                    <option value="">Semua segment</option>
                    @foreach($customerTypes as $type)
                        <option value="{{ $type->code }}" {{ old('customer_type') === $type->code ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
                <small class="dms-form-help">Diabaikan jika customer khusus dipilih.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Mulai Berlaku <span class="text-danger">*</span></label>
                <input type="date" name="starts_at" value="{{ old('starts_at', now()->toDateString()) }}" class="form-control" required>
                @error('starts_at') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Berakhir</label>
                <input type="date" name="ends_at" value="{{ old('ends_at') }}" class="form-control">
                @error('ends_at') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group span-2">
                <label class="form-label">Catatan</label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Contoh: Harga agen Q3">
            </div>
            <div class="pricing-actions">
                <button type="submit" class="dms-btn dms-btn-primary"><i class="bi bi-plus-circle"></i> Tambah Harga</button>
            </div>
        </form>
    @endcan

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Scope</th>
                    <th>Harga</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>
                            <strong>{{ $rule->product?->name ?? '-' }}</strong>
                            <div class="dms-muted">Default Rp {{ number_format($rule->product?->price ?? 0, 0, ',', '.') }}</div>
                        </td>
                        <td><span class="pricing-scope">{{ $rule->scope_label }}</span></td>
                        <td class="dms-money">Rp {{ number_format($rule->price, 0, ',', '.') }}</td>
                        <td>
                            {{ $rule->starts_at?->format('d M Y') }}
                            <div class="dms-muted">s/d {{ $rule->ends_at?->format('d M Y') ?? 'seterusnya' }}</div>
                        </td>
                        <td>
                            <span class="dms-badge {{ $rule->is_active ? 'dms-badge-success' : 'dms-badge-muted' }}">
                                {{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            @can('edit products')
                                <form action="{{ route('product-price-rules.toggle-status', $rule) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm">
                                        <i class="bi {{ $rule->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                        {{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="dms-empty">
                            <i class="bi bi-tags"></i>
                            <p>Belum ada aturan harga</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        {{ $rules->links() }}
    </div>
</div>
@endsection
