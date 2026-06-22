@extends('layouts.sidebar')

@section('page-title', 'Tambah Sesi Rute')
@section('breadcrumb', 'Operasional / Pengiriman / Sesi Rute / Tambah')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Tambah Sesi Rute</h3>
            <p class="dms-section-subtitle">Buat sesi rute untuk full canvas atau semi canvas sebelum armada berangkat.</p>
        </div>
    </div>

    <form action="{{ route('delivery-route-sessions.store') }}" method="POST">
        @csrf
        @include('delivery-route-sessions._form', ['submitLabel' => 'Simpan Sesi Rute'])
    </form>
</div>
@endsection
