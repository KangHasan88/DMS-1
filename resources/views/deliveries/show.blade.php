@extends('layouts.sidebar')

@section('page-title', 'Detail Pengiriman')
@section('breadcrumb', 'Operasional / Pengiriman / Detail')

@section('content')
@include('deliveries._module-nav')

<div class="dms-card">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.5rem;">
        <div>
            <h3 style="font-size:1.2rem; font-weight:700; color:var(--k-dark); margin:0;">
                Detail Pengiriman
            </h3>
            <p style="font-size:0.86rem; color:var(--k-gray-600); margin-top:0.3rem;">
                Pengiriman #{{ $delivery->id }} - Order #{{ $delivery->order->order_number ?? '-' }}
            </p>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; justify-content:flex-end;">
            @can('edit deliveries')
            @if($delivery->status === \App\Models\Delivery::STATUS_ASSIGNED)
            <a href="{{ route('deliveries.edit', $delivery) }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-person-gear"></i> Ubah Penugasan
            </a>
            @endif
            @endcan
            <a href="{{ route('deliveries.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        <div style="border:1px solid var(--k-gray-200); border-radius:10px; padding:1rem; background:#fbfdff;">
            <div class="dms-muted">Status</div>
            <div style="margin-top:0.4rem;">
                <span class="dms-badge dms-badge-{{ $delivery->status_color }}">{{ $delivery->status_label }}</span>
            </div>
        </div>
        <div style="border:1px solid var(--k-gray-200); border-radius:10px; padding:1rem; background:#fbfdff;">
            <div class="dms-muted">Metode</div>
            <div style="font-weight:700; margin-top:0.4rem;">{{ $delivery->delivery_method_label }}</div>
        </div>
        <div style="border:1px solid var(--k-gray-200); border-radius:10px; padding:1rem; background:#fbfdff;">
            <div class="dms-muted">{{ $delivery->usesExpedition() ? 'Ekspedisi' : 'Kurir' }}</div>
            <div style="font-weight:700; margin-top:0.4rem;">{{ $delivery->usesExpedition() ? ($delivery->vendor?->name ?? '-') : ($delivery->kurir?->name ?? '-') }}</div>
        </div>
        <div style="border:1px solid var(--k-gray-200); border-radius:10px; padding:1rem; background:#fbfdff;">
            <div class="dms-muted">POD</div>
            <div style="font-weight:700; margin-top:0.4rem;">{{ $delivery->pod_receiver_name ?: '-' }}</div>
        </div>
    </div>

    @can('process deliveries')
    @if(in_array($delivery->status, [\App\Models\Delivery::STATUS_ASSIGNED, \App\Models\Delivery::STATUS_PICKED_UP, \App\Models\Delivery::STATUS_IN_TRANSIT], true))
    <div style="border:1px solid var(--k-gray-200); border-radius:12px; padding:1rem; margin-bottom:1.5rem; background:#f8fbff;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem;">
            <div>
                <h4 style="font-size:1rem; font-weight:700; margin:0; color:var(--k-dark);">Proses Pengiriman</h4>
                <p class="dms-muted" style="margin:0.25rem 0 0;">Update status sesuai kondisi lapangan. POD minimal wajib saat pengiriman selesai.</p>
            </div>
        </div>

        @if($delivery->status === \App\Models\Delivery::STATUS_ASSIGNED)
        <form action="{{ route('deliveries.update-status', $delivery) }}" method="POST" style="display:flex; justify-content:flex-end;">
            @csrf
            <input type="hidden" name="status" value="{{ \App\Models\Delivery::STATUS_PICKED_UP }}">
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-box-arrow-up"></i> Barang Diambil
            </button>
        </form>
        @elseif($delivery->status === \App\Models\Delivery::STATUS_PICKED_UP)
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <form action="{{ route('deliveries.update-status', $delivery) }}" method="POST" style="border:1px solid var(--k-gray-200); border-radius:10px; padding:1rem; background:white;">
                @csrf
                <input type="hidden" name="status" value="{{ \App\Models\Delivery::STATUS_IN_TRANSIT }}">
                <div style="font-weight:700; margin-bottom:0.4rem;">Mulai Pengiriman</div>
                <p class="dms-muted" style="min-height:2.5rem;">Barang sudah keluar dari gudang/ekspedisi dan sedang menuju customer.</p>
                <button type="submit" class="dms-btn dms-btn-primary">
                    <i class="bi bi-truck"></i> Mulai Pengiriman
                </button>
            </form>
            <form action="{{ route('deliveries.update-status', $delivery) }}" method="POST" style="border:1px solid var(--k-gray-200); border-radius:10px; padding:1rem; background:white;">
                @csrf
                <input type="hidden" name="status" value="{{ \App\Models\Delivery::STATUS_FAILED }}">
                <div class="form-group">
                    <label class="form-label">Alasan Gagal Kirim <span style="color:var(--k-red);">*</span></label>
                    <input type="text" name="failure_reason" class="form-control @error('failure_reason') is-invalid @enderror" placeholder="Contoh: toko tutup / alamat tidak ditemukan" value="{{ old('failure_reason') }}">
                    @error('failure_reason')<div style="color:var(--k-red); font-size:0.78rem; margin-top:0.3rem;">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="dms-btn dms-btn-outline" style="border-color:#fecaca; color:#b91c1c;">
                    <i class="bi bi-x-circle"></i> Tandai Gagal Kirim
                </button>
            </form>
        </div>
        @elseif($delivery->status === \App\Models\Delivery::STATUS_IN_TRANSIT)
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:1rem;">
            <form action="{{ route('deliveries.update-status', $delivery) }}" method="POST" enctype="multipart/form-data" style="border:1px solid var(--k-gray-200); border-radius:10px; padding:1rem; background:white;">
                @csrf
                <input type="hidden" name="status" value="{{ \App\Models\Delivery::STATUS_COMPLETED }}">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Nama Penerima <span style="color:var(--k-red);">*</span></label>
                        <input type="text" name="pod_receiver_name" class="form-control @error('pod_receiver_name') is-invalid @enderror" placeholder="Nama penerima barang" value="{{ old('pod_receiver_name', $delivery->pod_receiver_name) }}">
                        @error('pod_receiver_name')<div style="color:var(--k-red); font-size:0.78rem; margin-top:0.3rem;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Foto Bukti Pengiriman</label>
                        <input type="file" name="proof_image" class="form-control" accept="image/png,image/jpeg,image/jpg">
                    </div>
                    <div class="form-group" style="grid-column:1 / -1;">
                        <label class="form-label">Catatan POD</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Opsional, contoh: diterima bagian gudang">{{ old('notes', $delivery->notes) }}</textarea>
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; margin-top:1rem;">
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-check-circle"></i> Selesaikan Pengiriman
                    </button>
                </div>
            </form>
            <form action="{{ route('deliveries.update-status', $delivery) }}" method="POST" style="border:1px solid var(--k-gray-200); border-radius:10px; padding:1rem; background:white;">
                @csrf
                <input type="hidden" name="status" value="{{ \App\Models\Delivery::STATUS_FAILED }}">
                <div class="form-group">
                    <label class="form-label">Alasan Gagal Kirim <span style="color:var(--k-red);">*</span></label>
                    <textarea name="failure_reason" class="form-control @error('failure_reason') is-invalid @enderror" rows="4" placeholder="Contoh: customer menolak / toko tutup">{{ old('failure_reason') }}</textarea>
                    @error('failure_reason')<div style="color:var(--k-red); font-size:0.78rem; margin-top:0.3rem;">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="dms-btn dms-btn-outline" style="border-color:#fecaca; color:#b91c1c;">
                    <i class="bi bi-x-circle"></i> Gagal Kirim
                </button>
            </form>
        </div>
        @endif
    </div>
    @endif
    @endcan

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
        <div>
            <h4 style="font-size:1rem; font-weight:700; color:var(--k-dark); margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--k-gray-200);">Informasi Order</h4>
            <div style="background:var(--k-gray-50); border-radius:10px; padding:1rem;">
                <div style="display:grid; grid-template-columns:130px 1fr; gap:0.55rem;">
                    <div style="font-weight:600;">No. Order</div>
                    <div><strong>{{ $delivery->order->order_number ?? '-' }}</strong></div>
                    <div style="font-weight:600;">Total</div>
                    <div>Rp {{ number_format($delivery->order->total ?? 0, 0, ',', '.') }}</div>
                    <div style="font-weight:600;">Ongkir Customer</div>
                    <div>Rp {{ number_format($delivery->order->delivery_fee ?? 0, 0, ',', '.') }}</div>
                    <div style="font-weight:600;">Alamat</div>
                    <div>{{ $delivery->order->address ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div>
            <h4 style="font-size:1rem; font-weight:700; color:var(--k-dark); margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--k-gray-200);">Informasi Pengiriman</h4>
            <div style="background:var(--k-gray-50); border-radius:10px; padding:1rem;">
                <div style="display:grid; grid-template-columns:130px 1fr; gap:0.55rem;">
                    @if($delivery->usesInternalDelivery())
                        <div style="font-weight:600;">Armada</div>
                        <div>{{ $delivery->vehicle ? $delivery->vehicle->code . ' - ' . $delivery->vehicle->name : '-' }}</div>
                    @endif
                    <div style="font-weight:600;">Kontak</div>
                    <div>{{ $delivery->usesExpedition() ? ($delivery->vendor?->phone ?? '-') : ($delivery->kurir?->phone ?? '-') }}</div>
                    <div style="font-weight:600;">No Resi</div>
                    <div>{{ $delivery->tracking_code ?? $delivery->order->tracking_code ?? '-' }}</div>
                    @if($delivery->failed_at)
                        <div style="font-weight:600;">Gagal Pada</div>
                        <div>{{ $delivery->failed_at->format('d M Y H:i') }}</div>
                        <div style="font-weight:600;">Alasan</div>
                        <div>{{ $delivery->failure_reason }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @can('manage journal entries')
    <div style="margin-top:1.5rem; border:1px solid var(--k-gray-200); border-radius:12px; padding:1rem; background:#fbfdff;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem;">
            <div>
                <h4 style="font-size:1rem; font-weight:700; color:var(--k-dark); margin:0;">Biaya Pengiriman</h4>
                <p class="dms-muted" style="margin:0.25rem 0 0;">Catat BBM, parkir, tol, atau biaya driver langsung ke Kas & Bank dengan reference delivery.</p>
            </div>
            <span class="dms-badge dms-badge-info">Ref DLV-{{ $delivery->id }}</span>
        </div>

        @if($cashAccounts->isEmpty() || $expenseAccounts->isEmpty())
            <div class="dms-empty" style="padding:1rem;">
                <i class="bi bi-cash-coin"></i>
                <p>Akun kas/bank atau akun biaya belum tersedia untuk scope cabang ini.</p>
            </div>
        @else
            <form action="{{ route('cash-bank.expenses.store') }}" method="POST">
                @csrf
                <input type="hidden" name="return_delivery_id" value="{{ $delivery->id }}">
                <input type="hidden" name="company_branch_id" value="{{ $delivery->order?->company_branch_id }}">
                <input type="hidden" name="reference_number" value="DLV-{{ $delivery->id }}">

                <div style="display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:0.9rem; align-items:end;">
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="form-control" required>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Sumber Kas/Bank</label>
                        <select name="cash_account_id" class="form-control" required>
                            @foreach($cashAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Akun Biaya</label>
                        <select name="expense_account_id" class="form-control" required>
                            @foreach($expenseAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nominal</label>
                        <input type="number" min="1" name="amount" class="form-control" placeholder="0" required>
                    </div>
                    <div class="form-group" style="grid-column:span 5;">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description', 'Biaya delivery order ' . ($delivery->order->order_number ?? '-')) }}" required>
                    </div>
                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" class="dms-btn dms-btn-primary">
                            <i class="bi bi-cash-coin"></i> Catat Biaya
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
    @endcan

    @if($delivery->pod_receiver_name || $delivery->proof_image)
    <div style="margin-top:1.5rem;">
        <h4 style="font-size:1rem; font-weight:700; color:var(--k-dark); margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--k-gray-200);">Proof of Delivery</h4>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; background:var(--k-gray-50); border-radius:10px; padding:1rem;">
            <div>
                <div class="dms-muted">Penerima</div>
                <div style="font-weight:700;">{{ $delivery->pod_receiver_name ?: '-' }}</div>
                <div class="dms-muted" style="margin-top:0.75rem;">Waktu Terima</div>
                <div>{{ $delivery->pod_received_at ? $delivery->pod_received_at->format('d M Y H:i') : '-' }}</div>
            </div>
            @if($delivery->proof_image)
            <div style="text-align:right;">
                <img src="{{ asset('storage/' . $delivery->proof_image) }}" alt="Bukti Pengiriman" style="max-width:100%; max-height:240px; border-radius:8px; border:1px solid var(--k-gray-200);">
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($returnablePackagePlan->isNotEmpty())
    <div style="margin-top:1.5rem;">
        <h4 style="font-size:1rem; font-weight:700; color:var(--k-dark); margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--k-gray-200);">Rencana Kemasan Kembali</h4>
        <div style="background:#fbfdff; border:1px solid var(--k-gray-200); border-radius:10px; overflow:hidden;">
            <div style="padding:0.9rem 1rem; color:var(--k-gray-600); font-size:0.85rem; border-bottom:1px solid var(--k-gray-200);">
                Saat pengiriman diselesaikan, sistem akan mencatat kemasan ini sebagai outstanding customer.
            </div>
            <div style="overflow-x:auto;">
                <table class="dms-table" style="width:100%; border-collapse:collapse; margin:0;">
                    <thead>
                        <tr style="background:var(--k-gray-100);">
                            <th style="padding:0.75rem; text-align:left;">Kemasan</th>
                            <th style="padding:0.75rem; text-align:right;">Qty</th>
                            <th style="padding:0.75rem; text-align:right;">Nilai Pengganti</th>
                            <th style="padding:0.75rem; text-align:left;">Pemicu Produk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returnablePackagePlan as $plan)
                        <tr style="border-top:1px solid var(--k-gray-200);">
                            <td style="padding:0.75rem;"><strong>{{ $plan['package']->name }}</strong><br><span class="dms-muted">{{ $plan['package']->code }}</span></td>
                            <td style="padding:0.75rem; text-align:right; font-weight:700;">{{ number_format($plan['quantity']) }} {{ $plan['package']->unit }}</td>
                            <td style="padding:0.75rem; text-align:right;">Rp {{ number_format($plan['quantity'] * $plan['package']->replacement_value, 0, ',', '.') }}</td>
                            <td style="padding:0.75rem;">{{ $plan['items']->pluck('product_name')->unique()->join(', ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div style="margin-top:1.5rem;">
        <h4 style="font-size:1rem; font-weight:700; color:var(--k-dark); margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--k-gray-200);">Timeline Pengiriman</h4>
        <div style="background:var(--k-gray-50); border-radius:10px; padding:1rem;">
            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <div style="display:flex; gap:1rem;"><div style="width:32px;"><i class="bi bi-person-check" style="color:var(--k-blue);"></i></div><div><strong>Ditugaskan</strong><div class="dms-muted">{{ $delivery->assigned_at ? $delivery->assigned_at->format('d M Y H:i') : '-' }}</div></div></div>
                @if($delivery->picked_up_at)<div style="display:flex; gap:1rem;"><div style="width:32px;"><i class="bi bi-box-seam" style="color:var(--k-blue);"></i></div><div><strong>Barang Diambil</strong><div class="dms-muted">{{ $delivery->picked_up_at->format('d M Y H:i') }}</div></div></div>@endif
                @if($delivery->in_transit_at)<div style="display:flex; gap:1rem;"><div style="width:32px;"><i class="bi bi-truck" style="color:var(--k-blue);"></i></div><div><strong>Dalam Pengiriman</strong><div class="dms-muted">{{ $delivery->in_transit_at->format('d M Y H:i') }}</div></div></div>@endif
                @if($delivery->completed_at)<div style="display:flex; gap:1rem;"><div style="width:32px;"><i class="bi bi-flag" style="color:var(--k-green);"></i></div><div><strong>Selesai</strong><div class="dms-muted">{{ $delivery->completed_at->format('d M Y H:i') }}</div></div></div>@endif
                @if($delivery->failed_at)<div style="display:flex; gap:1rem;"><div style="width:32px;"><i class="bi bi-x-circle" style="color:var(--k-red);"></i></div><div><strong>Gagal Kirim</strong><div class="dms-muted">{{ $delivery->failed_at->format('d M Y H:i') }} - {{ $delivery->failure_reason }}</div></div></div>@endif
            </div>
        </div>
    </div>

    @if($delivery->notes)
    <div style="margin-top:1.5rem;">
        <h4 style="font-size:1rem; font-weight:700; color:var(--k-dark); margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--k-gray-200);">Catatan</h4>
        <div style="background:var(--k-gray-50); border-radius:10px; padding:1rem;">
            <p style="color:var(--k-gray-700); margin:0;">{{ $delivery->notes }}</p>
        </div>
    </div>
    @endif
</div>
@endsection