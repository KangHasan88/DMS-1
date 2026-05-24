@extends('layouts.sidebar')

@section('page-title', 'Detail Role')
@section('breadcrumb', 'Roles / Detail')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">
                Detail Role: {{ ucwords(str_replace('-', ' ', $role->name)) }}
            </h3>
            <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Informasi lengkap role dan permissionnya</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('roles.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            @can('edit roles')
            @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
            <a href="{{ route('roles.edit', $role) }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-pencil"></i> Edit Role
            </a>
            @endif
            @endcan
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
        <!-- Left Column - Info -->
        <div>
            <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200);">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div style="width: 100px; height: 100px; background: var(--dms-primary-light); border-radius: 30px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-shield" style="font-size: 4rem; color: var(--dms-primary);"></i>
                    </div>
                    <h4 style="font-weight: 600; color: var(--dms-secondary);">{{ ucwords(str_replace('-', ' ', $role->name)) }}</h4>
                    <p style="color: var(--dms-gray-500); font-size: 0.8rem;">{{ $role->name }}</p>
                </div>

                <div style="border-top: 1px solid var(--dms-gray-300); padding-top: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="color: var(--dms-gray-600);">Guard</span>
                        <span class="dms-badge dms-badge-info">{{ $role->guard_name }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="color: var(--dms-gray-600);">Total Permission</span>
                        <span class="dms-badge dms-badge-success">{{ $role->permissions->count() }} permission</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="color: var(--dms-gray-600);">Total User</span>
                        <span class="dms-badge dms-badge-primary">{{ $role->users()->count() }} user</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--dms-gray-600);">Dibuat</span>
                        <span>{{ $role->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Permissions -->
        <div>
            <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200);">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-check2-circle" style="color: var(--dms-primary);"></i>
                    Daftar Permission
                </h4>

                @if($role->name === 'super-admin')
                <div style="padding: 2rem; text-align: center; background: white; border-radius: 8px; border: 1px dashed var(--dms-warning);">
                    <i class="bi bi-star-fill" style="font-size: 3rem; color: var(--dms-warning); display: block; margin-bottom: 1rem;"></i>
                    <h5 style="font-weight: 600; color: var(--dms-secondary);">Super Admin</h5>
                    <p style="color: var(--dms-gray-500);">Role Super Admin memiliki semua permission secara otomatis.</p>
                </div>
                @elseif($role->permissions->isEmpty())
                <div style="padding: 2rem; text-align: center; background: white; border-radius: 8px;">
                    <i class="bi bi-shield-x" style="font-size: 3rem; color: var(--dms-gray-400); display: block; margin-bottom: 1rem;"></i>
                    <p style="color: var(--dms-gray-500);">Role ini belum memiliki permission</p>
                </div>
                @else
                <div style="max-height: 500px; overflow-y: auto;">
                    @foreach($groupedPermissions as $group => $permissions)
                    <div style="margin-bottom: 1.5rem;">
                        <h5 style="font-size: 0.9rem; font-weight: 600; color: var(--dms-primary); text-transform: uppercase; margin-bottom: 0.75rem;">
                            {{ ucfirst($group) }} ({{ $permissions->count() }})
                        </h5>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                            @foreach($permissions as $permission)
                            <div style="padding: 0.5rem; background: white; border-radius: 6px; border: 1px solid var(--dms-gray-200); display: flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-check-circle-fill" style="color: var(--dms-success); font-size: 0.9rem;"></i>
                                <span style="font-size: 0.85rem;">{{ $permission->name }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection