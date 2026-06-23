@extends('layouts.sidebar')

@section('title', 'Kemasan Kembali - DMS KURMIGO')
@section('page-title', 'Kemasan Kembali')
@section('breadcrumb', 'Inventori / Kemasan Kembali')

@section('content')
<div class="dms-card mb-4">
    <div class="dms-section-header">
        <div>
            <h2 class="dms-section-title">Kontrol Kemasan Kembali</h2>
            <p class="dms-section-subtitle">Pantau galon, botol, krat, tabung, pallet, atau kemasan lain yang masih berada di customer.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="dms-metric">
                <span class="dms-metric-label">Jenis Kemasan</span>
                <strong>{{ number_format($packages->count()) }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dms-metric">
                <span class="dms-metric-label">Outstanding Customer</span>
                <strong>{{ number_format($balances->sum('outstanding_quantity')) }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dms-metric">
                <span class="dms-metric-label">Mutasi Tercatat</span>
                <strong>{{ number_format($movements->total()) }}</strong>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <h3 class="dms-subsection-title">Master Kemasan</h3>
            <form method="POST" action="{{ route('returnable-packages.store') }}" class="row g-3">
                @csrf
                <div class="col-md-5">
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" placeholder="GAL19">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-7">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Galon 19L">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <select name="category" class="form-control @error('category') is-invalid @enderror">
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Satuan</label>
                    <input type="text" name="unit" value="{{ old('unit', 'pcs') }}" class="form-control @error('unit') is-invalid @enderror">
                    @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Nilai Pengganti</label>
                    <input type="number" name="replacement_value" value="{{ old('replacement_value', 0) }}" class="form-control @error('replacement_value') is-invalid @enderror" min="0">
                    @error('replacement_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-check">
                        <input type="checkbox" name="requires_serial_tracking" value="1" class="form-check-input" @checked(old('requires_serial_tracking'))>
                        <span class="form-check-label">Butuh tracking nomor seri</span>
                    </label>
                </div>
                <div class="col-12">
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Master
                    </button>
                </div>
            </form>
        </div>

        <div class="col-lg-7">
            <h3 class="dms-subsection-title">Catat Mutasi Customer</h3>
            <form method="POST" action="{{ route('returnable-packages.movements.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Kemasan</label>
                    <select name="returnable_package_id" class="form-control @error('returnable_package_id') is-invalid @enderror">
                        <option value="">Pilih kemasan</option>
                        @foreach($activePackages as $package)
                            <option value="{{ $package->id }}" data-value="{{ $package->replacement_value }}" @selected((string) old('returnable_package_id') === (string) $package->id)>
                                {{ $package->code }} - {{ $package->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('returnable_package_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-control @error('customer_id') is-invalid @enderror">
                        <option value="">Pilih customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @if($canFilterBranches)
                    <div class="col-md-6">
                        <label class="form-label">Cabang</label>
                        <select name="company_branch_id" class="form-control @error('company_branch_id') is-invalid @enderror">
                            <option value="">Global / tanpa cabang</option>
                            @foreach($companyBranches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) old('company_branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('company_branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label">Tipe Mutasi</label>
                    <select name="movement_type" class="form-control @error('movement_type') is-invalid @enderror">
                        @foreach($movementTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('movement_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('movement_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="movement_date" value="{{ old('movement_date', now()->toDateString()) }}" class="form-control @error('movement_date') is-invalid @enderror">
                    @error('movement_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Qty</label>
                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" class="form-control @error('quantity') is-invalid @enderror" min="1">
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nilai / Unit</label>
                    <input type="number" name="unit_value" value="{{ old('unit_value') }}" class="form-control @error('unit_value') is-invalid @enderror" min="0" placeholder="Auto">
                    @error('unit_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label">No Referensi</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control @error('reference_number') is-invalid @enderror" placeholder="Invoice / DO / manual">
                    @error('reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-7">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="form-control @error('notes') is-invalid @enderror" placeholder="Contoh: galon kosong kembali">
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-journal-plus"></i> Simpan Mutasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="dms-card mb-4">
    <div class="dms-section-header">
        <div>
            <h2 class="dms-section-title">Saldo Outstanding</h2>
            <p class="dms-section-subtitle">Customer yang masih memegang kemasan returnable.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table dms-table align-middle">
            <thead>
                <tr>
                    <th>Kemasan</th>
                    <th>Customer</th>
                    <th>Cabang</th>
                    <th class="text-end">Outstanding</th>
                    <th class="text-end">Eksposur Nilai</th>
                    <th>Update Terakhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $balance)
                    <tr>
                        <td>
                            <strong>{{ $balance->package->name }}</strong><br>
                            <span class="text-muted">{{ $balance->package->code }}</span>
                        </td>
                        <td>{{ $balance->customer->name }}</td>
                        <td>{{ $balance->companyBranch?->name ?? '-' }}</td>
                        <td class="text-end">{{ number_format($balance->outstanding_quantity) }} {{ $balance->package->unit }}</td>
                        <td class="text-end">Rp {{ number_format($balance->outstanding_quantity * $balance->package->replacement_value, 0, ',', '.') }}</td>
                        <td>{{ $balance->last_movement_at?->format('d M Y H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada saldo kemasan outstanding.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="dms-card mb-4">
    <div class="dms-section-header">
        <div>
            <h2 class="dms-section-title">Riwayat Mutasi</h2>
            <p class="dms-section-subtitle">Jejak keluar, kembali, dijual putus, hilang, rusak, dan penyesuaian.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table dms-table align-middle">
            <thead>
                <tr>
                    <th>No Mutasi</th>
                    <th>Tanggal</th>
                    <th>Kemasan</th>
                    <th>Customer</th>
                    <th>Tipe</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Saldo</th>
                    <th class="text-end">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>
                            <strong>{{ $movement->movement_number }}</strong><br>
                            <span class="text-muted">{{ $movement->reference_number ?: '-' }}</span>
                        </td>
                        <td>{{ $movement->movement_date->format('d M Y') }}</td>
                        <td>{{ $movement->package->name }}</td>
                        <td>{{ $movement->customer->name }}</td>
                        <td><span class="dms-badge">{{ $movement->type_label }}</span></td>
                        <td class="text-end">{{ number_format($movement->quantity) }}</td>
                        <td class="text-end">{{ number_format($movement->balance_before) }} -> {{ number_format($movement->balance_after) }}</td>
                        <td class="text-end">Rp {{ number_format($movement->total_value, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada mutasi kemasan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $movements->links() }}
</div>

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h2 class="dms-section-title">Daftar Master Kemasan</h2>
            <p class="dms-section-subtitle">Jenis kemasan yang bisa ditracking sebagai returnable packaging.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table dms-table align-middle">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th class="text-end">Nilai Pengganti</th>
                    <th>Tracking</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packages as $package)
                    <tr>
                        <td><strong>{{ $package->code }}</strong></td>
                        <td>{{ $package->name }}</td>
                        <td>{{ $package->category_label }}</td>
                        <td>{{ $package->unit }}</td>
                        <td class="text-end">Rp {{ number_format($package->replacement_value, 0, ',', '.') }}</td>
                        <td>{{ $package->requires_serial_tracking ? 'Serial' : 'Qty' }}</td>
                        <td>
                            <span class="dms-badge {{ $package->is_active ? 'dms-badge-success' : '' }}">{{ $package->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada master kemasan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
