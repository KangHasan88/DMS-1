@extends('layouts.sidebar')

@section('page-title', 'Edit User')
@section('breadcrumb', 'Users / Edit')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">Edit User</h3>
        <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Edit informasi user: {{ $user->name }}</p>
    </div>

    <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem;">
            <!-- Left Column - Photo -->
            <div>
                <div style="text-align: center; padding: 1.5rem; background: var(--dms-gray-50); border-radius: 12px; border: 1px solid var(--dms-gray-200);">
                    <label class="form-label" style="font-weight: 600; margin-bottom: 1rem; display: block;">Foto Profil</label>
                    
                    <!-- Preview Photo -->
                    <div id="photo-preview" style="width: 150px; height: 150px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: var(--dms-shadow); background: var(--dms-primary-light);">
                        @if($user->photo)
                            <img src="{{ asset('storage/' . $user->photo) }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover;" id="preview-image">
                        @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-circle" style="font-size: 5rem; color: var(--dms-primary);"></i>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Upload Button -->
                    <div style="margin-bottom: 0.5rem;">
                        <label for="photo" class="dms-btn dms-btn-outline" style="cursor: pointer; display: inline-flex; width: auto;">
                            <i class="bi bi-upload"></i> Ganti Foto
                        </label>
                        <input type="file" name="photo" id="photo" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>
                    <small style="color: var(--dms-gray-500);">Format: JPG, PNG. Maks: 2MB</small>
                    @error('photo') <div style="color: var(--dms-danger); font-size: 0.75rem; margin-top: 0.5rem;">{{ $message }}</div> @enderror
                </div>
            </div>

            <!-- Right Column - Form Fields -->
            <div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <!-- Name -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">Nama Lengkap <span style="color: var(--dms-danger);">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                        @error('name') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">Email <span style="color: var(--dms-danger);">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                        @error('email') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Username -->
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">Username <span style="color: var(--dms-danger);">*</span></label>
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-control" required>
                        @error('username') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Password (Optional) -->
                    <div class="form-group">
                        <label class="form-label">Password <span style="color: var(--dms-gray-500);">(Kosongkan jika tidak diubah)</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter">
                        <small style="color: var(--dms-gray-500);">Minimal 8 karakter</small>
                        @error('password') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
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
                        <input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date ? date('Y-m-d', strtotime($user->birth_date)) : '') }}" class="form-control">
                        @error('birth_date') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Address -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="3">{{ old('address', $user->address) }}</textarea>
                        @error('address') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Employee ID -->
                    <div class="form-group">
                        <label class="form-label">ID Karyawan</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}" class="form-control">
                        @error('employee_id') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Position -->
                    <div class="form-group">
                        <label class="form-label">Jabatan</label>
                        <input type="text" name="position" value="{{ old('position', $user->position) }}" class="form-control">
                        @error('position') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Department -->
                    <div class="form-group">
                        <label class="form-label">Departemen</label>
                        <input type="text" name="department" value="{{ old('department', $user->department) }}" class="form-control">
                        @error('department') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Join Date -->
                    <div class="form-group">
                        <label class="form-label">Tanggal Bergabung</label>
                        <input type="date" name="join_date" value="{{ old('join_date', $user->join_date ? date('Y-m-d', strtotime($user->join_date)) : '') }}" class="form-control">
                        @error('join_date') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Role -->
                    <div class="form-group">
                        <label class="form-label">Role <span style="color: var(--dms-danger);">*</span></label>
                        <select name="role_id" class="form-control" required>
                            <option value="">-- Pilih Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $userRole ? $userRole->id : '') == $role->id ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Supervisor (optional) -->
                    <div class="form-group">
                        <label class="form-label">Atasan</label>
                        <select name="supervisor_id" class="form-control">
                            <option value="">-- Pilih Atasan --</option>
                            @foreach(App\Models\User::where('id', '!=', $user->id)->get() as $supervisor)
                                <option value="{{ $supervisor->id }}" {{ old('supervisor_id', $user->supervisor_id) == $supervisor->id ? 'selected' : '' }}>
                                    {{ $supervisor->name }} ({{ $supervisor->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('supervisor_id') <span style="color: var(--dms-danger); font-size: 0.75rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Active Status -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                            <span>User aktif</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--dms-gray-200);">
            <a href="{{ route('users.index') }}" class="dms-btn dms-btn-outline">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
            <button type="submit" class="dms-btn dms-btn-primary">
                <i class="bi bi-save"></i> Update User
            </button>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            var preview = document.getElementById('photo-preview');
            preview.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;" id="preview-image">';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<style>
.form-group {
    margin-bottom: 1rem;
}
.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--dms-secondary);
    font-size: 0.9rem;
}
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--dms-gray-300);
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.form-control:focus {
    outline: none;
    border-color: var(--dms-primary);
    box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
}
textarea.form-control {
    resize: vertical;
    min-height: 80px;
}
</style>
@endsection