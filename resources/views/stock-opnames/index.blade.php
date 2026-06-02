@extends('layouts.sidebar')

@section('page-title', 'Stock Opname')
@section('breadcrumb', 'Inventory / Stock Opname')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Stock Opname</h3>
            <p class="dms-section-subtitle">Dokumen pemeriksaan stok fisik dan penyesuaian stok sistem.</p>
        </div>
        <a href="{{ route('stock-opnames.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Buat Opname
        </a>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>No. Opname</th>
                    <th>Tanggal</th>
                    <th>Item</th>
                    <th>Status</th>
                    <th>Dibuat Oleh</th>
                    <th>Selesai Oleh</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stockOpnames as $opname)
                    <tr>
                        <td><strong>{{ $opname->opname_number }}</strong></td>
                        <td>{{ $opname->opname_date->format('d M Y') }}</td>
                        <td>{{ number_format($opname->items_count) }}</td>
                        <td><span class="dms-badge dms-badge-{{ $opname->status_color }}">{{ $opname->status_label }}</span></td>
                        <td>{{ $opname->createdBy->name ?? '-' }}</td>
                        <td>{{ $opname->completedBy->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('stock-opnames.show', $opname) }}" class="dms-btn dms-btn-outline dms-btn-sm">
                                <i class="bi bi-eye"></i>
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="dms-empty-state">
                                <i class="bi bi-clipboard-check"></i>
                                <p>Belum ada dokumen stock opname.</p>
                                <a href="{{ route('stock-opnames.create') }}" class="dms-btn dms-btn-primary">
                                    <i class="bi bi-plus-circle"></i>
                                    Buat Opname Pertama
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $stockOpnames->firstItem() ?? 0 }} - {{ $stockOpnames->lastItem() ?? 0 }} dari {{ $stockOpnames->total() }} opname
        </div>
        <div>{{ $stockOpnames->links() }}</div>
    </div>
</div>
@endsection
