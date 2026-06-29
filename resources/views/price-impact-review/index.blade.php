@extends('layouts.sidebar')

@section('page-title', 'Review Dampak Harga')
@section('breadcrumb', 'Katalog / Review Dampak Harga')

@section('content')
<style>
    .impact-page { display: grid; gap: 1rem; }
    .impact-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--k-border); }
    .impact-title { display: flex; align-items: flex-start; gap: .75rem; }
    .impact-icon { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 40px; border-radius: 10px; color: var(--k-navy); background: #fff4e8; }
    .impact-filter { display: grid; grid-template-columns: minmax(260px, 1fr) 150px 170px 170px auto; gap: .75rem; align-items: end; padding: .9rem; border: 1px solid var(--k-border); border-radius: 10px; background: var(--k-gray-50); }
    .impact-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .85rem; }
    .impact-stat { border: 1px solid var(--k-border); border-radius: 8px; padding: .9rem 1rem; background: var(--k-white); }
    .impact-stat span { display: block; color: var(--k-gray-600); font-size: .82rem; font-weight: 700; }
    .impact-stat strong { display: block; margin-top: .25rem; color: var(--k-navy); font-size: 1.35rem; }
    .impact-product { display: grid; gap: .18rem; }
    .impact-product strong { color: var(--k-navy); }
    .impact-product small, .impact-muted { color: var(--k-gray-600); font-size: .8rem; }
    .impact-money { color: var(--k-navy); font-weight: 800; white-space: nowrap; }
    .impact-danger { color: #b42318; font-weight: 800; }
    .impact-success { color: #027a48; font-weight: 800; }
    .impact-actions { display: grid; gap: .45rem; align-items: stretch; min-width: 160px; }
    .impact-apply { display: inline; }
    .impact-actions .dms-btn { justify-content: center; width: 100%; padding: .48rem .7rem; }
    .impact-status-stack { display: grid; gap: .35rem; justify-items: start; }
    @media (max-width: 1180px) {
        .impact-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .impact-stats { grid-template-columns: 1fr; }
    }
    @media (max-width: 760px) {
        .impact-header { flex-direction: column; }
        .impact-filter { grid-template-columns: 1fr; }
    }
</style>

<div class="dms-card impact-page">
    <div class="dms-section-header impact-header">
        <div class="impact-title">
            <div class="impact-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
                <h3 class="dms-section-title">Review Dampak Harga</h3>
                <p class="dms-section-subtitle">Pantau dampak kenaikan harga beli terhadap margin dan rekomendasi harga jual.</p>
            </div>
        </div>
        <a href="{{ route('product-price-rules.index') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-tags"></i> Daftar Harga
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">Periksa kembali nilai harga yang akan diterapkan.</div>
    @endif

    <form action="{{ route('price-impact-review.index') }}" method="GET" class="impact-filter">
        <div class="form-group">
            <label class="form-label">Cari Produk</label>
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Nama produk, SKU, kategori...">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Target Margin (%)</label>
            <input type="number" name="target_margin" value="{{ $targetMargin }}" min="1" max="80" step="0.1" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">Batas Naik Cost (%)</label>
            <input type="number" name="cost_increase_threshold" value="{{ $costIncreaseThreshold }}" min="0" max="100" step="0.1" class="form-control">
        </div>
        <div class="form-group">
            <label class="form-label">Status</label>
            <select name="mode" class="form-control">
                <option value="review_only" {{ $mode === 'review_only' ? 'selected' : '' }}>Perlu review saja</option>
                <option value="all" {{ $mode === 'all' ? 'selected' : '' }}>Semua produk</option>
            </select>
        </div>
        <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
    </form>

    <div class="impact-stats">
        <div class="impact-stat">
            <span>Produk Dengan Harga Beli</span>
            <strong>{{ number_format($stats['products_with_purchase'], 0, ',', '.') }}</strong>
        </div>
        <div class="impact-stat">
            <span>Perlu Review</span>
            <strong>{{ number_format($stats['needs_review'], 0, ',', '.') }}</strong>
        </div>
        <div class="impact-stat">
            <span>Menunggu Approval</span>
            <strong>{{ number_format($stats['pending_approval'], 0, ',', '.') }}</strong>
        </div>
        <div class="impact-stat">
            <span>Rata-rata Margin Proyeksi</span>
            <strong>{{ $stats['average_projected_margin'] !== null ? number_format($stats['average_projected_margin'], 1, ',', '.') . '%' : '-' }}</strong>
        </div>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th style="width: 22%;">Produk</th>
                    <th style="width: 12%; text-align: right;">Modal Master</th>
                    <th style="width: 16%; text-align: right;">Harga Beli Terakhir</th>
                    <th style="width: 12%; text-align: right;">Harga Jual</th>
                    <th style="width: 12%; text-align: right;">Margin Proyeksi</th>
                    <th style="width: 14%; text-align: right;">Rekomendasi</th>
                    <th style="width: 12%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $product = $row['product'];
                        $latestItem = $row['latest_purchase_item'];
                        $purchaseOrder = $latestItem?->purchaseOrder;
                        $pendingApproval = $row['pending_approval'];
                    @endphp
                    <tr>
                        <td>
                            <div class="impact-product">
                                <strong>{{ $product->name }}</strong>
                                <small>{{ $product->sku ?: 'Tanpa SKU' }} · {{ $product->unit->name ?? $product->unit ?? '-' }}</small>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span class="impact-money">Rp {{ number_format($row['master_cost'], 0, ',', '.') }}</span>
                        </td>
                        <td style="text-align: right;">
                            @if($row['latest_purchase_price'] > 0)
                                <div class="impact-money">Rp {{ number_format($row['latest_purchase_price'], 0, ',', '.') }}</div>
                                <div class="impact-muted">
                                    {{ $purchaseOrder?->po_number ?? '-' }}
                                    @if($purchaseOrder?->supplier)
                                        · {{ $purchaseOrder->supplier->name }}
                                    @endif
                                </div>
                                @if($row['cost_change_percent'] !== null)
                                    <div class="{{ $row['cost_change_percent'] >= 0 ? 'impact-danger' : 'impact-success' }}">
                                        {{ $row['cost_change_percent'] >= 0 ? '+' : '' }}{{ number_format($row['cost_change_percent'], 1, ',', '.') }}%
                                    </div>
                                @endif
                            @else
                                <span class="impact-muted">Belum ada pembelian</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <span class="impact-money">Rp {{ number_format($row['selling_price'], 0, ',', '.') }}</span>
                            @if($row['current_margin_percent'] !== null)
                                <div class="impact-muted">Margin {{ number_format($row['current_margin_percent'], 1, ',', '.') }}%</div>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($row['projected_margin_percent'] !== null)
                                <span class="{{ $row['projected_margin_percent'] < $targetMargin ? 'impact-danger' : 'impact-success' }}">
                                    {{ number_format($row['projected_margin_percent'], 1, ',', '.') }}%
                                </span>
                                <div class="impact-muted">Target {{ number_format($targetMargin, 1, ',', '.') }}%</div>
                            @else
                                <span class="impact-muted">-</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <span class="impact-money">Rp {{ number_format($row['recommended_price'], 0, ',', '.') }}</span>
                            @if($row['recommended_increase'] > 0)
                                <div class="impact-danger">Naik Rp {{ number_format($row['recommended_increase'], 0, ',', '.') }}</div>
                            @else
                                <div class="impact-success">Harga aman</div>
                            @endif
                        </td>
                        <td>
                            <div class="impact-actions">
                                <div class="impact-status-stack">
                                    @if($pendingApproval)
                                        <span class="dms-badge dms-badge-info">Menunggu Approval</span>
                                    @elseif($row['needs_review'])
                                        <span class="dms-badge dms-badge-warning">Perlu Review</span>
                                    @else
                                        <span class="dms-badge dms-badge-success">OK</span>
                                    @endif
                                </div>
                                @can('edit products')
                                    @if($pendingApproval)
                                        <button type="button" class="dms-btn dms-btn-outline" disabled>
                                            <i class="bi bi-clock-history"></i> Sudah Diajukan
                                        </button>
                                        <a href="{{ route('approval-requests.show', $pendingApproval) }}" class="dms-btn dms-btn-outline">
                                            <i class="bi bi-eye"></i> Lihat Approval
                                        </a>
                                    @elseif($row['latest_purchase_price'] > 0)
                                        <form action="{{ route('price-impact-review.apply', $product) }}" method="POST" class="impact-apply">
                                            @csrf
                                            <input type="hidden" name="new_base_price" value="{{ $row['latest_purchase_price'] }}">
                                            <input type="hidden" name="new_price" value="{{ $row['recommended_price'] }}">
                                            <input type="hidden" name="reason" value="Update harga dari kenaikan harga beli {{ $purchaseOrder?->po_number ?? '' }}">
                                            <button type="submit" class="dms-btn dms-btn-primary">
                                                <i class="bi bi-send-check"></i> Ajukan Approval
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                <a href="{{ route('products.edit', $product) }}" class="dms-btn dms-btn-outline">
                                    <i class="bi bi-pencil"></i> Edit Produk
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="dms-empty-state">
                                <i class="bi bi-graph-up"></i>
                                <p>Tidak ada produk yang perlu review pada filter ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
