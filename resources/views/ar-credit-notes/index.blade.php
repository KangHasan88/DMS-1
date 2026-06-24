@extends('layouts.sidebar')

@section('page-title', 'Credit Note AR')
@section('breadcrumb', 'Finance / Credit Note AR')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Credit Note AR</h3>
            <p class="dms-section-subtitle">Pantau koreksi pengurang piutang customer dari retur, diskon, atau koreksi harga.</p>
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('ar-credit-notes.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari credit note, invoice, customer..."
                       value="{{ request('search') }}" class="form-control">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('ar-credit-notes.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('ar-credit-notes.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @if($canFilterBranches)
                <select name="company_branch_id" onchange="window.location.href = this.value" class="form-control">
                    <option value="{{ route('ar-credit-notes.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => null])) }}">Semua Cabang</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ route('ar-credit-notes.index', array_merge(request()->except('company_branch_id'), ['company_branch_id' => $branch->id])) }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
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
                    <th>No. Credit Note</th>
                    <th>Customer</th>
                    <th>Invoice AR</th>
                    <th>Tanggal</th>
                    <th>Alasan</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($creditNotes as $creditNote)
                    <tr>
                        <td><strong>{{ $creditNote->note_number }}</strong></td>
                        <td>{{ $creditNote->customer?->name ?? $creditNote->customerUser?->name ?? '-' }}</td>
                        <td>{{ $creditNote->arInvoice?->invoice_number ?? '-' }}</td>
                        <td>{{ $creditNote->note_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $creditNote->reason_label }}</td>
                        <td class="dms-money">Rp {{ number_format($creditNote->amount, 0, ',', '.') }}</td>
                        <td>
                            <span class="dms-badge dms-badge-{{ $creditNote->status_badge }}">
                                {{ $creditNote->status_label }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('ar-credit-notes.show', $creditNote) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="dms-empty">
                            <i class="bi bi-receipt-cutoff"></i>
                            <p>Belum ada Credit Note AR</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination" style="margin-top: 1rem;">
        {{ $creditNotes->links() }}
    </div>
</div>
@endsection
