@extends('layouts.sidebar')

@section('page-title', 'Peran & Hak Akses')
@section('breadcrumb', 'Administrasi / Peran & Hak Akses')

@section('content')
<div class="dms-card">
    <div class="dms-section-header">
        <div>
            <h3 class="dms-section-title">Peran & Hak Akses</h3>
            <p class="dms-section-subtitle">Atur role, permission, dan batas akses pengguna.</p>
        </div>
        @can('create roles')
        <a href="{{ route('roles.create') }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Role
        </a>
        @endcan
    </div>

    <!-- Search -->
    <div class="dms-toolbar">
        <form method="GET" action="{{ route('roles.index') }}" class="dms-search-form">
            <div class="dms-search-field">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="form-control" placeholder="Nama role..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="dms-btn dms-btn-primary">Cari</button>
        </form>
        <div class="dms-toolbar-actions">
            <a href="{{ route('roles.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-x-circle"></i> Reset
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="dms-table-wrap">
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
                        <div class="dms-identity">
                            <div class="dms-avatar-soft">
                                <i class="bi bi-shield"></i>
                            </div>
                            <div>
                                <div class="dms-strong">
                                    {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                </div>
                                <div class="dms-muted">{{ $role->name }}</div>
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
                        <div class="dms-actions">
                            <a href="{{ route('roles.show', $role) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            
                            @can('edit roles')
                            @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                            <a href="{{ route('roles.edit', $role) }}" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @endcan
                            
                            @can('assign permissions')
                            <a href="{{ route('roles.permissions', $role) }}" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-success);" title="Atur Permission">
                                <i class="bi bi-shield-check"></i>
                            </a>
                            @endcan
                            
                            @can('delete roles')
                            @if($role->name !== 'super-admin' && $role->name !== 'admin')
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm" style="color: var(--k-red);" 
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
                    <td colspan="7">
                        <div class="dms-empty-state">
                            <i class="bi bi-shield"></i>
                            <p>Belum ada data role</p>
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
