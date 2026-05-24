@extends('layouts.sidebar')

@section('page-title', 'Riwayat Login')
@section('breadcrumb', 'Profile / Riwayat Login')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Riwayat Login</h3>
            <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Catatan aktivitas login akun Anda</p>
        </div>
        <a href="{{ route('profile.edit') }}" class="dms-btn dms-btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali ke Profil
        </a>
    </div>

    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Waktu Login</th>
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Platform</th>
                    <th>Browser</th>
                    <th>Waktu Logout</th>
                    <th>Durasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($histories as $index => $history)
                <tr>
                    <td>{{ $histories->firstItem() + $index }}</td>
                    <td>{{ $history->login_at->format('d M Y H:i:s') }}</td>
                    <td><code>{{ $history->ip_address }}</code></td>
                    <td>
                        @if($history->device_type == 'mobile')
                            <i class="bi bi-phone"></i> Mobile
                        @elseif($history->device_type == 'tablet')
                            <i class="bi bi-tablet"></i> Tablet
                        @else
                            <i class="bi bi-pc-display"></i> Desktop
                        @endif
                    </td>
                    <td>{{ $history->platform }}</td>
                    <td>{{ $history->browser }}</td>
                    <td>
                        @if($history->logout_at)
                            {{ $history->logout_at->format('d M Y H:i:s') }}
                        @else
                            <span class="dms-badge dms-badge-success">Online</span>
                        @endif
                    </td>
                    <td>
                        @if($history->logout_at)
                            @php
                                $duration = $history->login_at->diffInMinutes($history->logout_at);
                                if ($duration < 60) {
                                    echo $duration . ' menit';
                                } else {
                                    echo floor($duration / 60) . ' jam ' . ($duration % 60) . ' menit';
                                }
                            @endphp
                        @else
                            <span class="dms-badge dms-badge-info">Masih online</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-clock-history" style="font-size: 3rem; color: var(--dms-gray-400); display: block; margin-bottom: 1rem;"></i>
                        <p style="color: var(--dms-gray-500);">Belum ada riwayat login</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="color: var(--dms-gray-600); font-size: 0.85rem;">
            Menampilkan {{ $histories->firstItem() ?? 0 }} - {{ $histories->lastItem() ?? 0 }} dari {{ $histories->total() }} data
        </div>
        <div>
            {{ $histories->links() }}
        </div>
    </div>
</div>
@endsection