@extends('layouts.sidebar')

@section('page-title', 'Detail Persetujuan')
@section('breadcrumb', 'Administrasi / Persetujuan / Detail')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">{{ $approvalRequest->title }}</h3>
            <p class="dms-section-subtitle">{{ $approvalRequest->request_number }} - {{ $approvalRequest->type_label }}</p>
        </div>
        <a href="{{ route('approval-requests.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="dms-detail-grid" style="margin-bottom: 1.5rem;">
        <div class="dms-detail-item">
            <span class="dms-detail-label">Status</span>
            <span class="dms-badge {{ $approvalRequest->status === 'pending' ? 'dms-badge-warning' : ($approvalRequest->status === 'approved' ? 'dms-badge-success' : 'dms-badge-danger') }}">
                {{ $approvalRequest->status_label }}
            </span>
        </div>
        <div class="dms-detail-item">
            <span class="dms-detail-label">Cabang</span>
            <span>{{ $approvalRequest->companyBranch->name ?? '-' }}</span>
        </div>
        <div class="dms-detail-item">
            <span class="dms-detail-label">Diminta Oleh</span>
            <span>{{ $approvalRequest->requester->name ?? '-' }}</span>
        </div>
        <div class="dms-detail-item">
            <span class="dms-detail-label">Tanggal Request</span>
            <span>{{ $approvalRequest->requested_at?->format('d M Y H:i') ?? '-' }}</span>
        </div>
        <div class="dms-detail-item">
            <span class="dms-detail-label">Diproses Oleh</span>
            <span>{{ $approvalRequest->decider->name ?? '-' }}</span>
        </div>
        <div class="dms-detail-item">
            <span class="dms-detail-label">Tanggal Keputusan</span>
            <span>{{ $approvalRequest->decided_at?->format('d M Y H:i') ?? '-' }}</span>
        </div>
    </div>

    @if($approvalRequest->description)
        <div style="margin-bottom: 1.5rem;">
            <h4 class="dms-section-title" style="font-size: 0.95rem;">Deskripsi</h4>
            <p style="color: var(--k-gray-600);">{{ $approvalRequest->description }}</p>
        </div>
    @endif

    @if($approvalRequest->request_note)
        <div style="margin-bottom: 1.5rem;">
            <h4 class="dms-section-title" style="font-size: 0.95rem;">Catatan Request</h4>
            <p style="color: var(--k-gray-600);">{{ $approvalRequest->request_note }}</p>
        </div>
    @endif

    @if($approvalRequest->decision_note)
        <div style="margin-bottom: 1.5rem;">
            <h4 class="dms-section-title" style="font-size: 0.95rem;">Catatan Keputusan</h4>
            <p style="color: var(--k-gray-600);">{{ $approvalRequest->decision_note }}</p>
        </div>
    @endif

    @if(!empty($approvalRequest->payload))
        <div style="margin-bottom: 1.5rem;">
            <h4 class="dms-section-title" style="font-size: 0.95rem;">Ringkasan Data</h4>
            <div class="dms-table-wrap">
                <table class="dms-table">
                    <tbody>
                        @foreach($approvalRequest->payload as $key => $value)
                            <tr>
                                <th style="width: 220px;">{{ str($key)->headline() }}</th>
                                <td>{{ is_scalar($value) ? $value : json_encode($value) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @can('manage approvals')
        @if($approvalRequest->isPending())
            <div class="dms-form-actions" style="border-top: 1px solid var(--k-gray-200); padding-top: 1rem;">
                <form action="{{ route('approval-requests.reject', $approvalRequest) }}" method="POST" style="display: flex; gap: 0.5rem; flex: 1;">
                    @csrf
                    <input type="text" name="decision_note" class="form-control" placeholder="Alasan penolakan wajib diisi">
                    <button type="submit" class="dms-btn dms-btn-outline">
                        <i class="bi bi-x-circle"></i> Tolak
                    </button>
                </form>
                <form action="{{ route('approval-requests.approve', $approvalRequest) }}" method="POST">
                    @csrf
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-check2-circle"></i> Setujui
                    </button>
                </form>
            </div>
        @endif
    @endcan
</div>
@endsection
