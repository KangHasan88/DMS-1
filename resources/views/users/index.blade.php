@extends('layouts.sidebar')

@section('page-title', 'User Management')
@section('breadcrumb', 'Users')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Daftar Users</h3>
            <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Kelola semua pengguna sistem</p>
        </div>
        <a href="{{ route('users.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Tambah User
        </a>
    </div>

    <!-- Search & Filter -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 250px;">
            <form action="{{ route('users.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
                <div style="position: relative; flex: 1;">
                    <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--dms-gray-400);"></i>
                    <input type="text" name="search" placeholder="Cari nama, email, username..." 
                           value="{{ request('search') }}"
                           style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1px solid var(--dms-gray-300); border-radius: 8px; font-size: 0.9rem;">
                </div>
                <button type="submit" class="dms-btn dms-btn-primary" style="padding: 0.75rem 1.5rem;">Cari</button>
            </form>
        </div>
        
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Filter Role -->
            <select name="role" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--dms-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('users.index', array_merge(request()->except('role'), ['role' => null])) }}">Semua Role</option>
                @foreach($roles as $role)
                    <option value="{{ route('users.index', array_merge(request()->except('role'), ['role' => $role->name])) }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                    </option>
                @endforeach
            </select>
            
            <!-- Filter Status -->
            <select name="status" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--dms-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('users.index', array_merge(request()->except('status'), ['status' => null])) }}">Semua Status</option>
                <option value="{{ route('users.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="{{ route('users.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            
            <!-- Pilihan Jumlah Data per Halaman -->
            <select name="per_page" onchange="window.location.href = this.value" style="padding: 0.75rem 2rem 0.75rem 1rem; border: 1px solid var(--dms-gray-300); border-radius: 8px; font-size: 0.9rem; background: white;">
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 5])) }}" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 per halaman</option>
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 10])) }}" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 per halaman</option>
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 20])) }}" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20 per halaman</option>
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 50])) }}" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 per halaman</option>
                <option value="{{ route('users.index', array_merge(request()->except('per_page'), ['per_page' => 100])) }}" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100 per halaman</option>
            </select>
        </div>
    </div>

    <!-- Users Table -->
    <div style="overflow-x: auto;">
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
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--dms-primary-light); display: flex; align-items: center; justify-content: center;">
                                @if($user->photo)
                                    <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                @else
                                    <i class="bi bi-person-circle" style="font-size: 1.5rem; color: var(--dms-primary);"></i>
                                @endif
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--dms-secondary);">{{ $user->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--dms-gray-500);">{{ $user->email }}</div>
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
                            <div style="font-size: 0.7rem; color: var(--dms-gray-500);">{{ $user->last_login_ip ?? '-' }}</div>
                        @else
                            <span style="color: var(--dms-gray-400);">Belum pernah login</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="{{ route('users.show', $user) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Lihat Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('users.edit', $user) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($user->id !== auth()->id() && !$user->hasRole('super-admin'))
                                <button onclick="toggleStatus({{ $user->id }})" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Toggle Status">
                                    <i class="bi bi-power"></i>
                                </button>
                                <button onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem; color: var(--dms-danger);" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-people" style="font-size: 3rem; color: var(--dms-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--dms-gray-500);">Tidak ada data user</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination & Info -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.9rem; color: var(--dms-gray-600);">
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

<style>
.pagination {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}
.pagination li {
    display: inline-block;
}
.pagination li a, .pagination li span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.5rem;
    border: 1px solid var(--dms-gray-300);
    border-radius: 8px;
    color: var(--dms-gray-600);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.pagination li.active span {
    background: var(--dms-primary);
    color: white;
    border-color: var(--dms-primary);
}
.pagination li a:hover {
    background: var(--dms-gray-100);
    border-color: var(--dms-primary);
}
.pagination .disabled span {
    background: var(--dms-gray-100);
    color: var(--dms-gray-400);
    border-color: var(--dms-gray-200);
    cursor: not-allowed;
}
</style>
@endsection