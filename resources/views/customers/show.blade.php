@extends('layouts.sidebar')

@section('page-title', 'Detail Pelanggan')
@section('breadcrumb', 'Pelanggan / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-person-badge" style="color: var(--k-green);"></i>
                Detail Pelanggan
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Informasi lengkap pelanggan
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            @can('edit customers')
            <a href="{{ route('customers.edit', $customer) }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            <a href="{{ route('customers.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
        <!-- Left Column - Photo & Basic Info -->
        <div>
            <div style="text-align: center; padding: 2rem; background: var(--k-gray-50); border-radius: 12px; border: 1px solid var(--k-gray-200);">
                <!-- Photo -->
                <div style="width: 120px; height: 120px; margin: 0 auto 1.5rem; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: var(--k-shadow-md); background: var(--k-green-light);">
                    @if($customer->photo)
                        <img src="{{ asset('storage/' . $customer->photo) }}" alt="{{ $customer->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-person-circle" style="font-size: 4rem; color: var(--k-green);"></i>
                        </div>
                    @endif
                </div>
                
                <!-- Name & Type -->
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 0.5rem;">{{ $customer->name }}</h3>
                <div style="margin-bottom: 1rem;">
                    <span class="dms-badge {{ $customer->customer_type_badge }}">
                        {{ $customer->customer_type_label }}
                    </span>
                    <span class="dms-badge {{ $customer->payment_term_badge }}" style="margin-left: 0.35rem;">
                        {{ $customer->payment_term_label }}
                    </span>
                    <span class="dms-badge {{ $customer->credit_status_badge }}" style="margin-left: 0.35rem;">
                        {{ $customer->credit_status_label }}
                    </span>
                </div>
                
                <!-- Status -->
                <div style="margin-bottom: 1rem;">
                    <span class="dms-badge {{ $customer->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}" style="font-size: 0.9rem; padding: 0.5rem 1.5rem;">
                        {{ $customer->is_active ? 'AKTIF' : 'TIDAK AKTIF' }}
                    </span>
                </div>
                
                <!-- Stats -->
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--k-gray-200);">
                    <div style="text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--k-green);">{{ number_format($totalOrders ?? 0) }}</div>
                        <div style="font-size: 0.65rem; color: var(--k-gray-500);">Total Orders</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--k-green);">Rp {{ number_format($totalSpent ?? 0, 0, ',', '.') }}</div>
                        <div style="font-size: 0.65rem; color: var(--k-gray-500);">Total Belanja</div>
                    </div>
                </div>
            </div>

            <!-- Wallet Section -->
            <div style="margin-top: 1rem; padding: 1rem; background: var(--k-green-light); border-radius: 12px; text-align: center;">
                <div style="font-size: 0.7rem; color: var(--k-gray-600);">Saldo Wallet</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--k-green);">
                    Rp {{ number_format($customer->user?->wallet?->balance ?? 0, 0, ',', '.') }}
                </div>
                @can('process payment')
                <button onclick="showTopupModal({{ $customer->id }})" class="dms-btn dms-btn-primary" style="margin-top: 0.5rem; padding: 0.3rem 1rem;">
                    <i class="bi bi-plus-circle"></i> Topup
                </button>
                @endcan
            </div>

            <div style="margin-top: 1rem; padding: 1rem; background: var(--k-gray-50); border-radius: 12px; border: 1px solid var(--k-gray-200);">
                <div style="font-size: 0.75rem; color: var(--k-gray-500); margin-bottom: 0.75rem;">Termin & Kontrol Kredit</div>
                <div style="display: grid; gap: 0.75rem;">
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Termin Pembayaran</div>
                        <div style="font-weight: 700; color: var(--k-gray-900);">{{ $customer->payment_term_label }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Credit Limit</div>
                        <div style="font-weight: 700; color: var(--k-gray-900);">
                            {{ $customer->usesCreditTerm() ? $customer->formatted_credit_limit : 'Tidak berlaku untuk tunai' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Outstanding Aktif</div>
                        <div style="font-weight: 700; color: var(--k-gray-900);">Rp {{ number_format($customer->outstandingAmount(), 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500);">Order Aktif</div>
                        <div style="font-weight: 700; color: var(--k-gray-900);">
                            {{ number_format($customer->outstandingOrdersCount()) }}
                            @if(($customer->max_outstanding_orders ?? 0) > 0)
                                / {{ number_format($customer->max_outstanding_orders) }}
                            @endif
                        </div>
                    </div>
                    @if($customer->credit_notes)
                        <div style="padding-top: 0.75rem; border-top: 1px solid var(--k-gray-200); color: var(--k-gray-600); font-size: 0.8rem; line-height: 1.5;">
                            {{ $customer->credit_notes }}
                        </div>
                    @endif
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
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $customer->phone }}</div>
                    </div>
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Email</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $customer->email ?? '-' }}</div>
                    </div>
                    <div style="grid-column: span 2; padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Alamat</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $customer->address ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Customer Addresses -->
            <div id="customer-addresses" style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-geo-alt" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Master Alamat Pelanggan
                </h4>
                <p style="font-size: 0.78rem; color: var(--k-gray-500); margin: -0.5rem 0 1rem;">
                    Kelola alamat invoice/dokumen dan alamat pengiriman. Tambahkan alamat baru di sini sebelum dipakai saat membuat order.
                </p>

                @can('edit customers')
                <form action="{{ route('customers.addresses.store', $customer) }}" method="POST" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid var(--k-gray-200); border-radius: 8px; background: var(--k-gray-50);">
                    @csrf
                    <div style="font-size: 0.9rem; font-weight: 700; color: var(--k-gray-900); margin-bottom: 0.75rem;">
                        <i class="bi bi-plus-circle" style="color: var(--k-green); margin-right: 0.35rem;"></i>
                        Tambah Alamat Pengiriman / Invoice
                    </div>
                    <div class="dms-form-grid">
                        <div>
                            <label class="form-label">Label Alamat <span class="dms-required">*</span></label>
                            <input type="text" name="label" class="form-control" required placeholder="Contoh: Kantor Pusat, Gudang Cakung">
                        </div>
                        <div>
                            <label class="form-label">Tipe Alamat <span class="dms-required">*</span></label>
                            <select name="type" class="form-control" required>
                                <option value="shipping">Pengiriman</option>
                                <option value="invoice">Invoice / Dokumen</option>
                                <option value="both">Invoice & Pengiriman</option>
                            </select>
                        </div>
                        <div class="dms-form-span-2">
                            <label class="form-label">Alamat Lengkap <span class="dms-required">*</span></label>
                            <textarea name="address" class="form-control" rows="2" required placeholder="Alamat lengkap customer"></textarea>
                        </div>
                        <div>
                            <label class="form-label">PIC Penerima</label>
                            <input type="text" name="recipient_name" class="form-control" placeholder="{{ $customer->name }}">
                        </div>
                        <div>
                            <label class="form-label">Telepon Penerima</label>
                            <input type="text" name="recipient_phone" class="form-control" placeholder="{{ $customer->phone }}">
                        </div>
                        <div>
                            <label class="form-label">Latitude</label>
                            <input type="text" name="latitude" class="form-control" placeholder="-6.200000">
                        </div>
                        <div>
                            <label class="form-label">Longitude</label>
                            <input type="text" name="longitude" class="form-control" placeholder="106.816666">
                        </div>
                        <div class="dms-form-span-2" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <label class="dms-check">
                                <input type="checkbox" name="is_default_invoice" value="1">
                                <span>Default invoice</span>
                            </label>
                            <label class="dms-check">
                                <input type="checkbox" name="is_default_shipping" value="1">
                                <span>Default pengiriman</span>
                            </label>
                            <input type="hidden" name="is_active" value="1">
                            <button type="submit" class="dms-btn dms-btn-primary">
                                <i class="bi bi-plus-circle"></i> Tambah Alamat
                            </button>
                        </div>
                    </div>
                </form>
                @endcan

                <div class="dms-table-wrap">
                    <table class="dms-table">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Tipe</th>
                                <th>Alamat</th>
                                <th>Default</th>
                                <th style="width: 96px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->addresses as $address)
                            <tr>
                                <td>
                                    <strong>{{ $address->label }}</strong>
                                    @if($address->recipient_name || $address->recipient_phone)
                                        <div style="font-size: 0.72rem; color: var(--k-gray-500);">{{ $address->recipient_name }} {{ $address->recipient_phone ? '- ' . $address->recipient_phone : '' }}</div>
                                    @endif
                                </td>
                                <td>{{ $address->type_label }}</td>
                                <td>{{ $address->address }}</td>
                                <td>
                                    @if($address->is_default_invoice)
                                        <span class="dms-badge dms-badge-info">Invoice</span>
                                    @endif
                                    @if($address->is_default_shipping)
                                        <span class="dms-badge dms-badge-success">Pengiriman</span>
                                    @endif
                                    @unless($address->is_active)
                                        <span class="dms-badge dms-badge-danger">Nonaktif</span>
                                    @endunless
                                </td>
                                <td>
                                    @can('edit customers')
                                    <form action="{{ route('customers.addresses.destroy', [$customer, $address]) }}" method="POST" onsubmit="return confirm('Hapus alamat ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dms-btn dms-btn-outline" style="padding: 0.25rem 0.55rem; color: var(--k-red);">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--k-gray-500);">Belum ada alamat customer.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Last Order Info -->
            @if($lastOrder)
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-clock-history" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Order Terakhir
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">No. Order</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $lastOrder->order_number }}</div>
                    </div>
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Tanggal</div>
                        <div style="font-weight: 600; color: var(--k-gray-800);">{{ $lastOrder->created_at->format('d M Y') }}</div>
                    </div>
                    <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Total</div>
                        <div style="font-weight: 600; color: var(--k-green);">Rp {{ number_format($lastOrder->total, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Notes -->
            @if($customer->notes)
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                    <i class="bi bi-chat-dots" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                    Catatan
                </h4>
                <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
                    <p style="color: var(--k-gray-700); line-height: 1.6;">{{ $customer->notes }}</p>
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
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Bergabung Sejak</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $customer->created_at->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">Terakhir Update</div>
                        <div style="font-weight: 500; color: var(--k-gray-800);">{{ $customer->updated_at->format('d M Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons (Bottom) -->
    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--k-gray-200);">
        <a href="{{ route('customers.order-history', $customer) }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-clock-history"></i> Lihat Riwayat Order
        </a>
        @can('delete customers')
        @if($customer->orders()->count() == 0)
        <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pelanggan {{ $customer->name }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="dms-btn" style="background: #fef2f2; color: var(--k-red); border: 1px solid #fee2e2;">
                <i class="bi bi-trash"></i> Hapus Pelanggan
            </button>
        </form>
        @endif
        @endcan
        @can('edit customers')
        <a href="{{ route('customers.edit', $customer) }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-pencil"></i> Edit Pelanggan
        </a>
        @endcan
    </div>
</div>

<!-- Topup Modal -->
@can('process payment')
<div id="topupModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; width: 400px; max-width: 90%;">
        <h3 style="margin-bottom: 1rem;">Topup Saldo</h3>
        <form id="topupForm" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Jumlah Topup</label>
                <input type="number" name="amount" class="form-control" required placeholder="Minimal Rp 10.000">
                <small>Minimal Rp 10.000</small>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan (Opsional)</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Topup via admin"></textarea>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" onclick="closeTopupModal()" class="dms-btn dms-btn-outline">Batal</button>
                <button type="submit" class="dms-btn dms-btn-primary">Topup</button>
            </div>
        </form>
    </div>
</div>
@endcan

<script>
function showTopupModal(customerId) {
    const modal = document.getElementById('topupModal');
    const form = document.getElementById('topupForm');
    form.action = `/customers/${customerId}/topup-wallet`;
    modal.style.display = 'flex';
}

function closeTopupModal() {
    document.getElementById('topupModal').style.display = 'none';
}
</script>

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
}
</style>
@endsection
