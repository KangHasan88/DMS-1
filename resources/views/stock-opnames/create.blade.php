@extends('layouts.sidebar')

@section('page-title', 'Buat Stock Opname')
@section('breadcrumb', 'Inventory / Stock Opname / Buat')

@section('content')
<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Buat Stock Opname</h3>
        <p class="dms-form-subtitle">Sistem akan membuat snapshot stok untuk {{ number_format($activeProductsCount) }} produk aktif.</p>
    </div>

    <form action="{{ route('stock-opnames.store') }}" method="POST">
        @csrf
        <div class="dms-form-grid">
            <div class="form-group">
                <label class="form-label">Tanggal Opname <span class="dms-required">*</span></label>
                <input type="date" name="opname_date" class="form-control" value="{{ old('opname_date', now()->toDateString()) }}" required>
                @error('opname_date') <span class="dms-form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Stock opname akhir bulan">{{ old('notes') }}</textarea>
                @error('notes') <span class="dms-form-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="dms-form-actions">
            <a href="{{ route('stock-opnames.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i>
                Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i>
                Buat Snapshot Opname
            </button>
        </div>
    </form>
</div>
@endsection
