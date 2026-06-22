@extends('layouts.sidebar')

@section('page-title', 'Tambah Delivery Coverage')
@section('breadcrumb', 'Operasional / Pengiriman / Delivery Coverage / Tambah')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    @include('delivery-coverage._form', [
        'action' => route('delivery-coverage.store'),
        'method' => 'POST',
        'deliveryZone' => null,
    ])
</div>
@endsection
