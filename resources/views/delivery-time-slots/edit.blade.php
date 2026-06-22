@extends('layouts.sidebar')

@section('page-title', 'Edit Slot Waktu')
@section('breadcrumb', 'Operasional / Pengiriman / Slot Waktu / Edit')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Edit Slot Waktu</h3>
        <p class="dms-form-subtitle">Perbarui jam pengiriman dan status aktifnya.</p>
    </div>

    @include('delivery-time-slots._form', [
        'action' => route('delivery-time-slots.update', $deliveryTimeSlot),
        'method' => 'PUT',
        'slot' => $deliveryTimeSlot,
        'companyBranches' => $companyBranches,
        'branchLocked' => $branchLocked,
    ])
</div>
@endsection
