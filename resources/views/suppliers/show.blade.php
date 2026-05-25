@extends('layouts.sidebar')

@section('page-title', 'Detail Pemasok')
@section('breadcrumb', 'Pemasok / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-shop" style="color: var(--k-green);"></i>
                Detail Pemasok
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Informasi lengkap pemasok dan pedagang
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            @can('edit suppliers')
            <a href="{{ route('suppliers.edit', $supplier) }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            <a href="{{ route('suppliers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
        <!-- Left Column - Photo & Basic Info -->
        <div>
            <div style="text-align: center; padding: 2rem; background: var(--k-gray-50); border-radius: 12px; border: 1px solid var(--k-gray-200);">
                <!-- Icon -->
                <div style="width: 100px; height: 100px; margin: 0 auto 1.5rem; background: var(--k-green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-shop" style="font-size: 3rem; color: var(--k-green);"></i>
                </div>
                
                <!-- Name & Category -->
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.5rem;">{{ $supplier->name }}</h3>
                <div style="margin-bottom: 1rem;">
                    <span class="dms-badge dms-badge-info">{{ $supplier->category_label }}</span>
                </div>
                
                <!-- Status -->
                <div style="margin-bottom: 1rem;">
                    <span class="dms-badge {{ $supplier->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}" style="font-size: 0.9rem; padding: 0.5rem 1.5rem;">
                        {{ $supplier->is_active ? 'AKTIF' : 'TIDAK AKTIF' }}
                    </span>
                </div>
                
                <!-- Stats -->
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
                    <div style="text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--k-green);">{{ number_format($totalTransactions ?? 0) }}</div>
                        <div style="font-size: 0.65rem; color: var(--k-gray-500);">Total Transaksi</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--k-green);">Rp {{ number_format($totalPurchase ?? 0, 0, ',', '.') }}</div>
                        <div style="font-size: 0.65rem; color: var(--k-gray-500);">Total Pembelian</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Detailed Info -->
        <div>
            <!-- Contact Information -->
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-telephone" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Informasi Kontak
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Nomor Telepon</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $supplier->phone }}</div>
                    </div>
                    @if($supplier->alternate_phone)
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Nomor Alternatif</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $supplier->alternate_phone }}</div>
                    </div>
                    @endif
                    @if($supplier->email)
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Email</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $supplier->email }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Location Information -->
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-geo-alt" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Informasi Lokasi
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    @if($supplier->market_name)
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Nama Pasar</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $supplier->market_name }}</div>
                    </div>
                    @endif
                    @if($supplier->stall_number)
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Nomor Lapak/Kios</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $supplier->stall_number }}</div>
                    </div>
                    @endif
                    @if($supplier->address)
                    <div style="grid-column: span 2; padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Alamat</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $supplier->address }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Business Information -->
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-briefcase" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Informasi Bisnis
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    @if($supplier->specialty)
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Spesialisasi</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $supplier->specialty }}</div>
                    </div>
                    @endif
                    @if($supplier->min_order > 0)
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Minimal Order</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">Rp {{ number_format($supplier->min_order, 0, ',', '.') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            @if($supplier->notes || $supplier->payment_notes)
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-chat-dots" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Catatan
                </h4>
                
                @if($supplier->notes)
                <div style="margin-bottom: 1rem; padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                    <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Catatan Umum</div>
                    <p style="color: var(--k-gray-700); line-height: 1.5;">{{ $supplier->notes }}</p>
                </div>
                @endif
                
                @if($supplier->payment_notes)
                <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                    <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Catatan Pembayaran</div>
                    <p style="color: var(--k-gray-700); line-height: 1.5;">{{ $supplier->payment_notes }}</p>
                </div>
                @endif
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
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Bergabung Sejak</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $supplier->created_at->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Terakhir Update</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $supplier->updated_at->format('d M Y') }}</div>
                    </div>
                    @if($lastPurchase)
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Terakhir Belanja</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ \Carbon\Carbon::parse($lastPurchase)->format('d M Y') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons (Bottom) -->
    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--k-gray-200);">
        @can('delete suppliers')
        @if($totalTransactions == 0)
        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pemasok {{ $supplier->name }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="dms-btn" style="background: #fef2f2; color: var(--k-red); border: 1px solid #fee2e2;">
                <i class="bi bi-trash"></i> Hapus Pemasok
            </button>
        </form>
        @endif
        @endcan
        @can('edit suppliers')
        <a href="{{ route('suppliers.edit', $supplier) }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-pencil"></i> Edit Pemasok
        </a>
        @endcan
    </div>
</div>
@endsection
