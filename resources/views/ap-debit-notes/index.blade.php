@extends('layouts.sidebar')

@section('page-title', 'Debit Note AP')
@section('breadcrumb', 'Finance / Debit Note AP')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Debit Note AP</h3>
            <p class="dms-section-subtitle">Pantau koreksi pengurang hutang supplier dari retur, diskon, atau koreksi harga.</p>
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('ap-debit-notes.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari debit note, invoice, pemasok..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('ap-debit-notes.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('ap-debit-notes.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <select name="supplier_id" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('ap-debit-notes.index', array_merge(request()->except('supplier_id'), ['supplier_id' => null])) }}">Semua Pemasok</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ route('ap-debit-notes.index', array_merge(request()->except('supplier_id'), ['supplier_id' => $supplier->id])) }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
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
                    <th>No. Debit Note</th>
                    <th>Pemasok</th>
                    <th>Invoice AP</th>
                    <th>Tanggal</th>
                    <th>Alasan</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($debitNotes as $debitNote)
                    <tr>
                        <td><strong>{{ $debitNote->note_number }}</strong></td>
                        <td>{{ $debitNote->supplier?->name ?? '-' }}</td>
                        <td>{{ $debitNote->apInvoice?->invoice_number ?? '-' }}</td>
                        <td>{{ $debitNote->note_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $debitNote->reason_label }}</td>
                        <td class="dms-money">Rp {{ number_format($debitNote->amount, 0, ',', '.') }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $debitNote->status_badge }}">
                                {{ $debitNote->status_label }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('ap-debit-notes.show', $debitNote) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="dms-empty">
                            <i class="bi bi-receipt-cutoff"></i>
                            <p>Belum ada Debit Note AP</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination" style="margin-top: 1rem;">
        {{ $debitNotes->links() }}
    </div>
</div>
@endsection
