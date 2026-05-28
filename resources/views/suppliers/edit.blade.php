@extends('layouts.sidebar')

@section('page-title', 'Edit Pemasok')
@section('breadcrumb', 'Pemasok / Edit')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Pemasok</h3>
        <p class="dms-form-subtitle">Edit informasi pemasok: {{ $supplier->name }}</p>
    </div>

    <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="dms-form-grid">
            <!-- Name -->
            <div class="form-group">
                <label class="form-label">Nama Pemasok <span class="dms-required">*</span></label>
                <input type="text" name="name" value="{{ old('name', $supplier->name) }}" class="form-control" required>
                @error('name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon <span class="dms-required">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control" required>
                @error('phone') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Alternate Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon Alternatif</label>
                <input type="text" name="alternate_phone" value="{{ old('alternate_phone', $supplier->alternate_phone) }}" class="form-control">
                @error('alternate_phone') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-control">
                @error('email') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Market Name -->
            <div class="form-group">
                <label class="form-label">Nama Pasar</label>
                <input type="text" name="market_name" value="{{ old('market_name', $supplier->market_name) }}" class="form-control">
                @error('market_name') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Stall Number -->
            <div class="form-group">
                <label class="form-label">Nomor Lapak/Kios</label>
                <input type="text" name="stall_number" value="{{ old('stall_number', $supplier->stall_number) }}" class="form-control">
                @error('stall_number') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Address -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address) }}</textarea>
                @error('address') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Category -->
            <div class="form-group">
                <label class="form-label">Kategori <span class="dms-required">*</span></label>
                <select name="category" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category', $supplier->category) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Specialty -->
            <div class="form-group">
                <label class="form-label">Spesialisasi</label>
                <input type="text" name="specialty" value="{{ old('specialty', $supplier->specialty) }}" class="form-control">
                @error('specialty') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Min Order -->
            <div class="form-group">
                <label class="form-label">Minimal Order</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-500);">Rp</span>
                    <input type="number" name="min_order" value="{{ old('min_order', $supplier->min_order) }}" class="form-control" style="padding-left: 2.5rem;">
                </div>
                @error('min_order') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Stats Info -->
            <div class="form-group dms-form-span-2">
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
            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $supplier->notes) }}</textarea>
                @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Payment Notes -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan Pembayaran</label>
                <textarea name="payment_notes" class="form-control" rows="2">{{ old('payment_notes', $supplier->payment_notes) }}</textarea>
                @error('payment_notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>

            <!-- Active Status -->
            <div class="form-group dms-form-span-2">
                <label class="dms-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}>
                    <span>Pemasok aktif</span>
                </label>
            </div>
        </div>

        <!-- Buttons -->
        <div class="dms-form-actions">
            <a href="{{ route('suppliers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Pemasok
            </button>
        </div>
    </form>
</div>

@endsection
