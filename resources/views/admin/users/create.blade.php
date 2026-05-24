@extends('layouts.sidebar')

@section('page-title', 'Tambah User')
@section('breadcrumb', 'Users / Tambah')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Tambah User Baru</h3>
        <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Isi form berikut untuk menambahkan user baru</p>
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

    <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" id="createForm">
        @csrf
        
        <div style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem;">
            <!-- Left Column - Photo -->
            <div>
                <div style="text-align: center; padding: 1.5rem; background: var(--dms-gray-50); border-radius: 12px; border: 1px solid var(--dms-gray-200); position: sticky; top: 2rem;">
                    <label style="font-weight: 600; margin-bottom: 1rem; display: block;">Foto Profil</label>
                    
                    <!-- Preview Photo -->
                    <div id="photo-preview" style="width: 150px; height: 150px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: var(--dms-shadow); background: var(--dms-primary-light);">
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-person-circle" style="font-size: 5rem; color: var(--dms-primary);"></i>
                        </div>
                    </div>
                    
                    <!-- Upload Button -->
                    <div style="margin-bottom: 0.5rem;">
                        <label for="photo" class="dms-btn dms-btn-outline" style="cursor: pointer; display: inline-flex; width: 100%; justify-content: center;">
                            <i class="bi bi-upload"></i> Pilih Foto
                        </label>
                        <input type="file" name="photo" id="photo" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>
                    <small style="color: var(--dms-gray-500);">Format: JPG, PNG. Maks: 2MB</small>
                    @error('photo') <div style="color: var(--dms-danger); font-size: 0.75rem; margin-top: 0.5rem;">{{ $message }}</div> @enderror
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
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <!-- Name -->
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap <span style="color: var(--dms-danger);">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="Masukkan nama lengkap">
                            @error('name') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label class="form-label">Email <span style="color: var(--dms-danger);">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required placeholder="contoh@email.com">
                            @error('email') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Username -->
                        <div class="form-group">
                            <label class="form-label">Username <span style="color: var(--dms-danger);">*</span></label>
                            <input type="text" name="username" value="{{ old('username') }}" class="form-control" required placeholder="username123">
                            <small style="color: var(--dms-gray-500);">Hanya huruf, angka, strip, underscore</small>
                            @error('username') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Role -->
                        <div class="form-group">
                            <label class="form-label">Role <span style="color: var(--dms-danger);">*</span></label>
                            <select name="roles[]" class="form-control" required>
                                <option value="">-- Pilih Role --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ collect(old('roles'))->contains($role->name) ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('roles') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: PASSWORD -->
                <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--dms-gray-200);">
                    <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-key" style="color: var(--dms-primary);"></i>
                        Password
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <!-- Password -->
                        <div class="form-group">
                            <label class="form-label">Password <span style="color: var(--dms-danger);">*</span></label>
                            <input type="password" name="password" class="form-control" required placeholder="Minimal 8 karakter">
                            <small style="color: var(--dms-gray-500);">Minimal 8 karakter (huruf besar, kecil, angka)</small>
                            @error('password') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div class="form-group">
                            <label class="form-label">Konfirmasi Password <span style="color: var(--dms-danger);">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" required placeholder="Ulangi password">
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
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="0812xxxxxx">
                            @error('phone') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Gender -->
                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-control">
                                <option value="">-- Pilih --</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('gender') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Birth Date -->
                        <div class="form-group">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="birth_date" value="{{ old('birth_date') }}" class="form-control">
                            @error('birth_date') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <!-- Address (full width) -->
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Jl. Contoh No. 123, Kota">{{ old('address') }}</textarea>
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
                            <input type="text" name="employee_id" value="{{ old('employee_id') }}" class="form-control" placeholder="EMP-001">
                            @error('employee_id') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Position -->
                        <div class="form-group">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="position" value="{{ old('position') }}" class="form-control" placeholder="Staff / Manager">
                            @error('position') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Department -->
                        <div class="form-group">
                            <label class="form-label">Departemen</label>
                            <input type="text" name="department" value="{{ old('department') }}" class="form-control" placeholder="Sales / Finance">
                            @error('department') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Join Date -->
                        <div class="form-group">
                            <label class="form-label">Tanggal Bergabung</label>
                            <input type="date" name="join_date" value="{{ old('join_date') }}" class="form-control">
                            @error('join_date') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Supervisor -->
                        <div class="form-group">
                            <label class="form-label">Atasan</label>
                            <select name="supervisor_id" class="form-control">
                                <option value="">-- Pilih Atasan --</option>
                                @foreach($supervisors ?? [] as $supervisor)
                                    <option value="{{ $supervisor->id }}" {{ old('supervisor_id') == $supervisor->id ? 'selected' : '' }}>
                                        {{ $supervisor->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supervisor_id') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                        </div>

                        <!-- Active Status -->
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0;">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <span style="font-weight: 500;">Aktifkan user ini</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <a href="{{ route('admin.users.index') }}" class="dms-btn dms-btn-outline">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                    <button type="submit" class="dms-btn dms-btn-primary" id="submitBtn">
                        <i class="bi bi-save"></i> Simpan User
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

// Notifikasi sukses/error dari session
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
document.getElementById('createForm')?.addEventListener('submit', function(e) {
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