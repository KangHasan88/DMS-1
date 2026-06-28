@extends('layouts.sidebar')

@section('page-title', 'Detail Stock Opname')
@section('breadcrumb', 'Inventory / Stock Opname / Detail')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $stockOpname->opname_number }}</h3>
            <p class="dms-section-subtitle">
                Tanggal {{ $stockOpname->opname_date->format('d M Y') }} ·
                Gudang {{ $stockOpname->warehouse?->name ?? '-' }} ·
                <span class="dms-badge dms-badge-{{ $stockOpname->status_color }}">{{ $stockOpname->status_label }}</span>
            </p>
        </div>
        <a href="{{ route('stock-opnames.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i>
            Kembali
        </a>
    </div>

    @if($stockOpname->notes)
        <div class="dms-toolbar">
            <span class="dms-muted">{{ $stockOpname->notes }}</span>
        </div>
    @endif

    <form action="{{ route('stock-opnames.update', $stockOpname) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="dms-table-wrap">
            <table class="dms-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Stok Sistem</th>
                        <th>Stok Fisik</th>
                        <th>Selisih</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockOpname->items as $index => $item)
                        <tr>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                <div class="dms-identity">
                                    <div class="dms-avatar-soft"><i class="bi bi-box-seam"></i></div>
                                    <div>
                                        <div class="dms-strong">{{ $item->product->name }}</div>
                                        <div class="dms-muted">{{ $item->product->category ?? '-' }} / {{ $item->product->unit->symbol ?? $item->product->unit->name ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ number_format($item->system_quantity) }}</td>
                            <td>
                                @if($stockOpname->isDraft())
                                    <input type="number" name="items[{{ $index }}][counted_quantity]" class="form-control" value="{{ old("items.$index.counted_quantity", $item->counted_quantity) }}" min="0" style="min-width: 110px;">
                                @else
                                    {{ number_format($item->counted_quantity) }}
                                @endif
                            </td>
                            <td>
                                @php($diff = $item->difference_quantity)
                                <span style="color: {{ $diff < 0 ? 'var(--k-red)' : ($diff > 0 ? 'var(--k-success)' : 'var(--k-gray-500)') }}; font-weight: 700;">
                                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff) }}
                                </span>
                            </td>
                            <td>
                                @if($stockOpname->isDraft())
                                    <input type="text" name="items[{{ $index }}][notes]" class="form-control" value="{{ old("items.$index.notes", $item->notes) }}" placeholder="Opsional">
                                @else
                                    {{ $item->notes ?? '-' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($stockOpname->isDraft())
            <div class="dms-form-actions" style="margin-top: 1rem;">
                <button type="submit" class="dms-btn dms-btn-outline">
                    <i class="bi bi-save"></i>
                    Simpan Hitung Fisik
                </button>
            </div>
        @endif
    </form>

    @if($stockOpname->isDraft())
        <form action="{{ route('stock-opnames.complete', $stockOpname) }}" method="POST" class="dms-form-actions" style="margin-top: 0.75rem;">
            @csrf
            <button type="submit" class="dms-btn dms-btn-primary" onclick="return confirm('Selesaikan stock opname dan sesuaikan stok sistem?')">
                <i class="bi bi-check-circle"></i>
                Selesaikan Opname
            </button>
        </form>
    @endif
</div>
@endsection
