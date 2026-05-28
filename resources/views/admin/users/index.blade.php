@extends('layouts.sidebar')

@section('page-title', 'User Management')
@section('breadcrumb', 'Users / List')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Daftar User</h3>
            <p class="dms-section-subtitle">Kelola semua user dalam sistem</p>
        </div>
        @can('create users')
        <a href="{{ route('admin.users.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah User
        </a>
        @endcan
    </div>

    <!-- Filter & Search -->
    <div class="dms-toolbar">
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
                <div class="dms-actions">
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
    <div class="dms-table-wrap">
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
                        <div class="dms-identity">
                            <div class="dms-avatar-soft">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <div class="dms-strong">{{ $user->name }}</div>
                                <div class="dms-muted">{{ $user->username }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>{{ $user->email }}</div>
                        <div class="dms-muted">{{ $user->phone ?? '-' }}</div>
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
                            <div class="dms-muted">{{ $user->last_login_ip ?? '-' }}</div>
                            @if($user->isOnline())
                                <span class="dms-badge dms-badge-success" style="font-size: 0.6rem;">Online</span>
                            @endif
                        @else
                            <span class="dms-badge dms-badge-warning">Belum pernah login</span>
                        @endif
                    </td>
                    <td>
                        <div class="dms-actions">
                            @can('edit users')
                            <a href="{{ route('admin.users.edit', $user) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                            
                            @can('activate users')
                            <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm" style="{{ $user->is_active ? 'color: var(--k-red);' : 'color: var(--k-success);' }}" 
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
                                <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" 
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
                    <td colspan="7">
                        <div class="dms-empty-state">
                            <i class="bi bi-people"></i>
                            <p>Belum ada data user</p>
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
