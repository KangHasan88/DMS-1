@extends('layouts.sidebar')

@section('page-title', 'Role & Permission Management')
@section('breadcrumb', 'Roles / List')

@section('content')
<div class="dms-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Daftar Roles</h3>
            <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Kelola roles dan hak akses dalam sistem</p>
        </div>
        @can('create roles')
        <a href="{{ route('roles.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Role
        </a>
        @endcan
    </div>

    <!-- Search -->
    <div style="background: var(--dms-gray-50); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
        <form method="GET" action="{{ route('roles.index') }}">
            <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; align-items: end;">
                <div>
                    <label class="form-label">Cari Role</label>
                    <input type="text" name="search" class="form-control" placeholder="Nama role..." value="{{ request('search') }}">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="dms-btn dms-btn-primary">
                        <i class="bi bi-search"></i> Cari
                    </button>
                    <a href="{{ route('roles.index') }}" class="dms-btn dms-btn-outline">
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
                    <th>Nama Role</th>
                    <th>Guard</th>
                    <th>Jumlah Permission</th>
                    <th>Jumlah User</th>
                    <th>Dibuat</th>
                    <th width="200">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $index => $role)
                <tr>
                    <td>{{ $roles->firstItem() + $index }}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 36px; height: 36px; background: var(--dms-primary-light); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-shield" style="color: var(--dms-primary);"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--dms-secondary);">
                                    {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                </div>
                                <div style="font-size: 0.7rem; color: var(--dms-gray-500);">{{ $role->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="dms-badge dms-badge-info">{{ $role->guard_name }}</span></td>
                    <td>
                        <span class="dms-badge dms-badge-success">{{ $role->permissions->count() }} permission</span>
                    </td>
                    <td>
                        <span class="dms-badge dms-badge-primary">{{ $role->users_count ?? $role->users()->count() }} user</span>
                    </td>
                    <td>{{ $role->created_at->format('d M Y') }}</td>
                    <td>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="{{ route('roles.show', $role) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            
                            @can('edit roles')
                            @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                            <a href="{{ route('roles.edit', $role) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @endcan
                            
                            @can('assign permissions')
                            <a href="{{ route('roles.permissions', $role) }}" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem; color: var(--dms-success);" title="Atur Permission">
                                <i class="bi bi-shield-check"></i>
                            </a>
                            @endcan
                            
                            @can('delete roles')
                            @if($role->name !== 'super-admin' && $role->name !== 'admin')
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dms-btn dms-btn-outline" style="padding: 0.4rem 0.8rem; color: var(--dms-danger);" 
                                        onclick="return confirm('Hapus role {{ $role->name }}? Semua user dengan role ini akan kehilangan akses.')">
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
                        <i class="bi bi-shield" style="font-size: 3rem; color: var(--dms-gray-400); display: block; margin-bottom: 1rem;"></i>
                        <p style="color: var(--dms-gray-500);">Belum ada data role</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="color: var(--dms-gray-600); font-size: 0.85rem;">
            Menampilkan {{ $roles->firstItem() ?? 0 }} - {{ $roles->lastItem() ?? 0 }} dari {{ $roles->total() }} data
        </div>
        <div>
            {{ $roles->links() }}
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