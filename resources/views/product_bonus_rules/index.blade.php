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
    .bonus-rule-actions { display: flex; align-items: flex-end; justify-content: flex-end; gap: .65rem; flex-wrap: wrap; }
    .bonus-rule-scope { color: #315076; font-size: .82rem; font-weight: 700; }
    .bonus-rule-hidden { display: none; }
    .bonus-rule-footer { grid-column: 1 / -1; display: grid; grid-template-columns: minmax(260px, 1fr) minmax(220px, auto); gap: .85rem; align-items: end; padding-top: .85rem; margin-top: .15rem; border-top: 1px solid #edf2f7; }
    .bonus-rule-submit-panel { display: flex; flex-direction: column; align-items: flex-end; gap: .4rem; }
    .bonus-rule-submit-panel .dms-muted { max-width: 260px; text-align: right; font-size: .78rem; }
    .bonus-customer-summary { min-height: 54px; display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .72rem .85rem; border: 1px solid #c8d6e8; border-radius: 8px; background: #fff; }
    .bonus-customer-summary strong { display: block; color: #061a3d; font-size: .92rem; }
    .bonus-customer-summary span { color: #60728c; font-size: .82rem; }
    .bonus-customer-modal { position: fixed; inset: 0; z-index: 1050; display: none; align-items: center; justify-content: center; padding: 1.25rem; background: rgba(6, 26, 61, .42); }
    .bonus-customer-modal.is-open { display: flex; }
    .bonus-customer-dialog { width: min(760px, 100%); max-height: min(760px, 88vh); display: grid; grid-template-rows: auto auto 1fr auto; overflow: hidden; border-radius: 12px; background: #fff; box-shadow: 0 24px 80px rgba(6, 26, 61, .22); }
    .bonus-customer-head, .bonus-customer-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.1rem; border-bottom: 1px solid #edf2f7; }
    .bonus-customer-foot { border-top: 1px solid #edf2f7; border-bottom: 0; }
    .bonus-customer-search { padding: .85rem 1.1rem; border-bottom: 1px solid #edf2f7; }
    .bonus-customer-meta { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-top: .6rem; color: #60728c; font-size: .82rem; }
    .bonus-customer-list { overflow-y: auto; padding: .5rem; }
    .bonus-customer-row { display: flex; align-items: center; gap: .75rem; padding: .68rem .75rem; border-radius: 8px; cursor: pointer; }
    .bonus-customer-row:hover { background: #f5f8fc; }
    .bonus-customer-row input { width: 18px; height: 18px; }
    .bonus-customer-empty { display: none; padding: 1.25rem; color: #60728c; text-align: center; }
    .bonus-customer-empty.is-visible { display: block; }
    @media (max-width: 1100px) { .bonus-rule-form { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 760px) { .bonus-rule-header { flex-direction: column; } .bonus-rule-form { grid-template-columns: 1fr; } .bonus-rule-form .span-2 { grid-column: auto; } .bonus-rule-footer { grid-template-columns: 1fr; } .bonus-rule-submit-panel { align-items: stretch; } .bonus-rule-submit-panel .dms-muted { text-align: left; } }
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
                <select name="company_branch_id" id="bonus-company-branch-id" class="form-control">
                    <option value="">Semua cabang</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group span-2">
                <label class="form-label">Customer Khusus</label>
                <div class="bonus-customer-summary">
                    <div>
                        <strong id="bonus-selected-customer-count">Belum ada customer dipilih</strong>
                        <span id="bonus-selected-customer-preview">Rule berlaku sesuai segment customer.</span>
                    </div>
                    <button type="button" class="dms-btn dms-btn-outline" id="bonus-open-customer-picker">
                        <i class="bi bi-people"></i> Pilih Customer
                    </button>
                </div>
                <small class="dms-form-help">Gunakan jika bonus hanya berlaku untuk customer tertentu. Segment customer otomatis tidak dipakai.</small>
                @error('customer_ids') <span class="dms-error">{{ $message }}</span> @enderror
                @error('customer_ids.*') <span class="dms-error">{{ $message }}</span> @enderror
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
            <div class="bonus-rule-footer">
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Contoh: Promo bundling Q3">
                </div>
                <div class="bonus-rule-submit-panel">
                    <span class="dms-muted">Sistem akan membuat rule terpisah untuk setiap customer khusus yang dipilih.</span>
                    <button type="submit" class="dms-btn dms-btn-primary"><i class="bi bi-plus-circle"></i> Tambah Bonus</button>
                </div>
            </div>

            <div class="bonus-customer-modal" id="bonus-customer-picker-modal" aria-hidden="true">
                <div class="bonus-customer-dialog">
                    <div class="bonus-customer-head">
                        <div>
                            <h4 class="dms-section-title" style="font-size: 1rem; margin: 0;">Pilih Customer Khusus</h4>
                            <p class="dms-section-subtitle" style="margin: .2rem 0 0;">Search nama customer, lalu centang customer yang mendapat bonus.</p>
                        </div>
                        <button type="button" class="dms-btn dms-btn-outline" id="bonus-close-customer-picker">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="bonus-customer-search">
                        <div class="dms-search-field">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control" id="bonus-customer-search" placeholder="Cari customer...">
                        </div>
                        <div class="bonus-customer-meta">
                            <span id="bonus-customer-branch-note">Menampilkan semua cabang.</span>
                            <span id="bonus-customer-visible-count">0 customer tampil</span>
                        </div>
                    </div>
                    <div class="bonus-customer-list" id="bonus-customer-list">
                        @foreach($customers as $customer)
                            <label class="bonus-customer-row" data-customer-name="{{ strtolower($customer->name) }}" data-customer-branch-id="{{ $customer->company_branch_id ?? '' }}">
                                <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}" data-customer-label="{{ $customer->name }}" {{ in_array((string) $customer->id, array_map('strval', old('customer_ids', [])), true) ? 'checked' : '' }}>
                                <span>
                                    {{ $customer->name }}
                                    <small class="dms-muted" style="display: block;">{{ $customer->companyBranch->name ?? 'Tanpa cabang' }}</small>
                                </span>
                            </label>
                        @endforeach
                        <div class="bonus-customer-empty" id="bonus-customer-empty-state">
                            Tidak ada customer sesuai cabang/search yang dipilih.
                        </div>
                    </div>
                    <div class="bonus-customer-foot">
                        <span class="dms-muted" id="bonus-modal-selected-count">0 customer dipilih</span>
                        <div class="bonus-rule-actions">
                            <button type="button" class="dms-btn dms-btn-outline" id="bonus-clear-customers">Bersihkan</button>
                            <button type="button" class="dms-btn dms-btn-primary" id="bonus-apply-customer-picker">Selesai</button>
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
        const customerChecks = Array.from(document.querySelectorAll('input[name="customer_ids[]"]'));
        const segmentGroup = document.getElementById('bonus-segment-group');
        const segmentSelect = document.getElementById('bonus-customer-type');
        const branchSelect = document.getElementById('bonus-company-branch-id');
        const modal = document.getElementById('bonus-customer-picker-modal');
        const openButton = document.getElementById('bonus-open-customer-picker');
        const closeButton = document.getElementById('bonus-close-customer-picker');
        const applyButton = document.getElementById('bonus-apply-customer-picker');
        const clearButton = document.getElementById('bonus-clear-customers');
        const searchInput = document.getElementById('bonus-customer-search');
        const selectedCount = document.getElementById('bonus-selected-customer-count');
        const selectedPreview = document.getElementById('bonus-selected-customer-preview');
        const modalSelectedCount = document.getElementById('bonus-modal-selected-count');
        const branchNote = document.getElementById('bonus-customer-branch-note');
        const visibleCount = document.getElementById('bonus-customer-visible-count');
        const emptyState = document.getElementById('bonus-customer-empty-state');
        const rows = Array.from(document.querySelectorAll('.bonus-customer-row'));

        function selectedBranchId() {
            return branchSelect?.value || '';
        }

        function selectedBranchLabel() {
            return branchSelect?.selectedOptions?.[0]?.textContent?.trim() || '';
        }

        function syncSegmentVisibility() {
            const checkedCustomers = customerChecks.filter((check) => check.checked);
            const hasSpecificCustomer = checkedCustomers.length > 0;

            segmentGroup?.classList.toggle('bonus-rule-hidden', hasSpecificCustomer);

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
            let shownRows = 0;

            rows.forEach((row) => {
                const matchesSearch = row.dataset.customerName.includes(keyword);
                const matchesBranch = !branchId || row.dataset.customerBranchId === branchId;
                const visible = matchesSearch && matchesBranch;
                const checkbox = row.querySelector('input[type="checkbox"]');

                row.style.display = visible ? 'flex' : 'none';

                if (checkbox) {
                    checkbox.disabled = !matchesBranch;
                }

                if (visible) {
                    shownRows += 1;
                }
            });

            if (branchNote) {
                branchNote.textContent = branchId
                    ? 'Menampilkan customer cabang ' + selectedBranchLabel() + '.'
                    : 'Menampilkan semua cabang.';
            }

            if (visibleCount) {
                visibleCount.textContent = shownRows + ' customer tampil';
            }

            emptyState?.classList.toggle('is-visible', shownRows === 0);
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
