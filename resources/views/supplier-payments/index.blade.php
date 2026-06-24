@extends('layouts.sidebar')

@section('page-title', 'Pembayaran Supplier')
@section('breadcrumb', 'Finance / Pembayaran Supplier')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Pembayaran Supplier</h3>
            <p class="dms-section-subtitle">Pantau pembayaran keluar dan saldo yang sudah dialokasikan ke invoice AP.</p>
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('supplier-payments.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari payment, referensi, pemasok..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('supplier-payments.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('supplier-payments.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <select name="supplier_id" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('supplier-payments.index', array_merge(request()->except('supplier_id'), ['supplier_id' => null])) }}">Semua Pemasok</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ route('supplier-payments.index', array_merge(request()->except('supplier_id'), ['supplier_id' => $supplier->id])) }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Payment</th>
                    <th>Pemasok</th>
                    <th>Tanggal</th>
                    <th>Metode</th>
                    <th>Akun Kas/Bank</th>
                    <th>Referensi</th>
                    <th>Total</th>
                    <th>Belum Dialokasi</th>
                    <th>Status</th>
                    <th>Alokasi</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr @if($payment->status === \App\Models\SupplierPayment::STATUS_VOID) style="color: var(--k-gray-500);" @endif>
                        <td><strong>{{ $payment->payment_number }}</strong></td>
                        <td>{{ $payment->supplier?->name ?? '-' }}</td>
                        <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td>{{ $payment->method_label }}</td>
                        <td>{{ $payment->chartAccount?->code ? $payment->chartAccount->code . ' - ' . $payment->chartAccount->name : '1110 - Kas dan Bank' }}</td>
                        <td>{{ $payment->reference_number ?? '-' }}</td>
                        <td class="dms-money">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($payment->unallocated_amount, 0, ',', '.') }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $payment->status_badge }}">
                                {{ $payment->status_label }}
                            </span>
                        </td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $payment->is_fully_allocated ? 'success' : 'warning' }}">
                                {{ $payment->is_fully_allocated ? 'Teralokasi' : 'Sisa Saldo' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('supplier-payments.show', $payment) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="dms-empty">
                            <i class="bi bi-bank"></i>
                            <p>Belum ada pembayaran supplier</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination" style="margin-top: 1rem;">
        {{ $payments->links() }}
    </div>
</div>
@endsection
