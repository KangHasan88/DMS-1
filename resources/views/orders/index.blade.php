@extends('layouts.sidebar')

@section('page-title', 'Pesanan Penjualan')
@section('breadcrumb', 'Operasional / Pesanan Penjualan')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Pesanan</h3>
            <p class="dms-section-subtitle">Kelola pesanan pelanggan dari pembayaran, pemenuhan, sampai pengiriman.</p>
        </div>
        @can('create sales order')
        <a href="{{ route('orders.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Order
        </a>
        @endcan
    </div>

    <!-- Search Form -->
    <div class="dms-order-filter-panel">
        <form action="{{ route('orders.index') }}" method="GET" class="dms-order-filter-grid">
            <div class="dms-filter-control dms-filter-control-wide">
                <label class="form-label">Cari Order</label>
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="No order, pelanggan, telepon..."
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
            </div>

            @if($canFilterBranches)
            <div class="dms-filter-control">
                <label class="form-label">Cabang</label>
                <select onchange="window.location.href = this.value" class="form-control">
                    <option value="{{ route('orders.index', array_merge(request()->except('company_branch_id', 'page'), ['company_branch_id' => null])) }}" {{ !request('company_branch_id') ? 'selected' : '' }}>Semua Cabang</option>
                    @foreach($companyBranches as $branch)
                    <option value="{{ route('orders.index', array_merge(request()->except('company_branch_id', 'page'), ['company_branch_id' => $branch->id])) }}" {{ request('company_branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}{{ $branch->code ? ' - '.$branch->code : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="dms-filter-control">
                <label class="form-label">Skema Bayar</label>
                <select onchange="window.location.href = this.value" class="form-control">
                    <option value="{{ route('orders.index', array_merge(request()->except('payment_timing', 'page'), ['payment_timing' => null])) }}" {{ !request('payment_timing') ? 'selected' : '' }}>Semua Skema</option>
                    <option value="{{ route('orders.index', array_merge(request()->except('payment_timing', 'page'), ['payment_timing' => 'pre_paid'])) }}" {{ request('payment_timing') == 'pre_paid' ? 'selected' : '' }}>Pre-paid</option>
                    <option value="{{ route('orders.index', array_merge(request()->except('payment_timing', 'page'), ['payment_timing' => 'post_paid'])) }}" {{ request('payment_timing') == 'post_paid' ? 'selected' : '' }}>Post-paid</option>
                </select>
            </div>

            <div class="dms-filter-control dms-filter-control-small">
                <label class="form-label">Per Halaman</label>
                <select onchange="window.location.href = this.value" class="form-control">
                    <option value="{{ route('orders.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 data</option>
                    <option value="{{ route('orders.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 data</option>
                    <option value="{{ route('orders.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 data</option>
                    <option value="{{ route('orders.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 data</option>
                </select>
            </div>

            <div class="dms-order-filter-actions">
                <button type="button" onclick="toggleAdvancedSearch()" class="dms-btn dms-btn-outline">
                    <i class="bi bi-sliders2"></i> Advanced
                </button>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </div>
        </form>
    </div>

    <!-- Advanced Search Form -->
    <div id="advancedSearch" style="display: none; margin-bottom: 1.5rem; padding: 1rem; background: var(--k-gray-50); border-radius: 8px;">
        <form action="{{ route('orders.index') }}" method="GET" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
            <input type="hidden" name="search" value="{{ request('search') }}">
            
            <div>
                <label class="form-label">Nama Pelanggan</label>
                <input type="text" name="customer_name" class="form-control" value="{{ request('customer_name') }}" placeholder="Nama pelanggan">
            </div>
            
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">-- Semua Status --</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            
            <div>
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            
            <div>
                <label class="form-label">Sumber Order</label>
                <select name="order_source" class="form-control">
                    <option value="">-- Semua --</option>
                    @foreach($orderSources as $key => $label)
                        <option value="{{ $key }}" {{ request('order_source') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label">Sales Owner</label>
                <select name="salesperson_id" class="form-control">
                    <option value="">-- Semua --</option>
                    @foreach($salespeople as $salesperson)
                        <option value="{{ $salesperson->id }}" {{ request('salesperson_id') == $salesperson->id ? 'selected' : '' }}>
                            {{ $salesperson->name }}{{ $salesperson->companyBranch?->code ? ' - '.$salesperson->companyBranch->code : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="form-label">Mode Pemenuhan</label>
                <select name="fulfillment_type" class="form-control">
                    <option value="">-- Semua --</option>
                    @foreach($fulfillmentTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('fulfillment_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div style="grid-column: span 2; display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
                <a href="{{ route('orders.index') }}" class="dms-btn dms-btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="dms-table-wrap orders-table-wrap">
        <table class="dms-table orders-table">
            <thead>
                <tr>
                    <th class="col-order">No. Order</th>
                    <th>Pelanggan</th>
                    <th class="col-date">Tanggal</th>
                    <th style="text-align: right;">Total</th>
                    <th class="col-attribute">Sumber</th>
                    <th class="col-sales-owner">Sales Owner</th>
                    <th class="col-attribute">Mode</th>
                    <th class="col-attribute">Skema</th>
                    <th>Status</th>
                    <th class="col-actions">Aksi</th>
                 </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="order-number">{{ $order->order_number }}</a>
                    </td>
                    <td>
                        <div class="order-customer">
                            <span class="dms-strong">{{ $order->user->name ?? '-' }}</span>
                            <span class="dms-muted">{{ $order->user->phone ?? '-' }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="order-date">
                            <span>{{ $order->created_at->format('d M Y') }}</span>
                            <span class="dms-muted">{{ $order->created_at->format('H:i') }}</span>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <span class="dms-money">
                            Rp {{ number_format($order->total, 0, ',', '.') }}
                        </span>
                    </td>
                    <td>
                        <span class="order-attribute">
                            {{ $order->order_source_label }}
                        </span>
                    </td>
                    <td>
                        <div class="order-sales-owner">
                            <span>{{ $order->salesperson->name ?? 'House Account' }}</span>
                            @if($order->createdBy)
                                <span class="dms-muted">Input: {{ $order->createdBy->name }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="order-attribute {{ $order->fulfillment_type == 'stock' ? 'is-stock' : 'is-blj' }}">
                            {{ $order->fulfillment_type == 'stock' ? 'Stock' : 'BLJ' }}
                        </span>
                    </td>
                    <td>
                        <span class="order-payment-attribute {{ $order->payment_timing == 'pre_paid' ? 'is-prepaid' : 'is-postpaid' }}" title="{{ $order->payment_timing == 'pre_paid' ? 'Pre-paid' : 'Post-paid' }}" aria-label="{{ $order->payment_timing == 'pre_paid' ? 'Pre-paid' : 'Post-paid' }}">
                            {{ $order->payment_timing == 'pre_paid' ? 'Pre-paid' : 'Post-paid' }}
                        </span>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-{{ $order->status_color }} order-status">
                            {{ $order->status_label }}
                        </span>
                    </td>
                    <td class="col-actions">
                        <div class="dms-actions">
                            @can('edit sales order')
                            @if($order->canEditOrder())
                            <a href="{{ route('orders.edit', $order) }}" class="dms-btn dms-btn-outline dms-btn-sm" style="text-decoration: none;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @endcan
                            @can('delete sales order')
                            @if($order->canDeleteOrder())
                            <button onclick="deleteOrder({{ $order->id }}, '{{ $order->order_number }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">
                        <div class="dms-empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>Tidak ada data order</p>
                        @can('create sales order')
                        <a href="{{ route('orders.create') }}" class="dms-btn dms-btn-primary">
                            <i class="bi bi-plus-circle"></i> Buat Order Pertama
                        </a>
                        @endcan
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} order
        </div>
        <div>
            {{ $orders->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
@can('delete sales order')
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endcan

<script>
function toggleAdvancedSearch() {
    const advSearch = document.getElementById('advancedSearch');
    advSearch.style.display = advSearch.style.display === 'none' ? 'block' : 'none';
}

function deleteOrder(orderId, orderNumber) {
    if (!confirm(`Apakah Anda yakin ingin menghapus order "${orderNumber}"?`)) {
        return;
    }
    
    const form = document.getElementById('delete-form');
    form.action = `/orders/${orderId}`;
    form.submit();
}
</script>

<style>
.dms-order-filter-panel {
    margin-bottom: 1.25rem;
    padding: 1rem;
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    background: var(--k-gray-50);
}

.dms-order-filter-grid {
    display: grid;
    grid-template-columns: minmax(260px, 1.8fr) minmax(170px, 0.85fr) minmax(160px, 0.75fr) minmax(130px, 0.55fr) auto;
    gap: 0.8rem;
    align-items: end;
}

.dms-filter-control {
    min-width: 0;
}

.dms-order-filter-panel .form-label {
    margin-bottom: 0.35rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--k-gray-700);
}

.dms-order-filter-panel .form-control {
    width: 100%;
    min-height: 46px;
}

.dms-order-filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.55rem;
    white-space: nowrap;
}

.dms-order-filter-actions .dms-btn {
    min-height: 46px;
}

.orders-table-wrap {
    border-radius: 8px;
}

.orders-table {
    table-layout: fixed;
}

.orders-table th {
    padding: 0.62rem 0.75rem;
    font-size: 0.68rem;
    letter-spacing: 0;
}

.orders-table td {
    padding: 0.58rem 0.75rem;
    vertical-align: middle;
}

.orders-table .col-order {
    width: 150px;
}

.orders-table .col-date {
    width: 120px;
}

.orders-table .col-attribute {
    width: 96px;
}

.orders-table .col-sales-owner {
    width: 138px;
}

.order-payment-attribute {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 74px;
    min-height: 26px;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    border: 1px solid var(--k-gray-200);
    background: var(--k-gray-50);
    color: var(--k-gray-700);
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0;
    line-height: 1;
    white-space: nowrap;
}

.order-payment-attribute.is-prepaid {
    background: #fff7ed;
    color: #c2410c;
    border-color: #fed7aa;
}

.order-payment-attribute.is-postpaid {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

.orders-table .col-actions {
    width: 96px;
    text-align: center;
}

.order-number {
    display: inline-block;
    font-family: inherit;
    font-size: 0.76rem;
    font-weight: 600;
    letter-spacing: 0;
    color: var(--k-blue);
    line-height: 1.2;
    text-decoration: none;
}

.order-number:hover {
    color: var(--k-blue-darker);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.order-customer,
.order-date,
.order-sales-owner {
    display: flex;
    flex-direction: column;
    gap: 0.12rem;
    min-width: 0;
}

.order-sales-owner span:first-child {
    color: var(--k-gray-800);
    font-size: 0.72rem;
    font-weight: 600;
    line-height: 1.2;
}

.order-customer .dms-strong {
    line-height: 1.2;
}

.order-date span:first-child {
    color: var(--k-gray-700);
    font-size: 0.74rem;
    line-height: 1.2;
}

.order-attribute {
    display: inline-flex;
    align-items: center;
    min-height: 22px;
    padding: 0.16rem 0.45rem;
    border-radius: 999px;
    background: var(--k-gray-100);
    color: var(--k-gray-600);
    font-size: 0.68rem;
    font-weight: 600;
    line-height: 1;
}

.order-attribute.is-stock {
    background: #fff7ed;
    color: #c2410c;
}

.order-attribute.is-blj {
    background: #f1f5f9;
    color: var(--k-gray-700);
}

.order-status {
    font-size: 0.68rem;
    min-height: 22px;
    padding: 0.18rem 0.55rem;
}

.orders-table .dms-actions {
    justify-content: center;
    gap: 0.38rem;
}

.orders-table .dms-btn-sm {
    width: 32px;
    height: 32px;
    min-width: 32px;
    padding: 0;
}

@media (max-width: 1180px) {
    .dms-order-filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dms-filter-control-wide,
    .dms-order-filter-actions {
        grid-column: 1 / -1;
    }

    .dms-order-filter-actions {
        justify-content: flex-end;
    }

    .orders-table .col-sales-owner,
    .orders-table .col-attribute:nth-of-type(5) {
        display: none;
    }
}

@media (max-width: 720px) {
    .dms-order-filter-grid {
        grid-template-columns: 1fr;
    }

    .dms-order-filter-actions {
        flex-direction: column-reverse;
    }

    .dms-order-filter-actions .dms-btn {
        width: 100%;
    }
}
</style>

@endsection
