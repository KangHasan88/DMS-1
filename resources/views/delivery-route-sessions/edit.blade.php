@extends('layouts.sidebar')

@section('page-title', 'Edit Sesi Rute')
@section('breadcrumb', 'Operasional / Pengiriman / Sesi Rute / Edit')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Edit Sesi Rute</h3>
            <p class="dms-section-subtitle">{{ $session->route_code }} · {{ $session->route_date?->format('d M Y') }}</p>
        </div>
        <a href="{{ route('delivery-route-sessions.show', $session) }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-eye"></i> Detail
        </a>
    </div>

    <form action="{{ route('delivery-route-sessions.update', $session) }}" method="POST">
        @csrf
        @method('PUT')
        @include('delivery-route-sessions._form', ['submitLabel' => 'Simpan Perubahan'])
    </form>
</div>
@endsection
