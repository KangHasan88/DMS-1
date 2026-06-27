@extends('layouts.sidebar')

@section('page-title', 'Dokumen Stok')
@section('breadcrumb', 'Inventori / Dokumen Stok')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Dokumen Stok</h3>
            <p class="dms-section-subtitle">Register BTB/BKB untuk kontrol penerimaan dan pengeluaran barang.</p>
        </div>
        @can('manage warehouse')
            <a href="{{ route('inventory-documents.create') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-plus-circle"></i> Buat Dokumen
            </a>
        @endcan
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('inventory-documents.index') }}" method="GET" class="dms-search-form" style="flex-wrap: wrap;">
            <div class="dms-search-field" style="min-width: 260px;">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nomor, referensi, gudang...">
            </div>
            <select name="type" class="form-control" style="max-width: 210px;">
                <option value="">Semua Tipe</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ strtoupper($value) }}</option>
                @endforeach
            </select>
            <select name="status" class="form-control" style="max-width: 180px;">
                <option value="">Semua Status</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="warehouse_id" class="form-control" style="max-width: 220px;">
                <option value="">Semua Gudang</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ (string) request('warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="dms-btn dms-btn-primary">Filter</button>
        </form>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Dokumen</th>
                    <th>Tipe</th>
                    <th>Tanggal</th>
                    <th>Gudang</th>
                    <th>Referensi</th>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                    <tr>
                        <td class="dms-strong">{{ $document->document_number }}</td>
                        <td>{{ strtoupper($document->type) }}</td>
                        <td>{{ optional($document->document_date)->format('d M Y') }}</td>
                        <td>{{ $document->warehouse?->name ?? '-' }}</td>
                        <td>{{ $document->reference_number ?: '-' }}</td>
                        <td>{{ number_format($document->items_count) }} item</td>
                        <td>
                            <span class="dms-badge {{ $document->status_badge }}">{{ $document->status_label }}</span>
                        </td>
                        <td>
                            <a href="{{ route('inventory-documents.show', $document) }}" class="dms-btn dms-btn-outline dms-btn-sm" style="width: auto; padding: 0 0.75rem;">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--k-gray-500);">Belum ada dokumen stok</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $documents->firstItem() ?? 0 }} - {{ $documents->lastItem() ?? 0 }} dari {{ $documents->total() }} dokumen
        </div>
        <div>{{ $documents->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
