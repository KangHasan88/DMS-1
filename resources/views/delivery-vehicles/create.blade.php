@extends('layouts.sidebar')

@section('page-title', 'Tambah Armada')
@section('breadcrumb', 'Operasional / Pengiriman / Armada / Tambah')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Tambah Armada</h3>
        <p class="dms-form-subtitle">Isi data kendaraan internal untuk pengiriman.</p>
    </div>

    @include('delivery-vehicles._form', [
        'action' => route('delivery-vehicles.store'),
        'method' => 'POST',
        'vehicle' => null,
    ])
</div>
@endsection
