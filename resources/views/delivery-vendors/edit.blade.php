@extends('layouts.sidebar')

@section('page-title', 'Edit Ekspedisi')
@section('breadcrumb', 'Operasional / Pengiriman / Ekspedisi / Edit')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Ekspedisi</h3>
        <p class="dms-form-subtitle">Perbarui data vendor pengiriman dan status aktifnya.</p>
    </div>

    @include('delivery-vendors._form', [
        'action' => route('delivery-vendors.update', $deliveryVendor),
        'method' => 'PUT',
        'vendor' => $deliveryVendor,
        'companyBranches' => $companyBranches,
        'branchLocked' => $branchLocked,
    ])
</div>
@endsection
