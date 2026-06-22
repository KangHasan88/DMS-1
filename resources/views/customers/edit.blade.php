@extends('layouts.sidebar')

@section('page-title', 'Edit Pelanggan')
@section('breadcrumb', 'Pelanggan / Edit')

@section('content')
<div class="dms-card customer-form-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Pelanggan</h3>
        <p class="dms-form-subtitle">Edit informasi pelanggan: {{ $customer->name }}</p>
    </div>

    <form action="{{ route('customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="customer-master-form">
            <div class="customer-form-section">
                <div class="customer-section-title">
                    <i class="bi bi-person-vcard"></i>
                    <span>Data Utama</span>
                </div>
                <div class="dms-form-grid customer-form-grid">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span class="dms-required">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-control" required>
                        @error('name') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nomor Telepon <span class="dms-required">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-control" required>
                        @error('phone') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control">
                        @error('email') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cabang Pelanggan <span class="dms-required">*</span></label>
                        @if($branchLocked)
                            <input type="hidden" name="company_branch_id" value="{{ $defaultCompanyBranchId }}">
                        @endif
                        <select name="company_branch_id" class="form-control" required {{ $branchLocked ? 'disabled' : '' }}>
                            @foreach($companyBranches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) old('company_branch_id', $defaultCompanyBranchId) === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}{{ $branch->code ? ' - '.$branch->code : '' }}
                            </option>
                            @endforeach
                        </select>
                        <small class="dms-form-help">Dipakai untuk filter pelanggan per cabang saat input order.</small>
                        @error('company_branch_id') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="customer-form-section">
                <div class="customer-section-title">
                    <i class="bi bi-tags"></i>
                    <span>Klasifikasi & Termin</span>
                </div>
                <div class="dms-form-grid customer-form-grid">
                    <div class="form-group">
                        <label class="form-label">Tipe Pelanggan <span class="dms-required">*</span></label>
                        <select name="customer_type" class="form-control" required>
                            @foreach($customerTypes as $type)
                            <option value="{{ $type->code }}" {{ old('customer_type', $customer->customer_type) == $type->code ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <small class="dms-form-help">
                            <a href="{{ route('customer-types.index') }}">Kelola tipe pelanggan</a>
                        </small>
                        @error('customer_type') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Termin Pembayaran</label>
                        <select name="payment_term" class="form-control js-payment-term">
                            <option value="cash" {{ old('payment_term', $customer->payment_term ?? 'cash') == 'cash' ? 'selected' : '' }}>Tunai</option>
                            <option value="credit" {{ old('payment_term', $customer->payment_term ?? 'cash') == 'credit' ? 'selected' : '' }}>Kredit</option>
                        </select>
                        <small class="dms-form-help">Tunai tidak memakai credit limit. Kredit memakai aturan kredit di bawah.</small>
                        @error('payment_term') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="customer-form-section customer-credit-section">
                <div class="customer-section-title">
                    <i class="bi bi-shield-check"></i>
                    <span>Aturan Kredit</span>
                </div>
                <div class="dms-form-grid customer-form-grid">
                    <div class="form-group">
                        <label class="form-label">Credit Limit</label>
                        <input type="number" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}" class="form-control js-credit-control" min="0">
                        <small class="dms-form-help">Hanya berlaku untuk termin Kredit.</small>
                        @error('credit_limit') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Maks. Outstanding Order</label>
                        <input type="number" name="max_outstanding_orders" value="{{ old('max_outstanding_orders', $customer->max_outstanding_orders ?? 0) }}" class="form-control js-credit-control" min="0" max="999">
                        <small class="dms-form-help">Isi 0 jika tidak dibatasi.</small>
                        @error('max_outstanding_orders') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Kredit <span class="dms-required">*</span></label>
                        <select name="credit_status" class="form-control js-credit-control" required>
                            <option value="normal" {{ old('credit_status', $customer->credit_status ?? 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="watchlist" {{ old('credit_status', $customer->credit_status) == 'watchlist' ? 'selected' : '' }}>Watchlist</option>
                            <option value="blocked" {{ old('credit_status', $customer->credit_status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
                        </select>
                        <small class="dms-form-help">Tunai mengabaikan status kredit.</small>
                        @error('credit_status') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan Kredit</label>
                        <textarea name="credit_notes" class="form-control js-credit-control" rows="2">{{ old('credit_notes', $customer->credit_notes) }}</textarea>
                        @error('credit_notes') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="customer-form-section">
                <div class="customer-section-title">
                    <i class="bi bi-geo-alt"></i>
                    <span>Alamat</span>
                </div>
                <div class="dms-form-grid customer-form-grid">
                    @include('customers.partials.address-lookup', [
                        'addressLabel' => 'Alamat Utama',
                        'addressValue' => $customer->address,
                        'latitudeValue' => $customer->latitude,
                        'longitudeValue' => $customer->longitude,
                        'addressHelp' => 'Alamat ini disinkronkan sebagai Alamat Utama. <a href="'.route('customers.show', $customer).'#customer-addresses" style="color: var(--k-green); font-weight: 600;">Tambah alamat pengiriman/invoice lain</a>',
                    ])
                </div>
            </div>

            <div class="customer-form-section">
                <div class="customer-section-title">
                    <i class="bi bi-journal-text"></i>
                    <span>Catatan & Status</span>
                </div>
                <div class="dms-form-grid customer-form-grid">
                    <div class="form-group dms-form-span-2">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $customer->notes) }}</textarea>
                        @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group dms-form-span-2 customer-stat-strip">
                        <div>
                            <span>Total Orders</span>
                            <strong>{{ number_format($customer->total_orders) }}</strong>
                        </div>
                        <div>
                            <span>Total Belanja</span>
                            <strong>Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="form-group dms-form-span-2">
                        <label class="dms-check">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                            <span>Pelanggan aktif</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="dms-form-actions">
            <a href="{{ route('customers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Pelanggan
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentTerm = document.querySelector('.js-payment-term');
    const creditControls = document.querySelectorAll('.js-credit-control');

    function syncCreditControls() {
        const isCash = paymentTerm && paymentTerm.value === 'cash';

        creditControls.forEach((field) => {
            field.disabled = isCash;
            field.closest('.form-group')?.classList.toggle('dms-field-disabled', isCash);

            if (isCash) {
                if (field.name === 'credit_limit' || field.name === 'max_outstanding_orders') {
                    field.value = 0;
                }
                if (field.name === 'credit_status') {
                    field.value = 'normal';
                }
                if (field.name === 'credit_notes') {
                    field.value = '';
                }
            }
        });
    }

    paymentTerm?.addEventListener('change', syncCreditControls);
    syncCreditControls();
});
</script>

@endsection
