@extends('layouts.sidebar')

@section('page-title', 'Edit User')
@section('breadcrumb', 'Users / Edit')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Edit User</h3>
        <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Edit informasi user: <strong>{{ $user->name }}</strong></p>
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

    <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data" id="editForm">
        @csrf
        @method('PUT')
        
        <div class="dms-form-grid-wide">
            <!-- Left Column - Photo -->
            <div>
                <div style="text-align: center; padding: 1.5rem; background: var(--dms-gray-50); border-radius: 12px; border: 1px solid var(--dms-gray-200); position: sticky; top: 2rem;">
                    <label style="font-weight: 600; margin-bottom: 1rem; display: block;">Foto Profil</label>
                    
                    <!-- Preview Photo -->
                    <div id="photo-preview" style="width: 150px; height: 150px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: var(--dms-shadow);">
                        @if($user->photo)
                            <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div style="width: 100%; height: 100%; background: var(--dms-primary-light); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-circle" style="font-size: 5rem; color: var(--dms-primary);"></i>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Upload Button -->
                    <div style="margin-bottom: 0.5rem;">
                        <label for="photo" class="dms-btn dms-btn-outline" style="cursor: pointer; display: inline-flex; width: 100%; justify-content: center;">
                            <i class="bi bi-upload"></i> Ganti Foto
                        </label>
                        <input type="file" name="photo" id="photo" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>
                    <small style="color: var(--dms-gray-500);">Format: JPG, PNG. Maks: 2MB</small>
                    @error('photo') <div style="color: var(--dms-danger); font-size: 0.75rem; margin-top: 0.5rem;">{{ $message }}</div> @enderror
                    
                    <!-- Info Status -->
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--dms-gray-200);">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            @if($user->is_active)
                                <span class="dms-badge dms-badge-success" style="padding: 0.4rem 1rem;">
                                    <i class="bi bi-check-circle"></i> Aktif
                                </span>
                            @else
                                <span class="dms-badge dms-badge-danger" style="padding: 0.4rem 1rem;">
                                    <i class="bi bi-x-circle"></i> Tidak Aktif
                                </span>
                            @endif
                        </div>
                        <div style="font-size: 0.75rem; color: var(--dms-gray-500); margin-top: 0.5rem; text-align: center;">
                            Terdaftar: {{ $user->created_at->format('d M Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Form Fields -->
            <div>
                <!-- SECTION 1: INFORMASI AKUN -->
                <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--dms-gray-200);">
                    <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-person-badge" style="color: var(--dms-primary);"></i>
                        Informasi Akun
                    </h4>
                    
                    <div class="dms-form-grid">
                        <!-- Name -->
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap <span style="color: var(--dms-danger);">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required placeholder="Masukkan nama lengkap">
                            @error('name') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label class="form-label">Email <span style="color: var(--dms-danger);">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required placeholder="contoh@email.com">
                            @error('email') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Username -->
                        <div class="form-group">
                            <label class="form-label">Username <span style="color: var(--dms-danger);">*</span></label>
                            <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-control" required placeholder="username123">
                            <small style="color: var(--dms-gray-500);">Hanya huruf, angka, strip, underscore</small>
                            @error('username') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Role -->
                        <div class="form-group">
                            <label class="form-label">Role <span style="color: var(--dms-danger);">*</span></label>
                            <select name="roles[]" class="form-control" required>
                                <option value="">-- Pilih Role --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" 
                                        {{ collect(old('roles', $user->roles->pluck('name')->toArray()))->contains($role->name) ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('roles') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cabang Operasional</label>
                            <select name="company_branch_id" class="form-control">
                                <option value="">Semua cabang / pusat</option>
                                @foreach($companyBranches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('company_branch_id', $user->company_branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}{{ $branch->code ? ' - '.$branch->code : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small style="color: var(--dms-gray-500);">Kosongkan untuk Super Admin/HQ. Isi untuk admin atau staff per cabang.</small>
                            @error('company_branch_id') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: PASSWORD (OPSIONAL) -->
                <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--dms-gray-200);">
                    <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-key" style="color: var(--dms-primary);"></i>
                        Ubah Password
                        <span style="font-size: 0.75rem; font-weight: normal; color: var(--dms-gray-500); margin-left: 0.5rem;">(Kosongkan jika tidak ingin mengubah)</span>
                    </h4>
                    
                    <div class="dms-form-grid">
                        <!-- Password -->
                        <div class="form-group">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter">
                            <small style="color: var(--dms-gray-500);">Minimal 8 karakter (huruf besar, kecil, angka)</small>
                            @error('password') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div class="form-group">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password baru">
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: INFORMASI PRIBADI -->
                <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--dms-gray-200);">
                    <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-person-vcard" style="color: var(--dms-primary);"></i>
                        Informasi Pribadi
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <!-- Phone -->
                        <div class="form-group">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control" placeholder="0812xxxxxx">
                            @error('phone') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Gender -->
                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-control">
                                <option value="">-- Pilih --</option>
                                <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('gender') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Birth Date -->
                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date ? $user->birth_date->format('Y-m-d') : '') }}" class="form-control">
                            @error('birth_date') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <!-- Address (full width) -->
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Jl. Contoh No. 123, Kota">{{ old('address', $user->address) }}</textarea>
                        @error('address') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- SECTION 4: INFORMASI PEKERJAAN -->
                <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--dms-gray-200);">
                    <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-briefcase" style="color: var(--dms-primary);"></i>
                        Informasi Pekerjaan
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <!-- Employee ID -->
                        <div class="form-group">
                            <label class="form-label">ID Karyawan</label>
                            <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}" class="form-control" placeholder="EMP-001">
                            @error('employee_id') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Position -->
                        <div class="form-group">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="position" value="{{ old('position', $user->position) }}" class="form-control" placeholder="Staff / Manager">
                            @error('position') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Department -->
                        <div class="form-group">
                            <label class="form-label">Departemen</label>
                            <input type="text" name="department" value="{{ old('department', $user->department) }}" class="form-control" placeholder="Sales / Finance">
                            @error('department') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Join Date -->
                        <div class="form-group">
                            <label class="form-label">Tanggal Bergabung</label>
                            <input type="date" name="join_date" value="{{ old('join_date', $user->join_date ? $user->join_date->format('Y-m-d') : '') }}" class="form-control">
                            @error('join_date') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Supervisor -->
                        <div class="form-group">
                            <label class="form-label">Atasan</label>
                            <select name="supervisor_id" class="form-control">
                                <option value="">-- Pilih Atasan --</option>
                                @foreach($supervisors ?? [] as $supervisor)
                                    <option value="{{ $supervisor->id }}" 
                                        {{ old('supervisor_id', $user->supervisor_id) == $supervisor->id ? 'selected' : '' }}>
                                        {{ $supervisor->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supervisor_id') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Active Status -->
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0;">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                <span style="font-weight: 500;">User aktif</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- INFO TAMBAHAN (LAST LOGIN) -->
                @if($user->last_login_at)
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 1rem; margin-bottom: 2rem; color: white;">
                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <i class="bi bi-clock-history" style="font-size: 1.5rem;"></i>
                        <div>
                            <small>Terakhir Login</small>
                            <div style="font-weight: 600;">{{ $user->last_login_at->format('d M Y H:i') }}</div>
                        </div>
                        <div style="margin-left: auto;">
                            <small>IP Address</small>
                            <div style="font-weight: 600;">{{ $user->last_login_ip ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Buttons -->
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <a href="{{ route('admin.users.index') }}" class="dms-btn dms-btn-outline">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                    <button type="submit" class="dms-btn dms-btn-primary" id="submitBtn">
                        <i class="bi bi-save"></i> Update User
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photo-preview').innerHTML = 
                '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;">';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

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
}).then(() => {
    window.location.href = '{{ route("admin.users.index") }}';
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

// Tampilkan error validasi
@if($errors->any())
Swal.fire({
    icon: 'error',
    title: 'Validasi Gagal',
    html: '<ul style="text-align: left; max-height: 200px; overflow-y: auto;">@foreach($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul>',
    confirmButtonColor: '#3085d6',
    confirmButtonText: 'OK'
});
@endif

// Loading state saat submit
document.getElementById('editForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Menyimpan...';
    btn.disabled = true;
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    animation: spin 1s linear infinite;
    display: inline-block;
}
.form-group {
    margin-bottom: 0;
}
.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--dms-secondary);
    font-size: 0.85rem;
    font-weight: 600;
}
.form-control {
    width: 100%;
    padding: 0.6rem 1rem;
    border: 1px solid var(--dms-gray-300);
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s;
    background: white;
}
.form-control:focus {
    outline: none;
    border-color: var(--dms-primary);
    box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
}
.form-control:disabled {
    background: var(--dms-gray-100);
    cursor: not-allowed;
}
textarea.form-control {
    resize: vertical;
    min-height: 60px;
}
select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
}
</style>
@endsection