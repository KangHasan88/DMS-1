@extends('layouts.sidebar')

@section('page-title', 'Detail User')
@section('breadcrumb', 'Users / Detail')

@section('content')
<div class="dms-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Detail User</h3>
            <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Informasi lengkap pengguna</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.users.edit', $user) }}" class="dms-btn dms-btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('admin.users.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
        <!-- Left Column - Photo & Basic Info -->
        <div>
            <div style="text-align: center; padding: 2rem; background: var(--dms-gray-50); border-radius: 12px; border: 1px solid var(--dms-gray-200);">
                <!-- Photo -->
                <div style="width: 150px; height: 150px; margin: 0 auto 1.5rem; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: var(--dms-shadow);">
                    @if($user->photo)
                        <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div style="width: 100%; height: 100%; background: var(--dms-primary-light); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-person-circle" style="font-size: 5rem; color: var(--dms-primary);"></i>
                        </div>
                    @endif
                </div>
                
                <!-- Name & Role -->
                <h3 style="font-size: 1.3rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 0.5rem;">{{ $user->name }}</h3>
                <div style="margin-bottom: 1rem;">
                    @foreach($user->roles as $role)
                        <span class="dms-badge dms-badge-info" style="margin: 0.25rem;">
                            {{ ucwords(str_replace('-', ' ', $role->name)) }}
                        </span>
                    @endforeach
                </div>
                
                <!-- Status -->
                <div style="margin-bottom: 1rem;">
                    <span class="dms-badge {{ $user->is_active ? 'dms-badge-success' : 'dms-badge-danger' }}" style="font-size: 0.9rem; padding: 0.5rem 1.5rem;">
                        {{ $user->is_active ? 'AKTIF' : 'TIDAK AKTIF' }}
                    </span>
                </div>
                
                <!-- Employee ID -->
                @if($user->employee_id)
                <div style="padding: 0.75rem; background: var(--dms-gray-100); border-radius: 8px; margin-top: 1rem;">
                    <div style="font-size: 0.75rem; color: var(--dms-gray-500);">ID Karyawan</div>
                    <div style="font-weight: 600; color: var(--dms-secondary);">{{ $user->employee_id }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Right Column - Detailed Info -->
        <div>
            <!-- Personal Information -->
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--dms-gray-200);">
                    <i class="bi bi-person-badge" style="margin-right: 0.5rem; color: var(--dms-primary);"></i>
                    Informasi Personal
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Email</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->email }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Username</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->username ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Telepon</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->phone ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Jenis Kelamin</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">
                            @if($user->gender == 'male') Laki-laki
                            @elseif($user->gender == 'female') Perempuan
                            @else -
                            @endif
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Tanggal Lahir</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->birth_date ? date('d M Y', strtotime($user->birth_date)) : '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Alamat</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->address ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Employment Information -->
            <div style="margin-bottom: 2rem;">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--dms-gray-200);">
                    <i class="bi bi-briefcase" style="margin-right: 0.5rem; color: var(--dms-primary);"></i>
                    Informasi Pekerjaan
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Jabatan</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->position ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Departemen</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->department ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Tanggal Bergabung</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->join_date ? date('d M Y', strtotime($user->join_date)) : '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Atasan</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">
                            @if($user->supervisor)
                                {{ $user->supervisor->name }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div>
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--dms-gray-200);">
                    <i class="bi bi-gear" style="margin-right: 0.5rem; color: var(--dms-primary);"></i>
                    Informasi Sistem
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Terakhir Login</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('d M Y H:i') }}
                            @else
                                Belum pernah login
                            @endif
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">IP Terakhir</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->last_login_ip ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Dibuat Pada</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-bottom: 0.25rem;">Diupdate Pada</div>
                        <div style="font-weight: 500; color: var(--dms-secondary);">{{ $user->updated_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons (Bottom) -->
    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--dms-gray-200);">
        @if($user->id !== auth()->id() && !$user->hasRole('super-admin'))
        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" id="deleteForm">
            @csrf
            @method('DELETE')
            <button type="button" onclick="confirmDelete()" class="dms-btn" style="background: #fef2f2; color: var(--dms-danger); border: 1px solid #fee2e2;">
                <i class="bi bi-trash"></i> Hapus User
            </button>
        </form>
        @endif
        <a href="{{ route('admin.users.edit', $user) }}" class="dms-btn dms-btn-primary">
            <i class="bi bi-pencil"></i> Edit User
        </a>
    </div>
</div>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Notifikasi sukses dari session
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

// Notifikasi error dari session
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

// Konfirmasi hapus dengan SweetAlert
function confirmDelete() {
    Swal.fire({
        title: 'Hapus User',
        text: 'Apakah Anda yakin ingin menghapus user ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>

<style>
.dms-table tbody tr:hover {
    background: var(--dms-gray-50);
}
</style>
@endsection