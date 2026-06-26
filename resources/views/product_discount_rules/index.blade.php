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

    .discount-rule-footer {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: minmax(260px, 1fr) auto;
        gap: .85rem;
        align-items: end;
        padding-top: .25rem;
    }

    .discount-rule-hidden {
        display: none;
    }

    .discount-rule-scope {
        color: #315076;
        font-size: .82rem;
        font-weight: 700;
    }

    .customer-picker-summary {
        min-height: 54px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .72rem .85rem;
        border: 1px solid #c8d6e8;
        border-radius: 8px;
        background: #fff;
    }

    .customer-picker-summary strong {
        display: block;
        color: #061a3d;
        font-size: .92rem;
    }

    .customer-picker-summary span {
        color: #60728c;
        font-size: .82rem;
    }

    .customer-picker-modal {
        position: fixed;
        inset: 0;
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        background: rgba(6, 26, 61, .42);
    }

    .customer-picker-modal.is-open {
        display: flex;
    }

    .customer-picker-dialog {
        width: min(760px, 100%);
        max-height: min(760px, 88vh);
        display: grid;
        grid-template-rows: auto auto 1fr auto;
        overflow: hidden;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 24px 80px rgba(6, 26, 61, .22);
    }

    .customer-picker-head,
    .customer-picker-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid #edf2f7;
    }

    .customer-picker-foot {
        border-top: 1px solid #edf2f7;
        border-bottom: 0;
    }

    .customer-picker-search {
        padding: .85rem 1.1rem;
        border-bottom: 1px solid #edf2f7;
    }

    .customer-picker-list {
        overflow-y: auto;
        padding: .5rem;
    }

    .customer-picker-row {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .68rem .75rem;
        border-radius: 8px;
        cursor: pointer;
    }

    .customer-picker-row:hover {
        background: #f5f8fc;
    }

    .customer-picker-row input {
        width: 18px;
        height: 18px;
    }

    @media (max-width: 1100px) {
        .discount-rule-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .discount-rule-footer {
            grid-template-columns: 1fr;
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
                <select name="company_branch_id" id="discount-company-branch-id" class="form-control">
                    <option value="">Semua cabang</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group span-2">
                <label class="form-label">Customer Khusus</label>
                <div class="customer-picker-summary">
                    <div>
                        <strong id="discount-selected-customer-count">Belum ada customer dipilih</strong>
                        <span id="discount-selected-customer-preview">Rule berlaku sesuai segment customer.</span>
                    </div>
                    <button type="button" class="dms-btn dms-btn-outline" id="discount-open-customer-picker">
                        <i class="bi bi-people"></i> Pilih Customer
                    </button>
                </div>
                <small class="dms-form-help">Gunakan jika diskon hanya berlaku untuk customer tertentu. Segment customer otomatis tidak dipakai.</small>
                @error('customer_ids') <span class="dms-error">{{ $message }}</span> @enderror
                @error('customer_ids.*') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group" id="discount-segment-group">
                <label class="form-label">Segment Customer</label>
                <select name="customer_type" id="discount-customer-type" class="form-control">
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
            <div class="discount-rule-footer">
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Contoh: Promo grosir Q3">
                </div>
                <div class="discount-rule-actions">
                    <button type="submit" class="dms-btn dms-btn-primary"><i class="bi bi-plus-circle"></i> Tambah Diskon</button>
                </div>
            </div>

            <div class="customer-picker-modal" id="discount-customer-picker-modal" aria-hidden="true">
                <div class="customer-picker-dialog">
                    <div class="customer-picker-head">
                        <div>
                            <h4 class="dms-section-title" style="font-size: 1rem; margin: 0;">Pilih Customer Khusus</h4>
                            <p class="dms-section-subtitle" style="margin: .2rem 0 0;">Search nama customer, lalu centang customer yang mendapat diskon.</p>
                        </div>
                        <button type="button" class="dms-btn dms-btn-outline" id="discount-close-customer-picker">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="customer-picker-search">
                        <div class="dms-search-field">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control" id="discount-customer-search" placeholder="Cari customer...">
                        </div>
                    </div>
                    <div class="customer-picker-list" id="discount-customer-list">
                        @foreach($customers as $customer)
                            <label class="customer-picker-row" data-customer-name="{{ strtolower($customer->name) }}" data-customer-branch-id="{{ $customer->company_branch_id ?? '' }}">
                                <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}" data-customer-label="{{ $customer->name }}" {{ in_array((string) $customer->id, array_map('strval', old('customer_ids', [])), true) ? 'checked' : '' }}>
                                <span>
                                    {{ $customer->name }}
                                    <small class="dms-muted" style="display: block;">{{ $customer->companyBranch->name ?? 'Tanpa cabang' }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="customer-picker-foot">
                        <span class="dms-muted" id="discount-modal-selected-count">0 customer dipilih</span>
                        <div class="discount-rule-actions">
                            <button type="button" class="dms-btn dms-btn-outline" id="discount-clear-customers">Bersihkan</button>
                            <button type="button" class="dms-btn dms-btn-primary" id="discount-apply-customer-picker">Selesai</button>
                        </div>
                    </div>
                </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const customerChecks = Array.from(document.querySelectorAll('input[name="customer_ids[]"]'));
        const segmentGroup = document.getElementById('discount-segment-group');
        const segmentSelect = document.getElementById('discount-customer-type');
        const branchSelect = document.getElementById('discount-company-branch-id');
        const modal = document.getElementById('discount-customer-picker-modal');
        const openButton = document.getElementById('discount-open-customer-picker');
        const closeButton = document.getElementById('discount-close-customer-picker');
        const applyButton = document.getElementById('discount-apply-customer-picker');
        const clearButton = document.getElementById('discount-clear-customers');
        const searchInput = document.getElementById('discount-customer-search');
        const selectedCount = document.getElementById('discount-selected-customer-count');
        const selectedPreview = document.getElementById('discount-selected-customer-preview');
        const modalSelectedCount = document.getElementById('discount-modal-selected-count');
        const rows = Array.from(document.querySelectorAll('.customer-picker-row'));

        function selectedBranchId() {
            return branchSelect?.value || '';
        }

        function syncSegmentVisibility() {
            const checkedCustomers = customerChecks.filter((check) => check.checked);
            const hasSpecificCustomer = checkedCustomers.length > 0;

            segmentGroup?.classList.toggle('discount-rule-hidden', hasSpecificCustomer);

            if (segmentSelect) {
                segmentSelect.disabled = hasSpecificCustomer;
                if (hasSpecificCustomer) {
                    segmentSelect.value = '';
                }
            }

            if (selectedCount) {
                selectedCount.textContent = hasSpecificCustomer
                    ? checkedCustomers.length + ' customer dipilih'
                    : 'Belum ada customer dipilih';
            }

            if (selectedPreview) {
                selectedPreview.textContent = hasSpecificCustomer
                    ? checkedCustomers.slice(0, 3).map((check) => check.dataset.customerLabel).join(', ') + (checkedCustomers.length > 3 ? ' +' + (checkedCustomers.length - 3) + ' lainnya' : '')
                    : 'Rule berlaku sesuai segment customer.';
            }

            if (modalSelectedCount) {
                modalSelectedCount.textContent = checkedCustomers.length + ' customer dipilih';
            }
        }

        function openModal() {
            modal?.classList.add('is-open');
            modal?.setAttribute('aria-hidden', 'false');
            searchInput?.focus();
        }

        function closeModal() {
            modal?.classList.remove('is-open');
            modal?.setAttribute('aria-hidden', 'true');
        }

        function filterRows() {
            const keyword = (searchInput?.value || '').trim().toLowerCase();
            const branchId = selectedBranchId();

            rows.forEach((row) => {
                const matchesSearch = row.dataset.customerName.includes(keyword);
                const matchesBranch = !branchId || row.dataset.customerBranchId === branchId;
                const visible = matchesSearch && matchesBranch;
                const checkbox = row.querySelector('input[type="checkbox"]');

                row.style.display = visible ? 'flex' : 'none';

                if (checkbox) {
                    checkbox.disabled = !matchesBranch;
                }
            });
        }

        function clearCustomersOutsideSelectedBranch() {
            const branchId = selectedBranchId();

            if (!branchId) {
                customerChecks.forEach((check) => {
                    check.disabled = false;
                });
                return;
            }

            rows.forEach((row) => {
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (checkbox && row.dataset.customerBranchId !== branchId) {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                }
            });
        }

        customerChecks.forEach((check) => check.addEventListener('change', syncSegmentVisibility));
        openButton?.addEventListener('click', openModal);
        closeButton?.addEventListener('click', closeModal);
        applyButton?.addEventListener('click', closeModal);
        searchInput?.addEventListener('input', filterRows);
        clearButton?.addEventListener('click', function () {
            customerChecks.forEach((check) => {
                check.checked = false;
            });
            syncSegmentVisibility();
        });
        branchSelect?.addEventListener('change', function () {
            clearCustomersOutsideSelectedBranch();
            filterRows();
            syncSegmentVisibility();
        });
        modal?.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        clearCustomersOutsideSelectedBranch();
        filterRows();
        syncSegmentVisibility();
    });
</script>
@endsection
