@extends('layouts.sidebar')

@section('page-title', 'Assign Delivery')
@section('breadcrumb', 'Deliveries / Assign')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-green); margin-bottom: 0.25rem;">Assign Delivery</h3>
        <p style="font-size: 0.85rem; color: var(--k-gray-500);">
            Assign pengiriman ke kurir untuk order yang sudah siap kirim.
        </p>
    </div>

    <form action="{{ route('deliveries.store') }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Order Selection -->
            <div class="form-group">
                <label class="form-label">Pilih Order <span style="color: var(--k-red);">*</span></label>
                <select name="order_id" class="form-control" required>
                    <option value="">-- Pilih Order --</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                            {{ $order->order_number }} - {{ $order->user->name ?? 'N/A' }} (Rp {{ number_format($order->total, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                <small style="color: var(--k-gray-500);">Hanya menampilkan order dengan status "Siap Kirim"</small>
                @error('order_id') <span style="color: var(--k-red); font-size: 0.7rem;">{{ $message }}</span> @enderror
            </div>
            
            <!-- Kurir Selection -->
            <div class="form-group">
                <label class="form-label">Pilih Kurir <span style="color: var(--k-red);">*</span></label>
                <select name="kurir_id" class="form-control" required>
                    <option value="">-- Pilih Kurir --</option>
                    @foreach($kurirs as $kurir)
                        <option value="{{ $kurir->id }}" {{ old('kurir_id') == $kurir->id ? 'selected' : '' }}>
                            {{ $kurir->name }} ({{ $kurir->phone }})
                        </option>
                    @endforeach
                </select>
                @error('kurir_id') <span style="color: var(--k-red); font-size: 0.7rem;">{{ $message }}</span> @enderror
            </div>
            
            <!-- Notes -->
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan untuk kurir (alamat khusus, instruksi pengiriman, dll)">{{ old('notes') }}</textarea>
                @error('notes') <span style="color: var(--k-red); font-size: 0.7rem;">{{ $message }}</span> @enderror
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

<style>
.form-group {
    margin-bottom: 1rem;
}
.form-label {
    display: block;
    margin-bottom: 0.3rem;
    color: var(--k-gray-700);
    font-size: 0.75rem;
    font-weight: 500;
}
.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 6px;
    font-size: 0.8rem;
    transition: all 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: var(--k-green);
    box-shadow: 0 0 0 2px var(--k-green-light);
}
textarea.form-control {
    resize: vertical;
}
.dms-btn {
    padding: 0.4rem 1rem;
    border-radius: 1.5rem;
    font-weight: 500;
    font-size: 0.7rem;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.2s;
}
.dms-btn-primary {
    background: var(--k-green);
    color: white;
}
.dms-btn-primary:hover {
    background: var(--k-green-dark);
}
.dms-btn-outline {
    background: transparent;
    border: 1px solid var(--k-gray-300);
    color: var(--k-gray-600);
}
.dms-btn-outline:hover {
    border-color: var(--k-green);
    color: var(--k-green);
}
</style>
@endsection