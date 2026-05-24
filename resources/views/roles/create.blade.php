@extends('layouts.sidebar')

@section('page-title', 'Tambah Role Baru')
@section('breadcrumb', 'Roles / Tambah')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Tambah Role Baru</h3>
        <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Buat role baru dan atur permissionnya</p>
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

    <form action="{{ route('roles.store') }}" method="POST" id="createForm">
        @csrf

        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
            <!-- Left Column - Info -->
            <div>
                <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200); position: sticky; top: 2rem;">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 80px; height: 80px; background: var(--dms-primary-light); border-radius: 20px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-shield" style="font-size: 3rem; color: var(--dms-primary);"></i>
                        </div>
                        <h4 style="font-weight: 600; color: var(--dms-secondary);">Informasi Role</h4>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Role <span style="color: var(--dms-danger);">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="contoh: marketing">
                        <small style="color: var(--dms-gray-500);">Hanya huruf kecil, angka, dan strip</small>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="dms-btn dms-btn-primary" style="width: 100%;" id="submitBtn">
                            <i class="bi bi-save"></i> Simpan Role
                        </button>
                        <a href="{{ route('roles.index') }}" class="dms-btn dms-btn-outline" style="width: 100%; margin-top: 0.5rem;">
                            <i class="bi bi-arrow-left"></i> Batal
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column - Permissions -->
            <div>
                <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200);">
                    <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-check2-circle" style="color: var(--dms-primary);"></i>
                        Permission untuk Role Ini
                    </h4>

                    <!-- Select All / Unselect All -->
                    <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--dms-gray-300); display: flex; gap: 1rem;">
                        <button type="button" class="dms-btn dms-btn-outline" onclick="selectAllPermissions()">
                            <i class="bi bi-check-all"></i> Pilih Semua
                        </button>
                        <button type="button" class="dms-btn dms-btn-outline" onclick="unselectAllPermissions()">
                            <i class="bi bi-x-circle"></i> Hapus Semua
                        </button>
                    </div>

                    <!-- Permissions List -->
                    <div style="max-height: 500px; overflow-y: auto;">
                        @foreach($permissions as $group => $groupPermissions)
                        <div style="margin-bottom: 1.5rem;">
                            <h5 style="font-size: 0.9rem; font-weight: 600; color: var(--dms-primary); text-transform: uppercase; margin-bottom: 0.75rem;">
                                {{ ucfirst($group) }}
                            </h5>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                                @foreach($groupPermissions as $permission)
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: white; border-radius: 6px; border: 1px solid var(--dms-gray-200); cursor: pointer;">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                                           {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
                                    <span style="font-size: 0.85rem;">{{ $permission->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function selectAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function unselectAllPermissions() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Loading state
document.getElementById('createForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Menyimpan...';
    btn.disabled = true;
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
</style>
@endsection