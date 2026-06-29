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
        gap: .65rem;
        flex-wrap: wrap;
    }

    .pricing-footer {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: minmax(260px, 1fr) minmax(220px, auto);
        gap: .85rem;
        align-items: end;
        padding-top: .85rem;
        margin-top: .15rem;
        border-top: 1px solid #edf2f7;
    }

    .pricing-submit-panel {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: .4rem;
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

    .customer-picker-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-top: .6rem;
        color: #60728c;
        font-size: .82rem;
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

    .customer-picker-empty {
        display: none;
        padding: 1.25rem;
        color: #60728c;
        text-align: center;
    }

    .customer-picker-empty.is-visible {
        display: block;
    }

    .pricing-row-actions {
        display: grid;
        gap: .45rem;
        min-width: 132px;
    }

    .pricing-action-button {
        min-width: 132px;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        white-space: nowrap;
    }

    .pricing-action-button:disabled {
        cursor: not-allowed;
        opacity: .58;
    }

    .pricing-hidden {
        display: none;
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

        .pricing-footer {
            grid-template-columns: 1fr;
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
                            {{ $product->display_name }} - default Rp {{ number_format($product->price, 0, ',', '.') }}
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
                <select name="company_branch_id" id="pricing-company-branch-id" class="form-control">
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
                        <strong id="pricing-selected-customer-count">Belum ada customer dipilih</strong>
                        <span id="pricing-selected-customer-preview">Rule berlaku sesuai segment customer.</span>
                    </div>
                    <button type="button" class="dms-btn dms-btn-outline" id="pricing-open-customer-picker">
                        <i class="bi bi-people"></i> Pilih Customer
                    </button>
                </div>
                <small class="dms-form-help">Gunakan jika harga hanya berlaku untuk customer tertentu. Segment customer otomatis tidak dipakai.</small>
                @error('customer_ids') <span class="dms-error">{{ $message }}</span> @enderror
                @error('customer_ids.*') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group" id="pricing-segment-group">
                <label class="form-label">Segment Customer</label>
                <select name="customer_type" id="pricing-customer-type" class="form-control">
                    <option value="">Semua segment</option>
                    @foreach($customerTypes as $type)
                        <option value="{{ $type->code }}" {{ old('customer_type') === $type->code ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
                <small class="dms-form-help">Dipakai hanya jika customer khusus kosong.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Mulai Berlaku <span class="text-danger">*</span></label>
                <input type="date" name="starts_at" value="{{ old('starts_at', now()->toDateString()) }}" min="{{ now()->toDateString() }}" class="form-control" required>
                @error('starts_at') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Berakhir</label>
                <input type="date" name="ends_at" value="{{ old('ends_at') }}" min="{{ now()->toDateString() }}" class="form-control">
                @error('ends_at') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            <div class="pricing-footer">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Contoh: Harga agen Q3">
                </div>
                <div class="pricing-submit-panel">
                    <button type="submit" class="dms-btn dms-btn-primary"><i class="bi bi-plus-circle"></i> Tambah Harga</button>
                    <span class="dms-muted">Harga baru berlaku untuk transaksi setelah tanggal mulai.</span>
                </div>
            </div>
        </form>

        <div class="customer-picker-modal" id="pricing-customer-picker-modal" aria-hidden="true">
            <div class="customer-picker-dialog" role="dialog" aria-modal="true" aria-labelledby="pricing-customer-picker-title">
                <div class="customer-picker-head">
                    <div>
                        <h3 id="pricing-customer-picker-title" class="dms-section-title" style="font-size: 1.05rem;">Pilih Customer Khusus</h3>
                        <p class="dms-section-subtitle" style="margin: .25rem 0 0;">Search nama customer, lalu centang customer yang mendapat harga khusus.</p>
                    </div>
                    <button type="button" class="dms-btn dms-btn-outline dms-btn-sm" id="pricing-close-customer-picker" aria-label="Tutup">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="customer-picker-search">
                    <div class="dms-search-field">
                        <i class="bi bi-search"></i>
                        <input type="text" id="pricing-customer-search" class="form-control" placeholder="Cari customer...">
                    </div>
                    <div class="customer-picker-meta">
                        <span id="pricing-customer-branch-copy">Menampilkan semua customer aktif.</span>
                        <span id="pricing-customer-visible-count">{{ $customers->count() }} customer tampil</span>
                    </div>
                </div>
                <div class="customer-picker-list" id="pricing-customer-list">
                    @foreach($customers as $customer)
                        @php($oldCustomerIds = array_map('strval', old('customer_ids', [])))
                        <label class="customer-picker-row"
                               data-name="{{ strtolower($customer->name) }}"
                               data-branch-id="{{ $customer->company_branch_id }}">
                            <input type="checkbox"
                                   name="customer_ids[]"
                                   value="{{ $customer->id }}"
                                   {{ in_array((string) $customer->id, $oldCustomerIds, true) ? 'checked' : '' }}>
                            <span>
                                <strong>{{ $customer->name }}</strong>
                                <span class="dms-muted">{{ $customer->companyBranch?->name ?? 'Global / tanpa cabang' }}</span>
                            </span>
                        </label>
                    @endforeach
                    <div class="customer-picker-empty" id="pricing-customer-empty">Tidak ada customer sesuai filter.</div>
                </div>
                <div class="customer-picker-foot">
                    <span class="dms-muted" id="pricing-customer-selected-foot">0 customer dipilih</span>
                    <div class="pricing-actions">
                        <button type="button" class="dms-btn dms-btn-outline" id="pricing-clear-customers">Bersihkan</button>
                        <button type="button" class="dms-btn dms-btn-primary" id="pricing-apply-customers">Selesai</button>
                    </div>
                </div>
            </div>
        </div>
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
                    @php($isExpired = !$rule->is_active && $rule->ends_at && $rule->ends_at->isPast())
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
                                {{ $rule->is_active ? 'Aktif' : ($isExpired ? 'Expired' : 'Nonaktif') }}
                            </span>
                        </td>
                        <td>
                            @can('edit products')
                                <form action="{{ route('product-price-rules.toggle-status', $rule) }}" method="POST" class="pricing-row-actions">
                                    @csrf
                                    <button type="submit"
                                            class="dms-btn dms-btn-outline dms-btn-sm pricing-action-button"
                                            @if($isExpired) disabled title="Periode sudah lewat" @endif>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const branchSelect = document.getElementById('pricing-company-branch-id');
        const segmentGroup = document.getElementById('pricing-segment-group');
        const segmentSelect = document.getElementById('pricing-customer-type');
        const modal = document.getElementById('pricing-customer-picker-modal');
        const openButton = document.getElementById('pricing-open-customer-picker');
        const closeButton = document.getElementById('pricing-close-customer-picker');
        const applyButton = document.getElementById('pricing-apply-customers');
        const clearButton = document.getElementById('pricing-clear-customers');
        const searchInput = document.getElementById('pricing-customer-search');
        const rows = Array.from(document.querySelectorAll('#pricing-customer-list .customer-picker-row'));
        const checkboxes = rows.map(row => row.querySelector('input[type="checkbox"]'));
        const emptyState = document.getElementById('pricing-customer-empty');
        const visibleCount = document.getElementById('pricing-customer-visible-count');
        const branchCopy = document.getElementById('pricing-customer-branch-copy');
        const selectedCount = document.getElementById('pricing-selected-customer-count');
        const selectedPreview = document.getElementById('pricing-selected-customer-preview');
        const selectedFoot = document.getElementById('pricing-customer-selected-foot');

        if (!modal || !openButton || !selectedCount || !selectedPreview || !selectedFoot) {
            return;
        }

        function syncSegmentVisibility() {
            const hasSpecificCustomer = checkboxes.some(checkbox => checkbox.checked);

            segmentGroup?.classList.toggle('pricing-hidden', hasSpecificCustomer);

            if (segmentSelect) {
                segmentSelect.disabled = hasSpecificCustomer;
                if (hasSpecificCustomer) {
                    segmentSelect.value = '';
                }
            }
        }

        function selectedCustomers() {
            return rows
                .filter(row => row.querySelector('input[type="checkbox"]').checked)
                .map(row => row.querySelector('strong').innerText.trim());
        }

        function syncSummary() {
            const selected = selectedCustomers();
            selectedCount.innerText = selected.length ? `${selected.length} customer dipilih` : 'Belum ada customer dipilih';
            selectedPreview.innerText = selected.length ? selected.slice(0, 3).join(', ') + (selected.length > 3 ? ` +${selected.length - 3} lainnya` : '') : 'Rule berlaku sesuai segment customer.';
            selectedFoot.innerText = `${selected.length} customer dipilih`;
            syncSegmentVisibility();
        }

        function syncVisibleRows() {
            const branchId = branchSelect?.value || '';
            const keyword = (searchInput?.value || '').trim().toLowerCase();
            let count = 0;

            rows.forEach(row => {
                const branchMatch = !branchId || row.dataset.branchId === branchId;
                const searchMatch = !keyword || row.dataset.name.includes(keyword);
                const visible = branchMatch && searchMatch;

                row.style.display = visible ? 'flex' : 'none';

                if (!branchMatch) {
                    row.querySelector('input[type="checkbox"]').checked = false;
                }

                if (visible) {
                    count += 1;
                }
            });

            visibleCount.innerText = `${count} customer tampil`;
            branchCopy.innerText = branchId
                ? `Menampilkan customer sesuai cabang yang dipilih.`
                : 'Menampilkan semua customer aktif.';
            emptyState?.classList.toggle('is-visible', count === 0);
            syncSummary();
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

        openButton?.addEventListener('click', openModal);
        closeButton?.addEventListener('click', closeModal);
        applyButton?.addEventListener('click', closeModal);
        clearButton?.addEventListener('click', function () {
            checkboxes.forEach(checkbox => checkbox.checked = false);
            syncSummary();
        });
        searchInput?.addEventListener('input', syncVisibleRows);
        branchSelect?.addEventListener('change', syncVisibleRows);
        checkboxes.forEach(checkbox => checkbox.addEventListener('change', syncSummary));
        modal?.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal?.classList.contains('is-open')) {
                closeModal();
            }
        });

        syncVisibleRows();
        syncSegmentVisibility();
        syncSummary();
    });
</script>
@endsection
