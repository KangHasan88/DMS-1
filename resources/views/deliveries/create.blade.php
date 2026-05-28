@extends('layouts.sidebar')

@section('page-title', 'Assign Delivery')
@section('breadcrumb', 'Deliveries / Assign')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Assign Delivery</h3>
        <p class="dms-form-subtitle">Assign pengiriman ke kurir untuk order yang sudah siap kirim.</p>
    </div>

    <form action="{{ route('deliveries.store') }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Order Selection -->
            <div class="form-group">
                <label class="form-label">Pilih Order <span class="dms-required">*</span></label>
                <select name="order_id" class="form-control" required>
                    <option value="">-- Pilih Order --</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                            {{ $order->order_number }} - {{ $order->user->name ?? 'N/A' }} (Rp {{ number_format($order->total, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                <small class="dms-form-help">Hanya menampilkan order dengan status "Siap Kirim"</small>
                @error('order_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            
            <!-- Kurir Selection -->
            <div class="form-group">
                <label class="form-label">Pilih Kurir <span class="dms-required">*</span></label>
                <select name="kurir_id" class="form-control" required>
                    <option value="">-- Pilih Kurir --</option>
                    @foreach($kurirs as $kurir)
                        <option value="{{ $kurir->id }}" {{ old('kurir_id') == $kurir->id ? 'selected' : '' }}>
                            {{ $kurir->name }} ({{ $kurir->phone }})
                        </option>
                    @endforeach
                </select>
                @error('kurir_id') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
            
            <!-- Notes -->
            <div class="form-group dms-form-span-2">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan untuk kurir (alamat khusus, instruksi pengiriman, dll)">{{ old('notes') }}</textarea>
                @error('notes') <span class="dms-error">{{ $message }}</span> @enderror
            </div>
        </div>
        
        <!-- Buttons -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
            <a href="{{ route('deliveries.index') }}" class="dms-btn dms-btn-outline" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                <i class="bi bi-save"></i> Assign Delivery
            </button>
        </div>
    </form>
</div>

@endsection