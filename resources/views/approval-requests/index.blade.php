@extends('layouts.sidebar')

@section('page-title', 'Persetujuan')
@section('breadcrumb', 'Administrasi / Persetujuan')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Daftar Persetujuan</h3>
            <p class="dms-section-subtitle">Pantau permintaan approval dari transaksi operasional, gudang, pembelian, dan finance.</p>
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('approval-requests.index') }}" method="GET" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="form-control" placeholder="Cari nomor approval, judul, deskripsi..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>

        <div class="dms-toolbar-actions">
            <select class="form-control" onchange="window.location.href = this.value">
                <option value="{{ route('approval-requests.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ route('approval-requests.index', array_merge(request()->except('status'), ['status' => $key])) }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <select class="form-control" onchange="window.location.href = this.value">
                <option value="{{ route('approval-requests.index', array_merge(request()->except('approval_type'), ['approval_type' => null])) }}">Semua Tipe</option>
                @foreach($types as $key => $label)
                    <option value="{{ route('approval-requests.index', array_merge(request()->except('approval_type'), ['approval_type' => $key])) }}" {{ request('approval_type') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Approval</th>
                    <th>Tipe</th>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>Diminta Oleh</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvalRequests as $approvalRequest)
                    <tr>
                        <td><strong>{{ $approvalRequest->request_number }}</strong></td>
                        <td>{{ $approvalRequest->type_label }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ $approvalRequest->title }}</div>
                            @if($approvalRequest->companyBranch)
                                <div class="dms-muted">{{ $approvalRequest->companyBranch->name }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="dms-badge {{ $approvalRequest->status === 'pending' ? 'dms-badge-warning' : ($approvalRequest->status === 'approved' ? 'dms-badge-success' : 'dms-badge-danger') }}">
                                {{ $approvalRequest->status_label }}
                            </span>
                        </td>
                        <td>{{ $approvalRequest->requester->name ?? '-' }}</td>
                        <td>{{ $approvalRequest->requested_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            <div class="dms-actions">
                                <a href="{{ route('approval-requests.show', $approvalRequest) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="dms-empty-state">
                            <i class="bi bi-check2-circle"></i>
                            <p>Belum ada permintaan persetujuan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination"><div></div><div>{{ $approvalRequests->links() }}</div></div>
</div>
@endsection
