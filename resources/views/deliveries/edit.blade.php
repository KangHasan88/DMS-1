@extends('layouts.sidebar')

@section('page-title', 'Edit Delivery')
@section('breadcrumb', 'Deliveries / Edit')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Pengiriman</h3>
        <p class="dms-form-subtitle">Edit informasi pengiriman untuk Delivery #{{ $delivery->id }}</p>
    </div>

    <form action="{{ route('deliveries.update', $delivery) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Kurir Selection -->
            <div class="form-group">
                <label class="form-label">Pilih Kurir <span class="dms-required">*</span></label>
                <select name="kurir_id" class="form-control" required>
                    <option value="">-- Pilih Kurir --</option>
                    @foreach($kurirs as $kurir)
                        <option value="{{ $kurir->id }}" {{ old('kurir_id', $delivery->kurir_id) == $kurir->id ? 'selected' : '' }}>
                            {{ $kurir->name }} ({{ $kurir->phone }})
                        </option>
                    @endforeach
                </select>
                @error('kurir_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            
            <!-- Notes -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $delivery->notes) }}</textarea>
                @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
        </div>
        
        <!-- Buttons -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('deliveries.show', $delivery) }}" class="dms-btn dms-btn-outline" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-save"></i> Update Delivery
            </button>
        </div>
    </form>
</div>

@endsection