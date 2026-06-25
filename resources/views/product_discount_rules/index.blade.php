@extends('layouts.sidebar')

@section('page-title', 'Aturan Diskon')
@section('breadcrumb', 'Katalog / Aturan Diskon')

@section('content')
<style>
    .discount-rule-page {
        display: grid;
        gap: 1rem;
    }

    .discount-rule-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: .9rem;
        border-bottom: 1px solid #edf2f7;
    }

    .discount-rule-title {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
    }

    .discount-rule-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 38px;
        border-radius: 10px;
        color: #061a3d;
        background: #fff2e6;
    }

    .discount-rule-form {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        padding: .9rem;
        border: 1px solid #dbe4f0;
        border-radius: 10px;
        background: #fbfdff;
    }

    .discount-rule-form .span-2 {
        grid-column: span 2;
    }

    .discount-rule-actions {
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
    }

    .discount-rule-scope {
        color: #315076;
        font-size: .82rem;
        font-weight: 700;
    }

    @media (max-width: 1100px) {
        .discount-rule-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .discount-rule-header {
            flex-direction: column;
        }

        .discount-rule-form {
            grid-template-columns: 1fr;
        }

        .discount-rule-form .span-2 {
            grid-column: auto;
        }
    }
</style>

<div class="dms-card discount-rule-page">
    <div class="dms-section-header discount-rule-header">
        <div class="discount-rule-title">
            <div class="discount-rule-icon"><i class="bi bi-percent"></i></div>
            <div>
                <h3 class="dms-section-title">Aturan Diskon</h3>
                <p class="dms-section-subtitle">Atur diskon produk per customer, segment customer, cabang, minimum qty, dan periode berlaku.</p>
            </div>
        </div>
        <form action="{{ route('product-discount-rules.index') }}" method="GET" class="dms-search-form">
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
        <div class="alert alert-danger">Periksa kembali data aturan diskon yang wajib diisi.</div>
    @endif

    @can('edit products')
        <form action="{{ route('product-discount-rules.store') }}" method="POST" class="discount-rule-form">
            @csrf
            <div class="form-group span-2">
                <label class="form-label">Produk</label>
                <select name="product_id" class="form-control">
                    <option value="">Semua produk</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ (string) old('product_id') === (string) $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                @error('product_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Tipe Diskon <span class="text-danger">*</span></label>
                <select name="discount_type" class="form-control" required>
                    <option value="percent" {{ old('discount_type', 'percent') === 'percent' ? 'selected' : '' }}>Persentase</option>
                    <option value="nominal" {{ old('discount_type') === 'nominal' ? 'selected' : '' }}>Nominal / unit</option>
                </select>
                @error('discount_type') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nilai Diskon <span class="text-danger">*</span></label>
                <input type="number" name="discount_value" value="{{ old('discount_value') }}" class="form-control" min="0" step="0.01" required>
                @error('discount_value') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Minimum Qty <span class="text-danger">*</span></label>
                <input type="number" name="min_quantity" value="{{ old('min_quantity', 1) }}" class="form-control" min="1" required>
                @error('min_quantity') <span class="dms-error">{{ $message }}</span> @enderror
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
                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Contoh: Promo grosir Q3">
            </div>
            <div class="discount-rule-actions">
                <button type="submit" class="dms-btn dms-btn-primary"><i class="bi bi-plus-circle"></i> Tambah Diskon</button>
            </div>
        </form>
    @endcan

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Scope</th>
                    <th>Diskon</th>
                    <th>Minimum Qty</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td><span class="discount-rule-scope">{{ $rule->scope_label }}</span></td>
                        <td class="dms-money">{{ $rule->discount_label }}</td>
                        <td>{{ number_format($rule->min_quantity, 0, ',', '.') }}</td>
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
                                <form action="{{ route('product-discount-rules.toggle-status', $rule) }}" method="POST">
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
                            <i class="bi bi-percent"></i>
                            <p>Belum ada aturan diskon</p>
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
