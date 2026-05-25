@extends('layouts.sidebar')

@section('page-title', 'Edit Pelanggan')
@section('breadcrumb', 'Pelanggan / Edit')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Edit Pelanggan</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">Edit informasi pelanggan: {{ $customer->name }}</p>
    </div>

    <form action="{{ route('customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <!-- Name -->
            <div class="form-group">
                <label class="form-label">Nama Lengkap <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-control" required>
                @error('name') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label class="form-label">Nomor Telepon <span style="color: var(--k-red);">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-control" required>
                @error('phone') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control">
                @error('email') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Tipe Pelanggan -->
            <div class="form-group">
                <label class="form-label">Tipe Pelanggan <span style="color: var(--k-red);">*</span></label>
                <select name="customer_type" class="form-control" required>
                    <option value="regular" {{ old('customer_type', $customer->customer_type) == 'regular' ? 'selected' : '' }}>Regular</option>
                    <option value="premium" {{ old('customer_type', $customer->customer_type) == 'premium' ? 'selected' : '' }}>Premium</option>
                    <option value="wholesale" {{ old('customer_type', $customer->customer_type) == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                </select>
                @error('customer_type') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Address -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="3">{{ old('address', $customer->address) }}</textarea>
                @error('address') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Latitude & Longitude -->
            <div class="form-group">
                <label class="form-label">Latitude</label>
                <input type="text" name="latitude" value="{{ old('latitude', $customer->latitude) }}" class="form-control">
                @error('latitude') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Longitude</label>
                <input type="text" name="longitude" value="{{ old('longitude', $customer->longitude) }}" class="form-control">
                @error('longitude') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Notes -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $customer->notes) }}</textarea>
                @error('notes') <span style="color: var(--k-red); font-size: 0.75rem;">{{ $message }}</span> @enderror
            </div>

            <!-- Stats Info -->
            <div class="form-group" style="grid-column: span 2;">
                <div style="display: flex; gap: 1rem; padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
                    <div style="flex: 1; text-align: center;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Total Orders</div>
                        <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">{{ number_format($customer->total_orders) }}</div>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Total Belanja</div>
                        <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-green);">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <!-- Active Status -->
            <div class="form-group" style="grid-column: span 2;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                    <span>Pelanggan aktif</span>
                </label>
            </div>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('customers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update Pelanggan
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
