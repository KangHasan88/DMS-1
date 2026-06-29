@extends('layouts.sidebar')

@section('page-title', 'Detail Order')
@section('breadcrumb', 'Orders / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--k-gray-800);">
                <i class="bi bi-receipt" style="color: var(--k-green);"></i>
                Detail Order
            </h3>
            <p style="font-size: 0.85rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                Order #{{ $order->order_number }}
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            @can('view invoice')
            <a href="{{ route('orders.proforma-invoice', $order) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-file-earmark-text"></i> Proforma
            </a>
            <a href="{{ route('orders.invoice', $order) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-receipt"></i> Invoice
            </a>
            @if($order->canViewDeliveryOrderDocument())
            <a href="{{ route('orders.delivery-order', $order) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-truck"></i> DO
            </a>
            @endif
            @endcan
            @can('edit sales order')
            @if($order->canEditOrder())
            <a href="{{ route('orders.edit', $order) }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endif
            @endcan
            <a href="{{ route('orders.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Status Badges -->
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="dms-badge dms-badge-{{ $order->status_color }}" style="font-size: 1rem; padding: 0.5rem 1rem;">
                    {{ $order->status_label }}
                </span>
            </div>
            <div>
                <span class="dms-badge dms-badge-info">
                    <i class="bi bi-{{ $order->order_source == 'sfa' ? 'phone' : ($order->order_source == 'telesales' ? 'headset' : 'laptop') }}"></i>
                    {{ $order->order_source_label }}
                </span>
                <span class="dms-badge dms-badge-{{ $order->fulfillment_type == 'stock' ? 'warning' : 'info' }}">
                    <i class="bi bi-{{ $order->fulfillment_type == 'stock' ? 'archive' : 'truck' }}"></i>
                    {{ $order->fulfillment_type == 'stock' ? 'Mode Stock' : 'Mode BLJ (Beli langsung jual)' }}
                </span>
                <span class="dms-badge dms-badge-secondary">
                    <i class="bi bi-credit-card"></i>
                    {{ $order->payment_timing == 'pre_paid' ? 'Pre-paid' : 'Post-paid' }}
                </span>
                <span class="dms-badge {{ $order->requiresPacking() ? 'dms-badge-warning' : 'dms-badge-secondary' }}">
                    <i class="bi bi-box2"></i>
                    {{ $order->requiresPacking() ? 'Packing / Repack' : 'Tanpa Packing' }}
                </span>
            </div>
        </div>
        
        <!-- Status Progress Timeline -->
        <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
            @php
                $statusSteps = [];

                if ($order->useStockMode()) {
                    $statusSteps['checking_stock'] = ['label' => 'Alokasi Stok', 'icon' => 'bi-box-seam', 'color' => 'info'];
                    $statusSteps['picking'] = ['label' => 'Picking', 'icon' => 'bi-hand-index-thumb', 'color' => 'info'];
                } else {
                    $statusSteps['procuring'] = ['label' => 'Belanja BLJ', 'icon' => 'bi-cart', 'color' => 'info'];
                }

                if ($order->requiresPacking()) {
                    $statusSteps['repacking'] = ['label' => 'Packing / Repack', 'icon' => 'bi-box', 'color' => 'info'];
                }
                $statusSteps['ready'] = ['label' => 'Siap Kirim', 'icon' => 'bi-check-circle', 'color' => 'success'];
                $statusSteps['shipped'] = ['label' => 'Dalam Pengiriman', 'icon' => 'bi-truck', 'color' => 'success'];

                if ($order->payment_timing == 'post_paid') {
                    $statusSteps['paid'] = ['label' => 'Sudah Bayar', 'icon' => 'bi-credit-card', 'color' => 'info'];
                }

                $statusSteps['delivered'] = ['label' => 'Selesai', 'icon' => 'bi-flag', 'color' => 'success'];

                if ($order->payment_timing == 'pre_paid') {
                    $statusSteps = array_merge([
                        'pending_payment' => ['label' => 'Menunggu Bayar', 'icon' => 'bi-clock', 'color' => 'warning'],
                        'paid' => ['label' => 'Sudah Bayar', 'icon' => 'bi-credit-card', 'color' => 'info'],
                    ], $statusSteps);
                }

                $statusKeys = array_keys($statusSteps);
                $currentStatusIndex = array_search($order->status, $statusKeys);
            @endphp
            
            @foreach($statusSteps as $key => $step)
                @php
                    $stepIndex = array_search($key, $statusKeys);
                    $isCompleted = $stepIndex !== false && $currentStatusIndex !== false && $stepIndex <= $currentStatusIndex;
                    $isActive = $order->status == $key;
                @endphp
                <div style="flex: 1; text-align: center;">
                    <div style="width: 40px; height: 40px; margin: 0 auto; background: {{ $isCompleted ? 'var(--k-green)' : 'var(--k-gray-200)' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi {{ $step['icon'] }}" style="color: {{ $isCompleted ? 'white' : 'var(--k-gray-500)' }}; font-size: 1.2rem;"></i>
                    </div>
                    <div style="font-size: 0.7rem; margin-top: 0.5rem; color: {{ $isActive ? 'var(--k-green)' : 'var(--k-gray-500)' }}; font-weight: {{ $isActive ? '600' : '400' }};">
                        {{ $step['label'] }}
                    </div>
                </div>
                @if(!$loop->last)
                    <div style="flex: 0.5; height: 2px; background: {{ $isCompleted ? 'var(--k-green)' : 'var(--k-gray-200)' }}; margin-top: 20px;"></div>
                @endif
            @endforeach
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Pelanggan Information -->
        <div>
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                <i class="bi bi-person" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                Informasi Pelanggan
            </h4>
            
            <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 0.5rem;">
                    <div style="font-weight: 600; color: var(--k-gray-600);">Nama</div>
                    <div>{{ $order->user->name ?? '-' }}</div>
                    
                    <div style="font-weight: 600; color: var(--k-gray-600);">Telepon</div>
                    <div>{{ $order->user->phone ?? '-' }}</div>
                    
                    <div style="font-weight: 600; color: var(--k-gray-600);">Email</div>
                    <div>{{ $order->user->email ?? '-' }}</div>
                    
                    <div style="font-weight: 600; color: var(--k-gray-600);">Alamat Invoice</div>
                    <div>{{ $order->invoice_address_snapshot ?? $order->address ?? '-' }}</div>

                    <div style="font-weight: 600; color: var(--k-gray-600);">Alamat Kirim</div>
                    <div>
                        {{ $order->shipping_address_snapshot ?? $order->address ?? '-' }}
                        @if($order->shipping_same_as_invoice)
                            <span class="dms-badge dms-badge-info" style="margin-left: 0.35rem;">Sama dengan invoice</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Information -->
        <div>
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
                <i class="bi bi-info-circle" style="margin-right: 0.5rem; color: var(--k-green);"></i>
                Informasi Order
            </h4>
            
            <div style="background: var(--k-gray-50); border-radius: 8px; padding: 1rem;">
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 0.5rem;">
                    <div style="font-weight: 600; color: var(--k-gray-600);">No. Order</div>
                    <div><strong>{{ $order->order_number }}</strong></div>
                    
                    <div style="font-weight: 600; color: var(--k-gray-600);">Tanggal Order</div>
                    <div>{{ $order->created_at->format('d M Y H:i') }}</div>

                    <div style="font-weight: 600; color: var(--k-gray-600);">Sumber Order</div>
                    <div>{{ $order->order_source_label }}</div>

                    <div style="font-weight: 600; color: var(--k-gray-600);">Sales Owner</div>
                    <div>{{ $order->salesperson->name ?? 'House Account' }}</div>

                    @if($order->createdBy)
                    <div style="font-weight: 600; color: var(--k-gray-600);">Input Oleh</div>
                    <div>{{ $order->createdBy->name }}</div>
                    @endif
                    
                    <div style="font-weight: 600; color: var(--k-gray-600);">Pengiriman</div>
                    <div>{{ \Carbon\Carbon::parse($order->delivery_date)->format('d M Y') }} ({{ $order->delivery_time_slot }})</div>
                    
                    <div style="font-weight: 600; color: var(--k-gray-600); display: flex; align-items: center; gap: 0.25rem;">
                        <i class="bi bi-upc-scan" style="color: var(--k-green);"></i>
                        No Resi
                    </div>
                    <div>{{ $order->tracking_code ?? '-' }}</div>

                    @if($order->companyBranch)
                    <div style="font-weight: 600; color: var(--k-gray-600);">Cabang Pengirim</div>
                    <div>
                        {{ $order->companyBranch->name }}
                        @if($order->companyBranch->code)
                            <span class="dms-badge dms-badge-info">{{ $order->companyBranch->code }}</span>
                        @endif
                    </div>
                    @endif

                    <div style="font-weight: 600; color: var(--k-gray-600);">Skema Pembayaran</div>
                    <div>{{ $order->payment_timing == 'pre_paid' ? 'Pre-paid' : 'Post-paid' }}</div>

                    @if($order->requiresPacking())
                    <div style="font-weight: 600; color: var(--k-gray-600);">Packing / Repack</div>
                    <div>Ya</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detail Produk & Tabel Data -->
    <div style="margin-top: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-box-seam" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Detail Produk
        </h4>
        
        <div style="overflow-x: auto;">
            <table class="dms-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--k-gray-100); border-bottom: 1px solid var(--k-gray-200);">
                        <th style="padding: 0.75rem; text-align: left;">Produk</th>
                        <th style="padding: 0.75rem; text-align: center;">Qty</th>
                        <th style="padding: 0.75rem; text-align: right;">Harga Satuan</th>
                        <th style="padding: 0.75rem; text-align: right;">Diskon</th>
                        <th style="padding: 0.75rem; text-align: right;">Subtotal</th>
                        <th style="padding: 0.75rem; text-align: left;">Status</th>
                        @if($order->useJitMode())
                        <th style="padding: 0.75rem; text-align: right;">Harga Beli</th>
                        <th style="padding: 0.75rem; text-align: left;">Supplier</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr style="border-bottom: 1px solid var(--k-gray-200);">
                        <td style="padding: 0.75rem;">
                            <div style="font-weight: 500;">{{ $item->product_name }}</div>
                            @if($item->notes)
                            <div style="font-size: 0.65rem; color: var(--k-gray-500); margin-top: 0.25rem;">
                                <i class="bi bi-chat-dots"></i> {{ $item->notes }}
                            </div>
                            @endif
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">{{ number_format($item->quantity) }}</td>
                        <td style="padding: 0.75rem; text-align: right;">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                        <td style="padding: 0.75rem; text-align: right;">
                            @if($item->discount > 0)
                                <span style="color: var(--k-green);">Rp {{ number_format($item->discount, 0, ',', '.') }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td style="padding: 0.75rem; text-align: right;">
                            <span style="font-weight: 600; color: var(--k-green);">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </span>
                        </td>
                        <td style="padding: 0.75rem;">
                            @if($item->fulfillment_status == 'fulfilled')
                                <span class="dms-badge dms-badge-success">Diambil dari Stock</span>
                            @elseif($item->fulfillment_status == 'procured')
                                <span class="dms-badge dms-badge-info">Dibeli</span>
                            @elseif($item->fulfillment_status == 'pending')
                                <span class="dms-badge dms-badge-warning">Menunggu</span>
                            @elseif($item->fulfillment_status == 'unavailable')
                                <span class="dms-badge dms-badge-danger">Kosong</span>
                            @endif
                        </td>
                        @if($order->useJitMode())
                        <td style="padding: 0.75rem; text-align: right;">
                            @if($item->purchase_price)
                                Rp {{ number_format($item->purchase_price, 0, ',', '.') }}
                            @else
                                <span class="dms-badge dms-badge-warning">Belum diinput</span>
                            @endif
                        </td>
                        <td style="padding: 0.75rem;">{{ $item->supplier_name ?? '-' }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: var(--k-gray-100);">
                        <td colspan="4" style="padding: 0.75rem; text-align: right; font-weight: 600;">Subtotal</td>
                        <td colspan="2" style="padding: 0.75rem; text-align: right; font-weight: 600;">
                            Rp {{ number_format($order->subtotal, 0, ',', '.') }}
                        </td>
                        @if($order->useJitMode())
                        <td colspan="2"></td>
                        @endif
                    </tr>
                    @if($order->discount_amount > 0)
                    <tr style="background: var(--k-gray-100);">
                        <td colspan="4" style="padding: 0.75rem; text-align: right; font-weight: 600;">Diskon Order</td>
                        <td colspan="2" style="padding: 0.75rem; text-align: right; color: var(--k-green);">
                            -Rp {{ number_format($order->discount_amount, 0, ',', '.') }}
                        </td>
                        @if($order->useJitMode())
                        <td colspan="2"></td>
                        @endif
                    </tr>
                    @endif
                    <tr style="background: var(--k-gray-100);">
                        <td colspan="4" style="padding: 0.75rem; text-align: right; font-weight: 600;">Ongkos Kirim</td>
                        <td colspan="2" style="padding: 0.75rem; text-align: right;">
                            Rp {{ number_format($order->delivery_fee, 0, ',', '.') }}
                        </td>
                        @if($order->useJitMode())
                        <td colspan="2"></td>
                        @endif
                    </tr>
                    @if($order->requiresPacking())
                    <tr style="background: var(--k-gray-100);">
                        <td colspan="4" style="padding: 0.75rem; text-align: right; font-weight: 600;">Biaya Packing</td>
                        <td colspan="2" style="padding: 0.75rem; text-align: right;">
                            Rp {{ number_format($order->packing_fee, 0, ',', '.') }}
                        </td>
                        @if($order->useJitMode())
                        <td colspan="2"></td>
                        @endif
                    </tr>
                    @endif
                    @if($order->ppn_amount > 0)
                    <tr style="background: var(--k-gray-100);">
                        <td colspan="4" style="padding: 0.75rem; text-align: right; font-weight: 600;">PPN ({{ $order->ppn_rate }}%)</td>
                        <td colspan="2" style="padding: 0.75rem; text-align: right;">
                            Rp {{ number_format($order->ppn_amount, 0, ',', '.') }}
                        </td>
                        @if($order->useJitMode())
                        <td colspan="2"></td>
                        @endif
                    </tr>
                    @endif
                    <tr style="background: var(--k-green-light); border-top: 2px solid var(--k-green);">
                        <td colspan="4" style="padding: 0.75rem; text-align: right; font-weight: 700;">Grand Total</td>
                        <td colspan="2" style="padding: 0.75rem; text-align: right; font-weight: 700; font-size: 1rem; color: var(--k-green);">
                            Rp {{ number_format($order->grand_total ?? $order->total, 0, ',', '.') }}
                        </td>
                        @if($order->useJitMode())
                        <td colspan="2"></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($bonusPlan->isNotEmpty())
    <div style="margin-top: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin: 0;">
                <i class="bi bi-gift" style="margin-right: 0.5rem; color: var(--k-orange);"></i>
                Bonus Eligible
            </h4>
            @can('create outbound foc')
            <a href="{{ route('outbound-focs.create', ['order_id' => $order->id]) }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-gift"></i> Buat Barang Bonus
            </a>
            @endcan
        </div>

        <div style="background: #fffaf3; border: 1px solid #ffd8ad; border-radius: 10px; overflow: hidden;">
            <div style="padding: 0.9rem 1rem; color: var(--k-gray-700); font-size: 0.85rem; border-bottom: 1px solid #ffd8ad;">
                Order ini memenuhi aturan bonus. Buat transaksi Barang Bonus untuk mengurangi stok dan menyimpan audit pengeluaran bonus.
            </div>
            <table class="dms-table" style="width: 100%; border-collapse: collapse; margin: 0;">
                <thead>
                    <tr>
                        <th style="padding: 0.75rem; text-align: left;">Pemicu</th>
                        <th style="padding: 0.75rem; text-align: left;">Bonus</th>
                        <th style="padding: 0.75rem; text-align: right;">Qty Bonus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bonusPlan as $plan)
                    <tr>
                        <td style="padding: 0.75rem;">
                            {{ $plan['item']->product_name }}
                            <div class="dms-muted">Qty order: {{ number_format($plan['item']->quantity) }}</div>
                        </td>
                        <td style="padding: 0.75rem;">{{ $plan['bonus_product']?->name ?? '-' }}</td>
                        <td style="padding: 0.75rem; text-align: right; font-weight: 700;">{{ number_format($plan['bonus_quantity']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($returnablePackagePlan->isNotEmpty())
    <div style="margin-top: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-recycle" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Rencana Kemasan Kembali
        </h4>

        <div style="background: #fbfdff; border: 1px solid var(--k-gray-200); border-radius: 10px; overflow: hidden;">
            <div style="padding: 0.9rem 1rem; color: var(--k-gray-600); font-size: 0.85rem; border-bottom: 1px solid var(--k-gray-200);">
                Kemasan berikut akan menjadi outstanding customer saat pengiriman selesai.
            </div>
            <div style="overflow-x: auto;">
                <table class="dms-table" style="width: 100%; border-collapse: collapse; margin: 0;">
                    <thead>
                        <tr style="background: var(--k-gray-100);">
                            <th style="padding: 0.75rem; text-align: left;">Kemasan</th>
                            <th style="padding: 0.75rem; text-align: right;">Qty</th>
                            <th style="padding: 0.75rem; text-align: right;">Nilai Pengganti</th>
                            <th style="padding: 0.75rem; text-align: left;">Pemicu Produk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returnablePackagePlan as $plan)
                        <tr style="border-top: 1px solid var(--k-gray-200);">
                            <td style="padding: 0.75rem;">
                                <strong>{{ $plan['package']->name }}</strong><br>
                                <span class="dms-muted">{{ $plan['package']->code }}</span>
                            </td>
                            <td style="padding: 0.75rem; text-align: right; font-weight: 700;">
                                {{ number_format($plan['quantity']) }} {{ $plan['package']->unit }}
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                Rp {{ number_format($plan['quantity'] * $plan['package']->replacement_value, 0, ',', '.') }}
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $plan['items']->pluck('product_name')->unique()->join(', ') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Notes Section -->
    @if($order->notes || $order->admin_notes)
    <div style="margin-top: 2rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--k-gray-800); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--k-gray-200);">
            <i class="bi bi-chat-dots" style="margin-right: 0.5rem; color: var(--k-green);"></i>
            Catatan
        </h4>
        
        @if($order->notes)
        <div style="margin-bottom: 1rem; padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">
                <i class="bi bi-person"></i> Catatan Pelanggan
            </div>
            <p style="color: var(--k-gray-700); line-height: 1.5;">{{ $order->notes }}</p>
        </div>
        @endif
        
        @if($order->admin_notes)
        <div style="padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
            <div style="font-size: 0.7rem; color: var(--k-gray-500); margin-bottom: 0.25rem;">
                <i class="bi bi-shield-check"></i> Catatan Admin
            </div>
            <p style="color: var(--k-gray-700); white-space: pre-line; line-height: 1.5;">{{ $order->admin_notes }}</p>
        </div>
        @endif
    </div>
    @endif
</div>

@if($order->canUpdateStatus())
<div class="dms-next-action-panel">
    <div class="dms-next-action-copy">
        <div class="dms-next-action-icon">
            <i class="bi bi-play-circle"></i>
        </div>
        <div>
            <h4>Aksi Berikutnya</h4>
            <p>Tombol yang tampil mengikuti status order, role user, dan mode pemenuhan.</p>
        </div>
    </div>
    <div class="dms-next-action-buttons">

    {{-- Konfirmasi Pembayaran --}}
    @if(auth()->user()?->canProcessFinance() && (($order->payment_timing == 'pre_paid' && $order->status == 'pending_payment') || ($order->payment_timing == 'post_paid' && $order->status == 'shipped')))
    <form action="{{ route('orders.confirm-payment', $order) }}" method="POST">
        @csrf
        <input type="hidden" name="notes" value="Pembayaran diterima">
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi bi-check-circle"></i> Konfirmasi Pembayaran
        </button>
    </form>
    @endif

    {{-- Mulai proses setelah pre-paid dibayar --}}
    @if($order->payment_timing == 'pre_paid' && $order->status == 'paid' && (($order->useStockMode() && auth()->user()?->canAllocateStock()) || ($order->useJitMode() && auth()->user()?->canProcessProcurement())))
    <form action="{{ route('orders.update-status', $order) }}" method="POST">
        @csrf
        <input type="hidden" name="status" value="{{ $order->useStockMode() ? 'checking_stock' : 'procuring' }}">
        <input type="hidden" name="notes" value="{{ $order->useStockMode() ? 'Mulai alokasi stok' : 'Mulai belanja BLJ' }}">
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi {{ $order->useStockMode() ? 'bi-box-seam' : 'bi-cart' }}"></i>
            {{ $order->useStockMode() ? 'Alokasi Stok' : 'Belanja BLJ' }}
        </button>
    </form>
    @endif
    
    {{-- HANYA untuk mode BLJ: Input Data Belanja --}}
    @if(auth()->user()?->canProcessProcurement() && $order->status == 'procuring' && $order->useJitMode())
    <button onclick="openProcurementModal()" class="dms-btn dms-btn-primary">
        <i class="bi bi-cart"></i> Input Data Belanja
    </button>
    @endif
    
    {{-- Mulai Picking (untuk mode stock setelah alokasi stok) --}}
    @if(auth()->user()?->canPickOrders() && $order->status == 'checking_stock' && $order->useStockMode())
    <form action="{{ route('orders.start-picking', $order) }}" method="POST">
        @csrf
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi bi-hand-index-thumb"></i> Mulai Picking
        </button>
    </form>
    @endif

    {{-- Selesai Picking --}}
    @if(auth()->user()?->canPickOrders() && $order->status == 'picking' && $order->useStockMode())
    <form action="{{ route('orders.mark-picked', $order) }}" method="POST">
        @csrf
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi bi-check2-square"></i> Selesai Picking
        </button>
    </form>
    @endif
    
    {{-- Proses Repack (untuk mode BLJ setelah procurement selesai) --}}
    @if(auth()->user()?->canPackOrders() && $order->requiresPacking() && $order->status == 'procuring' && $order->useJitMode() && $order->items()->where('fulfillment_status', 'pending')->count() == 0)
    <form action="{{ route('orders.process-repack', $order) }}" method="POST">
        @csrf
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi bi-box"></i> Proses Repack
        </button>
    </form>
    @endif
    
    {{-- Siap Kirim --}}
    @if(auth()->user()?->canPackOrders() && ((!$order->requiresPacking() && $order->status == 'procuring') || $order->status == 'repacking'))
    <form action="{{ route('orders.mark-ready', $order) }}" method="POST">
        @csrf
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi bi-check-circle"></i> Siap Kirim
        </button>
    </form>
    @endif
    
    {{-- Kirim Order --}}
    @can('process deliveries')
    @if($order->status == 'ready')
    <button onclick="openShippingModal()" class="dms-btn dms-btn-primary">
        <i class="bi bi-truck"></i> Kirim Order
    </button>
    @endif
    @endcan
    
    {{-- Selesaikan Order --}}
    @can('process deliveries')
    @if(($order->payment_timing == 'pre_paid' && $order->status == 'shipped') || ($order->payment_timing == 'post_paid' && $order->status == 'paid'))
    <form action="{{ route('orders.mark-delivered', $order) }}" method="POST">
        @csrf
        <button type="submit" class="dms-btn dms-btn-primary">
            <i class="bi bi-flag"></i> Selesaikan Order
        </button>
    </form>
    @endif
    @endcan
    </div>
</div>
@endif

<!-- Procurement Modal (untuk BLJ) -->
@if(auth()->user()?->canProcessProcurement())
<div id="procurementModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 1.5rem; width: 600px; max-width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600;">Input Data Belanja</h3>
            <button onclick="closeProcurementModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="procurementForm" action="{{ route('orders.process-procurement', $order) }}" method="POST">
            @csrf
            @foreach($order->items as $index => $item)
            <div style="margin-bottom: 1rem; padding: 0.75rem; background: var(--k-gray-50); border-radius: 8px;">
                <div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 0.8rem;">
                    {{ $item->product_name }} ({{ $item->quantity }} unit)
                </div>
                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div>
                        <label class="form-label" style="font-size: 0.7rem;">Harga Beli (Rp)</label>
                        <input type="number" name="items[{{ $index }}][purchase_price]" class="form-control" required placeholder="Harga beli" style="padding: 0.4rem; font-size: 0.75rem;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 0.7rem;">Nama Pedagang</label>
                        <input type="text" name="items[{{ $index }}][supplier_name]" class="form-control" placeholder="Nama pedagang" style="padding: 0.4rem; font-size: 0.75rem;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label class="form-label" style="font-size: 0.7rem;">Lokasi Pasar</label>
                        <input type="text" name="items[{{ $index }}][market_location]" class="form-control" placeholder="Nama pasar dan nomor lapak" style="padding: 0.4rem; font-size: 0.75rem;">
                    </div>
                </div>
            </div>
            @endforeach
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" onclick="closeProcurementModal()" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;">Batal</button>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.4rem 0.8rem;">Simpan Data</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Shipping Modal -->
@can('process deliveries')
<div id="shippingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 1.5rem; width: 400px; max-width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600;">Konfirmasi Pengiriman</h3>
            <button onclick="closeShippingModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form action="{{ route('orders.mark-shipped', $order) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" style="font-size: 0.7rem;">No Resi (opsional)</label>
                <input type="text" name="tracking_code" class="form-control" placeholder="Isi jika sudah ada nomor resi" style="padding: 0.5rem;">
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" onclick="closeShippingModal()" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;">Batal</button>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.4rem 0.8rem;">Kirim</button>
            </div>
        </form>
    </div>
</div>
@endcan

<script>
function openProcurementModal() {
    document.getElementById('procurementModal').style.display = 'flex';
}

function closeProcurementModal() {
    document.getElementById('procurementModal').style.display = 'none';
}

function openShippingModal() {
    document.getElementById('shippingModal').style.display = 'flex';
}

function closeShippingModal() {
    document.getElementById('shippingModal').style.display = 'none';
}

// Close modal when clicking outside
const procurementModal = document.getElementById('procurementModal');
if (procurementModal) {
    procurementModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeProcurementModal();
        }
    });
}

const shippingModal = document.getElementById('shippingModal');
if (shippingModal) {
    shippingModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeShippingModal();
        }
    });
}
</script>

<style>
.dms-next-action-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.25rem;
    padding: 1rem 1.1rem;
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}

.dms-next-action-copy {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 260px;
}

.dms-next-action-icon {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eaf2ff;
    color: var(--k-blue);
    font-size: 1.05rem;
}

.dms-next-action-copy h4 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--k-gray-900);
}

.dms-next-action-copy p {
    margin: 0.2rem 0 0;
    color: var(--k-gray-500);
    font-size: 0.76rem;
}

.dms-next-action-buttons {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.6rem;
}

.dms-next-action-buttons form {
    margin: 0;
}

.dms-next-action-buttons .dms-btn {
    min-height: 40px;
    white-space: nowrap;
}

.form-group {
    margin-bottom: 0.75rem;
}
.form-label {
    display: block;
    margin-bottom: 0.25rem;
    color: var(--k-gray-700);
    font-size: 0.7rem;
    font-weight: 500;
}
.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--k-gray-300);
    border-radius: 6px;
    font-size: 0.8rem;
    transition: all 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: var(--k-green);
    box-shadow: 0 0 0 2px var(--k-green-light);
}
.dms-table th, .dms-table td {
    vertical-align: middle;
}

@media (max-width: 860px) {
    .dms-next-action-panel {
        flex-direction: column;
        align-items: stretch;
    }

    .dms-next-action-buttons {
        justify-content: flex-start;
    }

    .dms-next-action-buttons .dms-btn,
    .dms-next-action-buttons form {
        width: 100%;
    }

    .dms-next-action-buttons .dms-btn {
        justify-content: center;
    }
}
</style>
@endsection
