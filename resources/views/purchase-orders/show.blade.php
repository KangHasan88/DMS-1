@extends('layouts.sidebar')

@section('page-title', 'Detail Purchase Order')
@section('breadcrumb', 'Purchase Orders / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-receipt" style="color: var(--k-green);"></i>
                Detail Purchase Order
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                PO #{{ $purchaseOrder->po_number }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            @can('edit purchase order')
            @if($purchaseOrder->canApprove())
                <form action="{{ route('purchase-orders.approve', $purchaseOrder) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-send"></i> Ajukan Approval
                    </button>
                </form>
            @endif
            @endcan

            @if($purchaseOrder->isApprovalPending() && $purchaseOrder->approvalRequest)
                <a href="{{ route('approval-requests.show', $purchaseOrder->approvalRequest) }}" class="dms-btn dms-btn-outline">
                    <i class="bi bi-hourglass-split"></i> Lihat Approval
                </a>
            @endif
            
            @can('edit purchase order')
            @if($purchaseOrder->canReceive())
                <a href="{{ route('purchase-orders.receive-form', $purchaseOrder) }}" class="dms-btn dms-btn-primary">
                    <i class="bi bi-box-seam"></i> Receive Barang
                </a>
            @endif
            @endcan
            
            @can('edit purchase order')
            @if($purchaseOrder->status === 'draft' && !$purchaseOrder->isApprovalPending())
                <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="dms-btn dms-btn-outline">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            @endif
            @endcan
            @can('delete purchase order')
            @if($purchaseOrder->status === 'draft' && !$purchaseOrder->isApprovalPending())
                <form action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="dms-btn dms-btn-outline" style="color: var(--k-red);" onclick="return confirm('Yakin ingin membatalkan PO ini?')">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                </form>
            @endif
            @endcan
            
            <a href="{{ route('purchase-orders.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- PO Status & Info -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Status</div>
            <div style="margin-top: 0.5rem;">
                <span class="dms-badge dms-badge-{{ $purchaseOrder->status_color }}" style="font-size: 0.9rem; padding: 0.3rem 1rem;">
                    {{ $purchaseOrder->status_label }}
                </span>
            </div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Total PO</div>
            <div style="font-size: 1.2rem; font-weight: 700; color: var(--k-green);">
                Rp {{ number_format($purchaseOrder->total, 0, ',', '.') }}
            </div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Dibuat Oleh</div>
            <div style="font-size: 0.9rem; font-weight: 500;">{{ $purchaseOrder->createdBy->name ?? '-' }}</div>
            <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $purchaseOrder->created_at->format('d M Y H:i') }}</div>
        </div>
        
        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Diapprove Oleh</div>
            <div style="font-size: 0.9rem; font-weight: 500;">{{ $purchaseOrder->approvedBy->name ?? '-' }}</div>
            <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $purchaseOrder->approved_at ? $purchaseOrder->approved_at->format('d M Y H:i') : '-' }}</div>
        </div>

        <div style="padding: 1rem; background: var(--k-gray-50); border-radius: 8px; text-align: center;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Approval</div>
            <div style="font-size: 0.9rem; font-weight: 600;">{{ $purchaseOrder->approval_status_label }}</div>
            @if($purchaseOrder->rejected_at)
                <div style="font-size: 0.65rem; color: var(--k-red);">{{ $purchaseOrder->rejection_note }}</div>
            @elseif($purchaseOrder->approvalRequest)
                <div style="font-size: 0.65rem; color: var(--k-gray-500);">{{ $purchaseOrder->approvalRequest->request_number }}</div>
            @endif
        </div>
    </div>

    <!-- Pemasok Info -->
    <div style="margin-bottom: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-shop" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Informasi Pemasok
        </h4>
        
        <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Nama Pemasok</div>
                    <div style="font-weight: 600;">{{ $purchaseOrder->supplier->name }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Telepon</div>
                    <div>{{ $purchaseOrder->supplier->phone }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; color: var(--k-gray-500);">Lokasi</div>
                    <div>{{ $purchaseOrder->supplier->market_name ?? '-' }} {{ $purchaseOrder->supplier->stall_number ? '(Lapak ' . $purchaseOrder->supplier->stall_number . ')' : '' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div style="margin-bottom: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-box-seam" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Detail Produk
        </h4>
        
        <div style="overflow-x: auto;">
            <table class="dms-table">
                <thead>
                     <tr>
                        <th>Produk</th>
                        <th>Satuan</th>
                        <th>Qty PO</th>
                        <th>Diterima</th>
                        <th>Sisa</th>
                        <th>Harga Beli</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                    @php
                        $remaining = $item->remaining_quantity;
                        $received = $item->received_quantity;
                        $isFullyReceived = $item->isFullyReceived();
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight: 600;">{{ $item->product->name }}</div>
                            @if($item->notes)
                            <div style="font-size: 0.65rem; color: var(--k-gray-500);">Catatan: {{ $item->notes }}</div>
                            @endif
                        </td>
                        <td>{{ $item->product->unit->name ?? '-' }}</td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td>
                            <span style="color: {{ $received > 0 ? 'var(--k-green)' : 'var(--k-gray-500)' }};">
                                {{ number_format($received) }}
                            </span>
                        </td>
                        <td>
                            @if($remaining > 0)
                                <span class="dms-badge dms-badge-warning">{{ number_format($remaining) }}</span>
                            @else
                                <span class="dms-badge dms-badge-success">Selesai</span>
                            @endif
                        </td>
                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: var(--k-green-light);">
                        <td colspan="6" style="text-align: right; font-weight: 700;">Total</td>
                        <td style="font-weight: 700;">Rp {{ number_format($purchaseOrder->total, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Notes -->
    @if($purchaseOrder->notes || $purchaseOrder->internal_notes)
    <div>
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-chat-dots" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Catatan
        </h4>
        
        @if($purchaseOrder->notes)
        <div style="margin-bottom: 1rem; padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Catatan untuk Pemasok</div>
            <p style="color: var(--k-gray-700);">{{ $purchaseOrder->notes }}</p>
        </div>
        @endif
        
        @if($purchaseOrder->internal_notes)
        <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500);">Catatan Internal</div>
            <p style="color: var(--k-gray-700);">{{ $purchaseOrder->internal_notes }}</p>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection
