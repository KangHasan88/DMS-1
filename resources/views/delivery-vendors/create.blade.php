@extends('layouts.sidebar')

@section('page-title', 'Tambah Ekspedisi')
@section('breadcrumb', 'Operasional / Pengiriman / Ekspedisi / Tambah')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Tambah Ekspedisi</h3>
        <p class="dms-form-subtitle">Vendor pengiriman pihak ketiga untuk kebutuhan delivery dan finance.</p>
    </div>

    @include('delivery-vendors._form', [
        'action' => route('delivery-vendors.store'),
        'method' => 'POST',
        'vendor' => null,
        'companyBranches' => $companyBranches,
        'branchLocked' => $branchLocked,
    ])
</div>
@endsection
