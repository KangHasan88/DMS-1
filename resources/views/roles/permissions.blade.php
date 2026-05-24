@extends('layouts.sidebar')

@section('page-title', 'Atur Permission')
@section('breadcrumb', 'Roles / Atur Permission')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">
            Atur Permission untuk Role: {{ ucwords(str_replace('-', ' ', $role->name)) }}
        </h3>
        <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Tentukan permission apa saja yang dimiliki role ini</p>
    </div>

    @if($errors->any())
    <div style="background: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
        <h4 style="color: var(--dms-danger); margin-bottom: 0.5rem;">Terjadi Kesalahan:</h4>
        <ul style="margin-left: 1.5rem; color: var(--dms-danger);">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('roles.permissions.update', $role) }}" method="POST" id="permissionForm">
        @csrf
        @method('PUT')

        @if($role->name === 'super-admin')
        <div style="padding: 3rem; text-align: center; background: var(--dms-gray-50); border-radius: 12px;">
            <i class="bi bi-star-fill" style="font-size: 4rem; color: var(--dms-warning); display: block; margin-bottom: 1rem;"></i>
            <h4 style="font-weight: 600; color: var(--dms-secondary);">Super Admin</h4>
            <p style="color: var(--dms-gray-500); margin-bottom: 1.5rem;">Role Super Admin memiliki semua permission secara otomatis. Tidak perlu diatur.</p>
            <a href="{{ route('roles.index') }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar Role
            </a>
        </div>
        @else
        <!-- Info jumlah permission -->
        <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="dms-badge dms-badge-info" id="selectedCount">{{ count($rolePermissions) }} permission dipilih</span>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="button" class="dms-btn dms-btn-outline" onclick="selectAllPermissions()">
                    <i class="bi bi-check-all"></i> Pilih Semua
                </button>
                <button type="button" class="dms-btn dms-btn-outline" onclick="unselectAllPermissions()">
                    <i class="bi bi-x-circle"></i> Hapus Semua
                </button>
            </div>
        </div>

        <!-- Permissions List -->
        <div style="max-height: 600px; overflow-y: auto; padding: 1rem; background: var(--dms-gray-50); border-radius: 12px;">
            @foreach($allPermissions as $group => $groupPermissions)
            <div style="margin-bottom: 2rem; background: white; border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h5 style="font-size: 1rem; font-weight: 600; color: var(--dms-primary); text-transform: uppercase;">
                        {{ ucfirst($group) }}
                        <span style="font-size: 0.75rem; color: var(--dms-gray-500); margin-left: 0.5rem;">
                            ({{ $groupPermissions->count() }})
                        </span>
                    </h5>
                    <button type="button" class="dms-btn dms-btn-outline" style="font-size: 0.7rem; padding: 0.2rem 0.8rem;" 
                            onclick="toggleGroup('{{ $group }}')">
                        <i class="bi bi-check-square"></i> Pilih Semua di Grup
                    </button>
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
                    @foreach($groupPermissions as $permission)
                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: var(--dms-gray-50); border-radius: 6px; border: 1px solid var(--dms-gray-200); cursor: pointer; transition: all 0.2s;">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="permission-checkbox group-{{ $group }}"
                               {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}
                               onchange="updateSelectedCount()">
                        <span style="font-size: 0.85rem;">{{ $permission->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <!-- Submit Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
            <a href="{{ route('roles.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary" id="submitBtn">
                <i class="bi bi-save"></i> Simpan Permission
            </button>
        </div>
        @endif
    </form>
</div>

<script>
// Update jumlah permission yang dipilih
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.permission-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').innerHTML = count + ' permission dipilih';
}

function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelectedCount();
}

function unselectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectedCount();
}

function toggleGroup(group) {
    const checkboxes = document.querySelectorAll('.group-' + group);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
    updateSelectedCount();
}

// Loading state
document.getElementById('permissionForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Menyimpan...';
        btn.disabled = true;
    }
});

// Inisialisasi count
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<style>
.spin {
    animation: spin 1s linear infinite;
    display: inline-block;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Hover effect untuk checkbox */
label:hover {
    background: var(--dms-primary-light) !important;
    border-color: var(--dms-primary) !important;
}

input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}
</style>
@endsection