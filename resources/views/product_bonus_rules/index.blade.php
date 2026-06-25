@extends('layouts.sidebar')

@section('page-title', 'Aturan Bonus')
@section('breadcrumb', 'Katalog / Aturan Bonus')

@section('content')
<style>
    .bonus-rule-page { display: grid; gap: 1rem; }
    .bonus-rule-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding-bottom: .9rem; border-bottom: 1px solid #edf2f7; }
    .bonus-rule-title { display: flex; align-items: flex-start; gap: .75rem; }
    .bonus-rule-icon { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; border-radius: 10px; color: #061a3d; background: #fff2e6; }
    .bonus-rule-form { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .85rem; padding: .9rem; border: 1px solid #dbe4f0; border-radius: 10px; background: #fbfdff; }
    .bonus-rule-form .span-2 { grid-column: span 2; }
    .bonus-rule-actions { display: flex; align-items: flex-end; justify-content: flex-end; }
    .bonus-rule-scope { color: #315076; font-size: .82rem; font-weight: 700; }
    .bonus-rule-hidden { display: none; }
    @media (max-width: 1100px) { .bonus-rule-form { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 760px) { .bonus-rule-header { flex-direction: column; } .bonus-rule-form { grid-template-columns: 1fr; } .bonus-rule-form .span-2 { grid-column: auto; } }
</style>

<div class="dms-card bonus-rule-page">
    <div class="dms-section-header bonus-rule-header">
        <div class="bonus-rule-title">
            <div class="bonus-rule-icon"><i class="bi bi-gift"></i></div>
            <div>
                <h3 class="dms-section-title">Aturan Bonus</h3>
                <p class="dms-section-subtitle">Atur promo bonus barang berdasarkan produk pembelian, minimum qty, customer, segment, cabang, dan periode berlaku.</p>
            </div>
        </div>
        <form action="{{ route('product-bonus-rules.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari produk, bonus, customer...">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">Periksa kembali data aturan bonus yang wajib diisi.</div>
    @endif

    @can('edit products')
        <form action="{{ route('product-bonus-rules.store') }}" method="POST" class="bonus-rule-form">
            @csrf
            <div class="form-group span-2">
                <label class="form-label">Produk Dibeli <span class="text-danger">*</span></label>
                <select name="trigger_product_id" class="form-control" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ (string) old('trigger_product_id') === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
                @error('trigger_product_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group span-2">
                <label class="form-label">Produk Bonus <span class="text-danger">*</span></label>
                <select name="bonus_product_id" class="form-control" required>
                    <option value="">-- Pilih Produk Bonus --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ (string) old('bonus_product_id') === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
                @error('bonus_product_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Minimum Qty <span class="text-danger">*</span></label>
                <input type="number" name="min_quantity" value="{{ old('min_quantity', 1) }}" class="form-control" min="1" required>
                @error('min_quantity') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Qty Bonus <span class="text-danger">*</span></label>
                <input type="number" name="bonus_quantity" value="{{ old('bonus_quantity', 1) }}" class="form-control" min="1" required>
                @error('bonus_quantity') <span class="dms-error">{{ $message }}</span> @enderror
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
            <div class="form-group span-2">
                <label class="form-label">Customer Khusus</label>
                <select name="customer_ids[]" id="bonus-customer-ids" class="form-control" multiple size="4">
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ in_array((string) $customer->id, array_map('strval', old('customer_ids', [])), true) ? 'selected' : '' }}>{{ $customer->name }}</option>
                    @endforeach
                </select>
                <small class="dms-form-help">Bisa pilih lebih dari satu. Jika diisi, segment customer tidak dipakai.</small>
            </div>
            <div class="form-group" id="bonus-segment-group">
                <label class="form-label">Segment Customer</label>
                <select name="customer_type" id="bonus-customer-type" class="form-control">
                    <option value="">Semua segment</option>
                    @foreach($customerTypes as $type)
                        <option value="{{ $type->code }}" {{ old('customer_type') === $type->code ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
                <small class="dms-form-help">Dipakai hanya jika customer khusus kosong.</small>
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
                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Contoh: Promo bundling Q3">
            </div>
            <div class="bonus-rule-actions">
                <button type="submit" class="dms-btn dms-btn-primary"><i class="bi bi-plus-circle"></i> Tambah Bonus</button>
            </div>
        </form>
    @endcan

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Promo</th>
                    <th>Scope</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>
                            <strong>{{ $rule->bonus_label }}</strong>
                            @if($rule->notes)<div class="dms-muted">{{ $rule->notes }}</div>@endif
                        </td>
                        <td><span class="bonus-rule-scope">{{ $rule->scope_label }}</span></td>
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
                                <form action="{{ route('product-bonus-rules.toggle-status', $rule) }}" method="POST">
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
                        <td colspan="5" class="dms-empty">
                            <i class="bi bi-gift"></i>
                            <p>Belum ada aturan bonus</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">{{ $rules->links() }}</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const customerSelect = document.getElementById('bonus-customer-ids');
        const segmentGroup = document.getElementById('bonus-segment-group');
        const segmentSelect = document.getElementById('bonus-customer-type');

        function syncSegmentVisibility() {
            const hasSpecificCustomer = Array.from(customerSelect?.selectedOptions || []).length > 0;
            segmentGroup?.classList.toggle('bonus-rule-hidden', hasSpecificCustomer);
            if (segmentSelect) {
                segmentSelect.disabled = hasSpecificCustomer;
                if (hasSpecificCustomer) segmentSelect.value = '';
            }
        }

        customerSelect?.addEventListener('change', syncSegmentVisibility);
        syncSegmentVisibility();
    });
</script>
@endsection
