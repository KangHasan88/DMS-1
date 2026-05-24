@extends('layouts.sidebar')

@section('page-title', 'History Harga Produk')
@section('breadcrumb', 'Products / History')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-clock-history" style="color: var(--k-green);"></i>
                History Perubahan Harga
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Produk: <strong>{{ $product->name }}</strong> 
                <span style="color: var(--k-gray-400);">|</span> 
                Satuan: 
                @if($product->unit)
                    {{ $product->unit->name }}
                    @if($product->unit->symbol)
                        ({{ $product->unit->symbol }})
                    @endif
                @else
                    -
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('products.edit', $product) }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-pencil"></i> Edit Produk
            </a>
            <a href="{{ route('products.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Current Price Info -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: var(--k-green-light); padding: 1rem; border-radius: 12px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Harga Jual Saat Ini</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--k-green);">
                Rp {{ number_format($product->price, 0, ',', '.') }}
            </div>
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">per {{ $product->unit->name ?? 'unit' }}</div>
        </div>
        
        @if($product->base_price)
        <div style="background: var(--k-gray-100); padding: 1rem; border-radius: 12px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-600);">Harga Beli Saat Ini (Pasar)</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--k-gray-700);">
                Rp {{ number_format($product->base_price, 0, ',', '.') }}
            </div>
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">per {{ $product->unit->name ?? 'unit' }}</div>
        </div>
        @endif
    </div>

    <!-- Price History Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                 <tr>
                    <th style="width: 120px;">Tanggal</th>
                    <th style="width: 150px;">User</th>
                    <th>Harga Lama</th>
                    <th>Harga Baru</th>
                    <th>Perubahan</th>
                    <th>Alasan</th>
                 </tr>
            </thead>
            <tbody>
                @forelse($priceHistories as $history)
                 <tr>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem;">{{ $history->created_at->format('d M Y') }}</span>
                            <span style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $history->created_at->format('H:i') }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 28px; height: 28px; background: var(--k-gray-100); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-circle" style="color: var(--k-gray-500);"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; font-weight: 500;">{{ $history->user->name ?? 'System' }}</div>
                                <div style="font-size: 0.6rem; color: var(--k-gray-500);">{{ $history->user->email ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($history->old_price)
                            <div style="text-decoration: line-through; color: var(--k-gray-500);">
                                Rp {{ number_format($history->old_price, 0, ',', '.') }}
                            </div>
                        @else
                            <span style="color: var(--k-gray-500);">-</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--k-green);">
                            Rp {{ number_format($history->new_price, 0, ',', '.') }}
                        </div>
                    </td>
                    <td>
                        @php
                            $diff = null;
                            $percent = null;
                            if ($history->old_price && $history->new_price) {
                                $diff = $history->new_price - $history->old_price;
                                $percent = $history->old_price > 0 ? round(($diff / $history->old_price) * 100, 2) : 0;
                            }
                        @endphp
                        @if($diff > 0)
                            <span style="color: #16a34a;">
                                <i class="bi bi-arrow-up"></i> +Rp {{ number_format($diff, 0, ',', '.') }}
                                <span style="font-size: 0.65rem;">({{ $percent }}%)</span>
                            </span>
                        @elseif($diff < 0)
                            <span style="color: #dc2626;">
                                <i class="bi bi-arrow-down"></i> Rp {{ number_format(abs($diff), 0, ',', '.') }}
                                <span style="font-size: 0.65rem;">({{ abs($percent) }}%)</span>
                            </span>
                        @else
                            <span style="color: var(--k-gray-500);">
                                @if($history->old_base_price != $history->new_base_price)
                                    <i class="bi bi-arrow-left-right"></i> Harga beli berubah
                                @else
                                    Tidak berubah
                                @endif
                            </span>
                        @endif
                        
                        @if($history->old_base_price != $history->new_base_price && $history->old_base_price && $history->new_base_price)
                            <div style="font-size: 0.6rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                                Harga beli: Rp {{ number_format($history->old_base_price, 0, ',', '.') }} 
                                ? Rp {{ number_format($history->new_base_price, 0, ',', '.') }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="max-width: 250px;">
                            @if($history->reason)
                                <div style="background: var(--k-gray-100); padding: 0.25rem 0.5rem; border-radius: 6px;">
                                    <i class="bi bi-chat-dots" style="font-size: 0.6rem; color: var(--k-gray-500);"></i>
                                    <span style="font-size: 0.7rem; color: var(--k-gray-600);">{{ $history->reason }}</span>
                                </div>
                            @else
                                <span style="color: var(--k-gray-400); font-size: 0.7rem;">-</span>
                            @endif
                        </div>
                    </td>
                 </tr>
                @empty
                 <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-clock-history" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Belum ada history perubahan harga</p>
                        <p style="font-size: 0.75rem; color: var(--k-gray-400);">Setiap perubahan harga akan tercatat di sini</p>
                    </td>
                 </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 1.5rem;">
        {{ $priceHistories->links() }}
    </div>
</div>

<style>
.pagination {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}
.pagination li {
    display: inline-block;
}
.pagination li a, .pagination li span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.5rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 8px;
    color: var(--k-gray-600);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.pagination li.active span {
    background: var(--k-green);
    color: white;
    border-color: var(--k-green);
}
.pagination li a:hover {
    background: var(--k-gray-100);
    border-color: var(--k-green);
}
.pagination .disabled span {
    background: var(--k-gray-100);
    color: var(--k-gray-400);
    border-color: var(--k-gray-200);
    cursor: not-allowed;
}
</style>
@endsection