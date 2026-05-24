@extends('layouts.sidebar')

@section('page-title', 'Detail Satuan')
@section('breadcrumb', 'Master / Satuan / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-rulers" style="color: var(--k-green);"></i>
                Detail Satuan
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Informasi lengkap satuan produk
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('units.edit', $unit) }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('units.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
        <!-- Left Column - Info Card -->
        <div>
            <div style="text-align: center; padding: 2rem; background: var(--k-gray-50); border-radius: 12px; border: 1px solid var(--k-gray-200);">
                <!-- Icon -->
                <div style="width: 80px; height: 80px; background: var(--k-green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="bi bi-rulers" style="font-size: 2.5rem; color: var(--k-green);"></i>
                </div>
                
                <!-- Name & Code -->
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.5rem;">
                    {{ $unit->name }}
                </h3>
                <div style="margin-bottom: 1rem;">
                    <code style="background: var(--k-gray-200); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">
                        {{ $unit->code }}
                    </code>
                </div>
                
                <!-- Status -->
                <div style="margin-bottom: 1rem;">
                    <span class="dms-badge {{ $unit->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}" style="font-size: 0.9rem; padding: 0.5rem 1.5rem;">
                        {{ $unit->is_active ? 'AKTIF' : 'TIDAK AKTIF' }}
                    </span>
                </div>
                
                <!-- Stats -->
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--k-green);">{{ $productsCount ?? 0 }}</div>
                        <div style="font-size: 0.65rem; color: var(--k-gray-500);">Produk Terkait</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Detailed Info -->
        <div>
            <!-- Basic Information -->
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-info-circle" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Informasi Dasar
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Nama Satuan</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $unit->name }}</div>
                    </div>
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Kode Satuan</div>
                        <div><code style="background: var(--k-gray-200); padding: 0.2rem 0.5rem; border-radius: 4px;">{{ $unit->code }}</code></div>
                    </div>
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Simbol</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $unit->symbol ?: '-' }}</div>
                    </div>
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Kategori</div>
                        <div><span class="dms-badge dms-badge-info">{{ $unit->category ?: '-' }}</span></div>
                    </div>
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Urutan</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $unit->sort_order }}</div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            @if($unit->description)
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-file-text" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Deskripsi
                </h4>
                <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
                    <p style="color: var(--k-gray-700); line-height: 1.6;">{{ $unit->description }}</p>
                </div>
            </div>
            @endif

            <!-- Products Using This Unit -->
            @if(isset($products) && $products->count() > 0)
            <div>
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-box-seam" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Produk dengan Satuan Ini
                </h4>
                <div style="overflow-x: auto;">
                    <table class="dms-table">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga Jual</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $product) }}" style="color: var(--k-green); text-decoration: none;">
                                        {{ $product->name }}
                                    </a>
                                </td>
                                <td>{{ $product->category ?? '-' }}</td>
                                <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                <td>
                                    <span class="dms-badge {{ $product->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                                        {{ $product->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(isset($products) && $products->hasPages())
                <div style="margin-top: 1rem;">
                    {{ $products->links() }}
                </div>
                @endif
            </div>
            @endif

            <!-- System Information -->
            <div style="margin-top: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-gear" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Informasi Sistem
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Dibuat Pada</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $unit->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Terakhir Diupdate</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $unit->updated_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons (Bottom) -->
    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--k-gray-200);">
        @if(($productsCount ?? 0) == 0)
        <form action="{{ route('units.destroy', $unit) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus satuan {{ $unit->name }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="dms-btn" style="background: #fef2f2; color: var(--k-red); border: 1px solid #fee2e2;">
                <i class="bi bi-trash"></i> Hapus Satuan
            </button>
        </form>
        @else
        <div style="padding: 0.5rem 1rem; background: var(--k-gray-100); border-radius: 8px; color: var(--k-gray-500);">
            <i class="bi bi-info-circle"></i> Tidak dapat dihapus karena digunakan oleh {{ $productsCount }} produk
        </div>
        @endif
        <a href="{{ route('units.edit', $unit) }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-pencil"></i> Edit Satuan
        </a>
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