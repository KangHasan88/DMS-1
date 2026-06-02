@extends('layouts.sidebar')

@section('page-title', 'Pengguna')
@section('breadcrumb', 'Administrasi / Pengguna')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Data Pengguna</h3>
            <p class="dms-section-subtitle">Kelola akun internal, status aktif, role, dan keamanan akses.</p>
        </div>
        <a href="{{ route('users.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Tambah User
        </a>
    </div>

    <!-- Search & Filter -->
    <div class="dms-toolbar">
        <form action="{{ route('users.index') }}" method="GET" class="dms-search-form">
                <div class="dms-search-field">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" placeholder="Cari nama, email, username..." 
                           value="{{ request('search') }}"
                           class="form-control">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
            </form>
        <div class="dms-toolbar-actions">
            <!-- Filter Role -->
            <select name="role" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('users.index', array_merge(request()->except('role'), ['role' => null])) }}">Semua Role</option>
                @foreach($roles as $role)
                    <option value="{{ route('users.index', array_merge(request()->except('role'), ['role' => $role->name])) }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                    </option>
                @endforeach
            </select>
            
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('users.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('users.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('users.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            
            <!-- Pilihan Jumlah Data per Halaman -->
            <select name="per_page" onchange="window.location.href = this.value" class="form-control">
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 100])) }}" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Users Table -->
    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>User</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $index => $user)
                <tr>
                    <td>{{ $users->firstItem() + $index }}</td>
                    <td>
                        <div class="dms-identity">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--k-blue-light); display: flex; align-items: center; justify-content: center;">
                                @if($user->photo)
                                    <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                @else
                                    <i class="bi bi-person-circle" style="font-size: 1.5rem; color: var(--k-blue);"></i>
                                @endif
                            </div>
                            <div>
                                <div class="dms-strong">{{ $user->name }}</div>
                                <div class="dms-muted">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->username ?? '-' }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="dms-badge dms-badge-info" style="margin-right: 0.25rem;">
                                {{ ucwords(str_replace('-', ' ', $role->name)) }}
                            </span>
                        @endforeach
                    </td>
                    <td>
                        <div class="status-toggle" data-id="{{ $user->id }}">
                            <span class="dms-badge {{ $user->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}">
                                {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                    </td>
                    <td>
                        @if($user->last_login_at)
                            <div style="font-size: 0.85rem;">{{ $user->last_login_at->format('d M Y H:i') }}</div>
                            <div style="font-size: 0.7rem; color: var(--k-gray-500);">{{ $user->last_login_ip ?? '-' }}</div>
                        @else
                            <span style="color: var(--k-gray-400);">Belum pernah login</span>
                        @endif
                    </td>
                    <td>
                        <div class="dms-actions">
                            <a href="{{ route('users.show', $user) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Lihat Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('users.edit', $user) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($user->id !== auth()->id() && !$user->hasRole('super-admin'))
                                <button onclick="toggleStatus({{ $user->id }})" class="dms-btn dms-btn-outline dms-btn-sm" title="Toggle Status">
                                    <i class="bi bi-power"></i>
                                </button>
                                <button onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="dms-empty-state">
                            <i class="bi bi-people"></i>
                            <p>Tidak ada data user</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination & Info -->
    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} data
        </div>
        
        <div>
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Hidden Form for Delete -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function toggleStatus(userId) {
    if (!confirm('Apakah Anda yakin ingin mengubah status user ini?')) {
        return;
    }
    
    fetch(`/users/${userId}/toggle-status`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Gagal mengubah status');
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan');
    });
}

function deleteUser(userId, userName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus user "${userName}"?`)) {
        return;
    }
    
    const form = document.getElementById('delete-form');
    form.action = `/users/${userId}`;
    form.submit();
}
</script>

@endsection
