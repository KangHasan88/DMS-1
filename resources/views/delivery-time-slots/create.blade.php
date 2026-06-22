@extends('layouts.sidebar')

@section('page-title', 'Tambah Slot Waktu')
@section('breadcrumb', 'Operasional / Pengiriman / Slot Waktu / Tambah')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div class="dms-form-header">
        <h3 class="dms-form-title">Tambah Slot Waktu</h3>
        <p class="dms-form-subtitle">Tambahkan pilihan jam pengiriman untuk form order.</p>
    </div>

    @include('delivery-time-slots._form', [
        'action' => route('delivery-time-slots.store'),
        'method' => 'POST',
        'slot' => null,
        'companyBranches' => $companyBranches,
        'branchLocked' => $branchLocked,
    ])
</div>
@endsection
