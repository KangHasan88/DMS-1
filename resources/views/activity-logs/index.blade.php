@extends('layouts.sidebar')

@section('page-title', 'Activity Log')
@section('breadcrumb', 'System / Activity Log')

@section('content')
<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem;">
    <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--k-shadow-md); border: 1px solid var(--k-gray-200);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: var(--k-blue-light); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-clock-history" style="font-size: 1.5rem; color: var(--k-blue);"></i>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 600; color: var(--k-gray-900);">{{ $stats['total'] }}</div>
                <div style="font-size: 0.8rem; color: var(--k-gray-500);">Total Logs</div>
            </div>
        </div>
    </div>
    
    <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--k-shadow-md); border: 1px solid var(--k-gray-200);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: #e6f7e6; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-calendar-day" style="font-size: 1.5rem; color: var(--k-success);"></i>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 600; color: var(--k-gray-900);">{{ $stats['today'] }}</div>
                <div style="font-size: 0.8rem; color: var(--k-gray-500);">Hari Ini</div>
            </div>
        </div>
    </div>
    
    <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--k-shadow-md); border: 1px solid var(--k-gray-200);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: #fff3e0; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-calendar-week" style="font-size: 1.5rem; color: var(--k-warning);"></i>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 600; color: var(--k-gray-900);">{{ $stats['this_week'] }}</div>
                <div style="font-size: 0.8rem; color: var(--k-gray-500);">Minggu Ini</div>
            </div>
        </div>
    </div>
    
    <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--k-shadow-md); border: 1px solid var(--k-gray-200);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: #e8f0fe; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-calendar-month" style="font-size: 1.5rem; color: var(--k-blue);"></i>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 600; color: var(--k-gray-900);">{{ $stats['this_month'] }}</div>
                <div style="font-size: 0.8rem; color: var(--k-gray-500);">Bulan Ini</div>
            </div>
        </div>
    </div>
    
    <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--k-shadow-md); border: 1px solid var(--k-gray-200);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; background: #f3e8ff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-people" style="font-size: 1.5rem; color: #8b5cf6;"></i>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 600; color: var(--k-gray-900);">{{ $stats['unique_users'] }}</div>
                <div style="font-size: 0.8rem; color: var(--k-gray-500);">Unique Users</div>
            </div>
        </div>
    </div>
</div>

<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Activity Log</h3>
            <p class="dms-section-subtitle">Catatan semua aktivitas dalam sistem</p>
        </div>
        @can('delete logs')
        <button onclick="clearOldLogs()" class="dms-btn dms-btn-outline" style="color: var(--k-red);">
            <i class="bi bi-trash"></i> Hapus Log Lama
        </button>
        @endcan
    </div>

    <!-- Filter Form -->
    <div class="dms-toolbar">
        <form method="GET" action="{{ route('activity-logs.index') }}">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div>
                    <label class="form-label">Cari</label>
                    <input type="text" name="search" class="form-control" placeholder="Deskripsi, IP, User..." value="{{ request('search') }}">
                </div>
                
                <div>
                    <label class="form-label">Log Name</label>
                    <select name="log_name" class="form-control">
                        <option value="">Semua</option>
                        @foreach($logNames as $name)
                            <option value="{{ $name }}" {{ request('log_name') == $name ? 'selected' : '' }}>
                                {{ ucfirst($name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="form-label">User</label>
                    <select name="causer_id" class="form-control">
                        <option value="">Semua User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                
                <div>
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                
                <div class="dms-actions">
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('activity-logs.index') }}" class="dms-btn dms-btn-outline">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th width="50">#</th>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Log Name</th>
                    <th>Event</th>
                    <th>Deskripsi</th>
                    <th>IP Address</th>
                    <th width="100">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $log)
                <tr>
                    <td>{{ $logs->firstItem() + $index }}</td>
                    <td>
                        <div>{{ $log->created_at->format('d M Y') }}</div>
                        <div style="font-size: 0.75rem; color: var(--k-gray-500);">{{ $log->created_at->format('H:i:s') }}</div>
                    </td>
                    <td>
                        @if($log->causer)
                            <div class="dms-identity">
                                <div class="dms-avatar-soft">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <div class="dms-strong">{{ $log->causer->name }}</div>
                                    <div class="dms-muted">{{ $log->causer->email }}</div>
                                </div>
                            </div>
                        @else
                            <span class="dms-badge dms-badge-warning">System</span>
                        @endif
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-info">{{ $log->log_name }}</span>
                    </td>
                    <td>
                        @php
                            $eventClass = match($log->event) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                'login' => 'primary',
                                'logout' => 'secondary',
                                default => 'info'
                            };
                        @endphp
                        <span class="dms-badge dms-badge-{{ $eventClass }}">{{ $log->event ?? 'system' }}</span>
                    </td>
                    <td>
                        <div style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $log->description }}
                        </div>
                    </td>
                    <td>
                        <code style="background: var(--k-gray-100); padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.7rem;">
                            {{ $log->ip_address ?? '-' }}
                        </code>
                    </td>
                    <td>
                        <a href="{{ route('activity-logs.show', $log) }}" class="dms-btn dms-btn-outline dms-btn-sm">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="dms-empty-state">
                            <i class="bi bi-clock-history"></i>
                            <p>Belum ada activity log</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} data
        </div>
        <div>
            {{ $logs->links() }}
        </div>
    </div>
</div>

<!-- Clear Logs Modal -->
<div id="clearModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 400px;">
        <h3 style="margin-bottom: 1rem;">Hapus Log Lama</h3>
        <p style="margin-bottom: 1.5rem; color: var(--k-gray-600);">Hapus log yang lebih dari berapa hari?</p>
        <form action="{{ route('activity-logs.clear') }}" method="POST">
            @csrf
            <input type="number" name="days" class="form-control" value="30" min="1" max="365" style="margin-bottom: 1.5rem;">
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeClearModal()" class="dms-btn dms-btn-outline">Batal</button>
                <button type="submit" class="dms-btn dms-btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function clearOldLogs() {
    document.getElementById('clearModal').style.display = 'flex';
}

function closeClearModal() {
    document.getElementById('clearModal').style.display = 'none';
}

// SweetAlert for messages
@if(session('success'))
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: '{{ session('success') }}',
    timer: 3000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
});
@endif

@if(session('error'))
Swal.fire({
    icon: 'error',
    title: 'Gagal!',
    text: '{{ session('error') }}',
    timer: 3000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
});
@endif

@if(session('info'))
Swal.fire({
    icon: 'info',
    title: 'Info',
    text: '{{ session('info') }}',
    timer: 3000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
});
@endif
</script>

<style>
.dms-btn-danger {
    background: var(--k-red);
    color: white;
    border: none;
}
.dms-btn-danger:hover {
    background: #c0392b;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(231, 76, 60, 0.2);
}
</style>
@endsection
