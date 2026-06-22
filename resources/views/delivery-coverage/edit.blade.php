@extends('layouts.sidebar')

@section('page-title', 'Edit Delivery Coverage')
@section('breadcrumb', 'Operasional / Pengiriman / Delivery Coverage / Edit')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    @include('delivery-coverage._form', [
        'action' => route('delivery-coverage.update', $deliveryZone),
        'method' => 'PUT',
    ])
</div>
@endsection
