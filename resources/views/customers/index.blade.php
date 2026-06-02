@extends('layouts.sidebar')

@section('page-title', 'Pelanggan')
@section('breadcrumb', 'Pelanggan')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Pelanggan</h3>
            <p class="dms-section-subtitle">Kelola data pelanggan, status akun, wallet, dan riwayat pesanan.</p>
        </div>
        @can('create customers')
        <a href="{{ route('customers.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Tambah Pelanggan
        </a>
        @endcan
    </div>

    <!-- Search & Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('customers.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nama, email, telepon..." 
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <!-- Filter Type -->
            <select name="customer_type" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('customers.index', array_merge(request()->except('customer_type'), ['customer_type' => null])) }}">Semua Tipe</option>
                <option value="{{ route('customers.index', array_merge(request()->except('customer_type'), ['customer_type' => 'regular'])) }}" {{ request('customer_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                <option value="{{ route('customers.index', array_merge(request()->except('customer_type'), ['customer_type' => 'premium'])) }}" {{ request('customer_type') == 'premium' ? 'selected' : '' }}>Premium</option>
                <option value="{{ route('customers.index', array_merge(request()->except('customer_type'), ['customer_type' => 'wholesale'])) }}" {{ request('customer_type') == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
            </select>
            
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('customers.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('customers.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('customers.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>

            <select name="credit_status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('customers.index', array_merge(request()->except('credit_status'), ['credit_status' => null])) }}">Semua Kredit</option>
                <option value="{{ route('customers.index', array_merge(request()->except('credit_status'), ['credit_status' => 'normal'])) }}" {{ request('credit_status') == 'normal' ? 'selected' : '' }}>Normal</option>
                <option value="{{ route('customers.index', array_merge(request()->except('credit_status'), ['credit_status' => 'watchlist'])) }}" {{ request('credit_status') == 'watchlist' ? 'selected' : '' }}>Watchlist</option>
                <option value="{{ route('customers.index', array_merge(request()->except('credit_status'), ['credit_status' => 'blocked'])) }}" {{ request('credit_status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
            </select>

            <select name="payment_term" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('customers.index', array_merge(request()->except('payment_term'), ['payment_term' => null])) }}">Semua Termin</option>
                <option value="{{ route('customers.index', array_merge(request()->except('payment_term'), ['payment_term' => 'cash'])) }}" {{ request('payment_term') == 'cash' ? 'selected' : '' }}>Tunai</option>
                <option value="{{ route('customers.index', array_merge(request()->except('payment_term'), ['payment_term' => 'credit'])) }}" {{ request('payment_term') == 'credit' ? 'selected' : '' }}>Kredit</option>
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('customers.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('customers.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('customers.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('customers.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Pelanggan Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                  <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Tipe</th>
                    <th>Kredit</th>
                    <th>Total Order</th>
                    <th>Total Belanja</th>
                    <th>Status</th>
                    <th style="width: 150px;">Aksi</th>
                  </tr>
            </thead>
            <tbody>
                @forelse($customers as $index => $customer)
                  <tr>
                    <td>{{ $customers->firstItem() + $index }}</td>
                    <td>
                        <div class="dms-identity">
                            <div class="dms-avatar-soft">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div>
                                <div class="dms-strong">{{ $customer->name }}</div>
                                @if($customer->email)
                                    <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $customer->email }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.75rem;">{{ $customer->phone }}</span>
                            @if($customer->address)
                                <span style="font-size: 0.65rem; color: var(--k-gray-500);">{{ Str::limit($customer->address, 30) }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $customer->customer_type == 'premium' ? 'success' : ($customer->customer_type == 'wholesale' ? 'warning' : 'info') }}">
                            {{ ucfirst($customer->customer_type) }}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <span class="dms-badge {{ $customer->payment_term_badge }}">
                                {{ $customer->payment_term_label }}
                            </span>
                            <span class="dms-badge {{ $customer->credit_status_badge }}">
                                {{ $customer->credit_status_label }}
                            </span>
                            @if($customer->usesCreditTerm() && ($customer->credit_limit ?? 0) > 0)
                                <span style="font-size: 0.7rem; color: var(--k-gray-500);">Limit {{ $customer->formatted_credit_limit }}</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ number_format($customer->total_orders) }}</td>
                    <td class="dms-money">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</td>
                    <td>
                        <span class="dms-badge {{ $customer->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                            {{ $customer->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td>
                        <div class="dms-actions">
                            <a href="{{ route('customers.show', $customer) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit customers')
                            <a href="{{ route('customers.edit', $customer) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $customer->id }})" class="dms-btn dms-btn-outline dms-btn-sm" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            @endcan
                            @can('delete customers')
                            <button onclick="deleteCustomer({{ $customer->id }}, '{{ $customer->name }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-people" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data pelanggan</p>
                        @can('create customers')
                        <a href="{{ route('customers.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Pelanggan Pertama
                        </a>
                        @endcan
                    </td>
                  </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} dari {{ $customers->total() }} pelanggan
        </div>
        <div>
            {{ $customers->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
@can('delete customers')
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endcan

<script>
function toggleStatus(customerId) {
    if (!confirm('Apakah Anda yakin ingin mengubah status pelanggan ini?')) {
        return;
    }
    
    fetch(`/customers/${customerId}/toggle-status`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Gagal mengubah status');
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan');
    });
}

function deleteCustomer(customerId, customerName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus pelanggan "${customerName}"?`)) {
        return;
    }
    
    const form = document.getElementById('delete-form');
    form.action = `/customers/${customerId}`;
    form.submit();
}
</script>

@endsection
