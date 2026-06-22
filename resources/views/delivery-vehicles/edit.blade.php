@extends('layouts.sidebar')

@section('page-title', 'Edit Armada')
@section('breadcrumb', 'Operasional / Pengiriman / Armada / Edit')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Armada</h3>
        <p class="dms-form-subtitle">Perbarui status, plat, atau informasi kendaraan.</p>
    </div>

    @include('delivery-vehicles._form', [
        'action' => route('delivery-vehicles.update', $deliveryVehicle),
        'method' => 'PUT',
        'vehicle' => $deliveryVehicle,
    ])
</div>
@endsection
