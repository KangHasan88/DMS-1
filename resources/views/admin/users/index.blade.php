@extends('layouts.sidebar')

@section('page-title', 'User Management')
@section('breadcrumb', 'Users / List')

@section('content')
<div class="dms-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Daftar User</h3>
            <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Kelola semua user dalam sistem</p>
        </div>
        @can('create users')
        <a href="{{ route('admin.users.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah User
        </a>
        @endcan
    </div>

    <!-- Filter & Search -->
    <div style="background: var(--dms-gray-50); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
        <form method="GET" action="{{ route('admin.users.index') }}">
            <div style="display: grid; grid-template-columns: 1fr 200px 200px auto; gap: 1rem; align-items: end;">
                <!-- Search -->
                <div>
                    <label class="form-label">Cari</label>
                    <input type="text" name="search" class="form-control" placeholder="Nama, email, username..." value="{{ request('search') }}">
                </div>
                
                <!-- Filter Role -->
                <div>
                    <label class="form-label">Role</label>
                    <select name="role" class="form-control">
                        <option value="">Semua Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                {{ ucwords(str_replace('-', ' ', $role)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Filter Status -->
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="dms-btn dms-btn-outline">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div style="overflow-x: auto;">
        <table class="dms-table">
            <thead>
                <tr>
                    <th width="50">#</th>
                    <th>User</th>
                    <th>Kontak</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Terakhir Login</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $index => $user)
                <tr>
                    <td>{{ $users->firstItem() + $index }}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 40px; height: 40px; background: var(--dms-primary-light); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-circle" style="font-size: 1.5rem; color: var(--dms-primary);"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--dms-secondary);">{{ $user->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--dms-gray-500);">{{ $user->username }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>{{ $user->email }}</div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500);">{{ $user->phone ?? '-' }}</div>
                    </td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="dms-badge dms-badge-info" style="margin-right: 0.25rem;">
                                {{ ucwords(str_replace('-', ' ', $role->name)) }}
                            </span>
                        @endforeach
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="dms-badge dms-badge-success">Active</span>
                        @else
                            <span class="dms-badge dms-badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($user->last_login_at)
                            <div>{{ $user->last_login_at->format('d M Y H:i') }}</div>
                            <div style="font-size: 0.7rem; color: var(--dms-gray-500);">{{ $user->last_login_ip ?? '-' }}</div>
                            @if($user->isOnline())
                                <span class="dms-badge dms-badge-success" style="font-size: 0.6rem;">Online</span>
                            @endif
                        @else
                            <span class="dms-badge dms-badge-warning">Belum pernah login</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            @can('edit users')
                            <a href="{{ route('admin.users.edit', $user) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                            
                            @can('activate users')
                            <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem; {{ $user->is_active ? 'color: var(--dms-danger);' : 'color: var(--dms-success);' }}" 
                                        onclick="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} user ini?')">
                                    <i class="bi {{ $user->is_active ? 'bi-lock' : 'bi-unlock' }}"></i>
                                </button>
                            </form>
                            @endcan
                            
                            @can('delete users')
                            @if($user->id !== auth()->id() && !$user->hasRole('super-admin'))
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem; color: var(--dms-danger);" 
                                        onclick="return confirm('Hapus user {{ $user->name }}?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-people" style="font-size: 3rem; color: var(--dms-gray-400); display: block; margin-bottom: 1rem;"></i>
                        <p style="color: var(--dms-gray-500);">Belum ada data user</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="color: var(--dms-gray-600); font-size: 0.85rem;">
            Menampilkan {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} data
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('success'))
<script>
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: '{{ session('success') }}',
    timer: 3000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
});
</script>
@endif

@if(session('error'))
<script>
Swal.fire({
    icon: 'error',
    title: 'Gagal!',
    text: '{{ session('error') }}',
    timer: 3000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
});
</script>
@endif
@endsection