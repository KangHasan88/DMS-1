@extends('layouts.sidebar')

@section('page-title', 'Customer Management')
@section('breadcrumb', 'Customers')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">Daftar Customer</h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500);">Kelola semua data customer KurmiGO</p>
        </div>
        @can('create customers')
        <a href="{{ route('customers.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Tambah Customer
        </a>
        @endcan
    </div>

    <!-- Search & Filter -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <form action="{{ route('customers.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--k-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari nama, email, telepon..." 
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter Type -->
            <select name="customer_type" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('customers.index', array_merge(request()->except('customer_type'), ['customer_type' => null])) }}">Semua Tipe</option>
                <option value="{{ route('customers.index', array_merge(request()->except('customer_type'), ['customer_type' => 'regular'])) }}" {{ request('customer_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                <option value="{{ route('customers.index', array_merge(request()->except('customer_type'), ['customer_type' => 'premium'])) }}" {{ request('customer_type') == 'premium' ? 'selected' : '' }}>Premium</option>
                <option value="{{ route('customers.index', array_merge(request()->except('customer_type'), ['customer_type' => 'wholesale'])) }}" {{ request('customer_type') == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
            </select>
            
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('customers.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('customers.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('customers.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            
            <!-- Per Page -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--k-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('customers.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('customers.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('customers.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('customers.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Customers Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                  <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Tipe</th>
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
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 40px; height: 40px; background: var(--k-green-light); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-circle" style="color: var(--k-green);"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--k-gray-800);">{{ $customer->name }}</div>
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
                    <td>{{ number_format($customer->total_orders) }}</td>
                    <td style="font-weight: 600; color: var(--k-green);">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</td>
                    <td>
                        <span class="dms-badge {{ $customer->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                            {{ $customer->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('customers.show', $customer) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('edit customers')
                            <a href="{{ route('customers.edit', $customer) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="toggleStatus({{ $customer->id }})" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Toggle Status">
                                <i class="bi bi-power"></i>
                            </button>
                            @endcan
                            @can('delete customers')
                            <button onclick="deleteCustomer({{ $customer->id }}, '{{ $customer->name }}')" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem; color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-people" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Tidak ada data customer</p>
                        @can('create customers')
                        <a href="{{ route('customers.create') }}" class="dms-btn dms-btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-circle"></i> Tambah Customer Pertama
                        </a>
                        @endcan
                    </td>
                  </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.9rem; color: var(--k-gray-600);">
            Menampilkan {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} dari {{ $customers->total() }} customer
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
    if (!confirm('Apakah Anda yakin ingin mengubah status customer ini?')) {
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
    if (!confirm(`Apakah Anda yakin ingin menghapus customer "${customerName}"?`)) {
        return;
    }
    
    const form = document.getElementById('delete-form');
    form.action = `/customers/${customerId}`;
    form.submit();
}
</script>

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
