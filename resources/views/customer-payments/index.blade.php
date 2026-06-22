@extends('layouts.sidebar')

@section('page-title', 'Pembayaran Customer')
@section('breadcrumb', 'Finance / Pembayaran Customer')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Pembayaran Customer</h3>
            <p class="dms-section-subtitle">Pantau pembayaran masuk dan saldo yang sudah dialokasikan ke invoice AR.</p>
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('customer-payments.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari payment, referensi, pelanggan..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            @if($canFilterBranches)
                <select name="company_branch_id" onchange="window.location.href = this.value" class="form-control">
                    <option value="{{ route('customer-payments.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => null])) }}">Semua Cabang</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ route('customer-payments.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Payment</th>
                    <th>Pelanggan</th>
                    <th>Tanggal</th>
                    <th>Metode</th>
                    <th>Referensi</th>
                    <th>Total</th>
                    <th>Belum Dialokasi</th>
                    <th>Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td><strong>{{ $payment->payment_number }}</strong></td>
                        <td>{{ $payment->customer?->name ?? $payment->customerUser?->name ?? '-' }}</td>
                        <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td>{{ $payment->method_label }}</td>
                        <td>{{ $payment->reference_number ?? '-' }}</td>
                        <td class="dms-money">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="dms-money">Rp {{ number_format($payment->unallocated_amount, 0, ',', '.') }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $payment->is_fully_allocated ? 'success' : 'warning' }}">
                                {{ $payment->is_fully_allocated ? 'Teralokasi' : 'Sisa Saldo' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('customer-payments.show', $payment) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="dms-empty">
                            <i class="bi bi-cash-coin"></i>
                            <p>Belum ada pembayaran customer</p>
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
