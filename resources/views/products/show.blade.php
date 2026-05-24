@extends('layouts.sidebar')

@section('page-title', 'Detail Produk')
@section('breadcrumb', 'Products / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Detail Produk</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">Informasi lengkap produk</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('products.edit', $product) }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('products.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
        <!-- Left Column - Image & Basic Info -->
        <div>
            <div style="text-align: center; padding: 2rem; background: var(--k-gray-50); border-radius: 12px; border: 1px solid var(--k-gray-200);">
                <!-- Product Image -->
                <div style="width: 200px; height: 200px; margin: 0 auto 1.5rem; border-radius: 12px; overflow: hidden; border: 4px solid white; box-shadow: var(--k-shadow-md);">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div style="width: 100%; height: 100%; background: var(--k-green-light); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-box-seam" style="font-size: 4rem; color: var(--k-green);"></i>
                        </div>
                    @endif
                </div>
                
                <!-- Name & Status -->
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.5rem;">{{ $product->name }}</h3>
                
                <!-- Status -->
                <div style="margin-bottom: 1rem;">
                    <span class="dms-badge {{ $product->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}" style="font-size: 0.9rem; padding: 0.5rem 1.5rem;">
                        {{ $product->is_active ? 'AKTIF' : 'TIDAK AKTIF' }}
                    </span>
                </div>
                
                <!-- Category & Unit -->
                <div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 1rem;">
                    @if($product->category)
                        <span class="dms-badge dms-badge-info">{{ $product->category }}</span>
                    @endif
                    @if($product->unit)
                        <span class="dms-badge dms-badge-info">
                            {{ $product->unit->name }}
                            @if($product->unit->symbol)
                                ({{ $product->unit->symbol }})
                            @endif
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column - Detailed Info -->
        <div>
            <!-- Pricing Information -->
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-currency-dollar" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Informasi Harga
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div style="padding: 1rem; background: var(--k-green-light); border-radius: 8px;">
                        <div style="font-size: 0.75rem; color: var(--k-gray-600); margin-bottom: 0.25rem;">Harga Jual</div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--k-green);">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">per {{ $product->unit->name ?? 'unit' }}</div>
                    </div>
                    
                    @if($product->base_price)
                    <div style="padding: 1rem; background: var(--k-gray-100); border-radius: 8px;">
                        <div style="font-size: 0.75rem; color: var(--k-gray-600); margin-bottom: 0.25rem;">Harga Beli (Pasar)</div>
                        <div style="font-size: 1.5rem; font-weight: 600; color: var(--k-gray-700);">Rp {{ number_format($product->base_price, 0, ',', '.') }}</div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">per {{ $product->unit->name ?? 'unit' }}</div>
                    </div>
                    
                    <div style="grid-column: span 2; padding: 1rem; background: var(--k-gray-100); border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.75rem; color: var(--k-gray-600);">Margin per Unit</div>
                                <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-green);">
                                    Rp {{ number_format($product->price - $product->base_price, 0, ',', '.') }}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: var(--k-gray-600);">Persentase Margin</div>
                                <div style="font-size: 1.2rem; font-weight: 600; color: var(--k-green);">
                                    {{ round(($product->price - $product->base_price) / $product->price * 100) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Basic Information -->
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-info-circle" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Informasi Dasar
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Kategori</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $product->category ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Satuan</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">
                            @if($product->unit)
                                {{ $product->unit->name }}
                                @if($product->unit->symbol)
                                    <span style="color: var(--k-gray-500);">({{ $product->unit->symbol }})</span>
                                @endif
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Description -->
            @if($product->description)
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-file-text" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Deskripsi Produk
                </h4>
                <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
                    <p style="color: var(--k-gray-700); line-height: 1.6;">{{ $product->description }}</p>
                </div>
            </div>
            @endif

            <!-- System Information -->
            <div>
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-gear" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Informasi Sistem
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Dibuat Pada</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $product->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Terakhir Diupdate</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $product->updated_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons (Bottom) -->
    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--k-gray-200);">
        <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk {{ $product->name }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="dms-btn" style="background: #fef2f2; color: var(--k-red); border: 1px solid #fee2e2;">
                <i class="bi bi-trash"></i> Hapus Produk
            </button>
        </form>
        <a href="{{ route('products.edit', $product) }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-pencil"></i> Edit Produk
        </a>
    </div>
</div>
@endsection