@extends('layouts.sidebar')

@section('page-title', 'Edit Supplier')
@section('breadcrumb', 'Suppliers / Edit')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Edit Supplier</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">Edit informasi supplier: {{ $supplier->name }}</p>
    </div>

    <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <!-- Name -->
            <div class="form-group">
                <label class="form-label">Nama Supplier <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="name" value="{{ old('name', $supplier->name) }}" class="form-control" required>
                @error('name') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control" required>
                @error('phone') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Alternate Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon Alternatif</label>
                <input type="text" name="alternate_phone" value="{{ old('alternate_phone', $supplier->alternate_phone) }}" class="form-control">
                @error('alternate_phone') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-control">
                @error('email') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Market Name -->
            <div class="form-group">
                <label class="form-label">Nama Pasar</label>
                <input type="text" name="market_name" value="{{ old('market_name', $supplier->market_name) }}" class="form-control">
                @error('market_name') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Stall Number -->
            <div class="form-group">
                <label class="form-label">Nomor Lapak/Kios</label>
                <input type="text" name="stall_number" value="{{ old('stall_number', $supplier->stall_number) }}" class="form-control">
                @error('stall_number') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Address -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address) }}</textarea>
                @error('address') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Category -->
            <div class="form-group">
                <label class="form-label">Kategori <span style="color: var(--k-red);">*</span></label>
                <select name="category" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category', $supplier->category) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Specialty -->
            <div class="form-group">
                <label class="form-label">Spesialisasi</label>
                <input type="text" name="specialty" value="{{ old('specialty', $supplier->specialty) }}" class="form-control">
                @error('specialty') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Min Order -->
            <div class="form-group">
                <label class="form-label">Minimal Order</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                    <input type="number" name="min_order" value="{{ old('min_order', $supplier->min_order) }}" class="form-control" style="padding-left: 2.5rem;">
                </div>
                @error('min_order') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Stats Info -->
            <div class="form-group" style="grid-column: span 2;">
                <div style="display: flex; gap: 1rem; padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
                    <div style="flex: 1; text-align: center;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Total Transaksi</div>
                        <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">{{ number_format($supplier->total_transactions) }}</div>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Total Pembelian</div>
                        <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-green);">Rp {{ number_format($supplier->total_purchase, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $supplier->notes) }}</textarea>
                @error('notes') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Payment Notes -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Catatan Pembayaran</label>
                <textarea name="payment_notes" class="form-control" rows="2">{{ old('payment_notes', $supplier->payment_notes) }}</textarea>
                @error('payment_notes') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Active Status -->
            <div class="form-group" style="grid-column: span 2;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}>
                    <span>Supplier aktif</span>
                </label>
            </div>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('suppliers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Supplier
            </button>
        </div>
    </form>
</div>

<style>
.form-group {
    margin-bottom: 1rem;
}
.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--k-gray-700);
    font-size: 0.85rem;
}
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: var(--k-green);
    box-shadow: 0 0 0 3px var(--k-green-light);
}
textarea.form-control {
    resize: vertical;
    min-height: 80px;
}
</style>
@endsection