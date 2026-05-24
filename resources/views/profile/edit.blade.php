@extends('layouts.sidebar')

@section('page-title', 'My Profile')
@section('breadcrumb', 'Profile / Edit')

@section('content')
<div class="dms-card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--dms-secondary);">My Profile</h3>
        <p style="font-size: 0.85rem; color: var(--dms-gray-500);">Kelola informasi profil Anda</p>
    </div>

    @if(session('success'))
    <div style="background: #e6f7e6; border: 1px solid #c3e6c3; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: var(--dms-success);">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
    @endif

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

    <!-- Tab Navigation -->
    <div style="display: flex; gap: 1rem; border-bottom: 2px solid var(--dms-gray-200); margin-bottom: 2rem;">
        <button class="tab-btn active" onclick="showTab('profile')" id="tab-profile" style="background: none; border: none; padding: 0.75rem 1.5rem; cursor: pointer; border-bottom: 2px solid var(--dms-primary); margin-bottom: -2px; font-weight: 600; color: var(--dms-primary);">
            <i class="bi bi-person-circle"></i> Informasi Profil
        </button>
        <button class="tab-btn" onclick="showTab('password')" id="tab-password" style="background: none; border: none; padding: 0.75rem 1.5rem; cursor: pointer; border-bottom: 2px solid transparent; font-weight: 500; color: var(--dms-gray-600);">
            <i class="bi bi-key"></i> Ubah Password
        </button>
        <button class="tab-btn" onclick="showTab('history')" id="tab-history" style="background: none; border: none; padding: 0.75rem 1.5rem; cursor: pointer; border-bottom: 2px solid transparent; font-weight: 500; color: var(--dms-gray-600);">
            <i class="bi bi-clock-history"></i> Riwayat Login
        </button>
    </div>

    <!-- TAB 1: PROFILE INFORMATION -->
    <div id="tab-profile-content" class="tab-content" style="display: block;">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
            @csrf
            @method('PATCH')

            <div style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem;">
                <!-- Left Column - Photo -->
                <div>
                    <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200); position: sticky; top: 2rem;">
                        <div style="text-align: center; margin-bottom: 1rem;">
                            <label style="font-weight: 600; margin-bottom: 1rem; display: block;">Foto Profil</label>
                            
                            <!-- Photo Preview -->
                            <div id="photo-preview" style="width: 150px; height: 150px; margin: 0 auto 1rem; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: var(--dms-shadow); background: var(--dms-primary-light);">
                                @if(auth()->user()->photo)
                                    <img src="{{ asset('storage/' . auth()->user()->photo) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
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
                            
                            @if(auth()->user()->photo)
                            <div style="margin-top: 0.5rem;">
                                <button type="button" class="dms-btn dms-btn-outline" style="width: 100%; color: var(--dms-danger);" onclick="removePhoto()">
                                    <i class="bi bi-trash"></i> Hapus Foto
                                </button>
                            </div>
                            @endif
                            
                            <small style="color: var(--dms-gray-500);">Format: JPG, PNG. Maks: 2MB</small>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Form Fields -->
                <div>
                    <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200);">
                        <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem;">
                            <i class="bi bi-info-circle"></i> Informasi Dasar
                        </h4>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <!-- Name -->
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap <span style="color: var(--dms-danger);">*</span></label>
                                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="form-control" required>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label class="form-label">Email <span style="color: var(--dms-danger);">*</span></label>
                                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="form-control" required>
                            </div>

                            <!-- Username -->
                            <div class="form-group">
                                <label class="form-label">Username <span style="color: var(--dms-danger);">*</span></label>
                                <input type="text" name="username" value="{{ old('username', auth()->user()->username) }}" class="form-control" required>
                                <small>Hanya huruf, angka, strip, underscore</small>
                            </div>

                            <!-- Phone -->
                            <div class="form-group">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}" class="form-control" placeholder="0812xxxxxx">
                            </div>

                            <!-- Gender -->
                            <div class="form-group">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="gender" class="form-control">
                                    <option value="">-- Pilih --</option>
                                    <option value="male" {{ (old('gender', auth()->user()->gender) == 'male') ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="female" {{ (old('gender', auth()->user()->gender) == 'female') ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>

                            <!-- Birth Date -->
                            <div class="form-group">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="birth_date" value="{{ old('birth_date', auth()->user()->birth_date ? auth()->user()->birth_date->format('Y-m-d') : '') }}" class="form-control">
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="form-group" style="margin-top: 1rem;">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Jl. Contoh No. 123, Kota">{{ old('address', auth()->user()->address) }}</textarea>
                        </div>

                        <!-- Submit Button -->
                        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                            <button type="submit" class="dms-btn dms-btn-primary" id="submitProfile">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Form untuk hapus foto -->
        <form action="{{ route('profile.photo.remove') }}" method="POST" id="removePhotoForm" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    </div>

    <!-- TAB 2: CHANGE PASSWORD -->
    <div id="tab-password-content" class="tab-content" style="display: none;">
        <div style="max-width: 500px; margin: 0 auto;">
            <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 2rem; border: 1px solid var(--dms-gray-200);">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem; text-align: center;">
                    <i class="bi bi-key"></i> Ubah Password
                </h4>

                <form action="{{ route('profile.password.update') }}" method="POST" id="passwordForm">
                    @csrf
                    @method('PUT')

                    <!-- Current Password -->
                    <div class="form-group">
                        <label class="form-label">Password Saat Ini <span style="color: var(--dms-danger);">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="current_password" id="current_password" class="form-control" required style="padding-right: 40px;">
                            <span onclick="togglePassword('current_password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label class="form-label">Password Baru <span style="color: var(--dms-danger);">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="new_password" class="form-control" required minlength="8" style="padding-right: 40px;">
                            <span onclick="togglePassword('new_password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                        <small>Minimal 8 karakter (huruf besar, kecil, angka)</small>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password Baru <span style="color: var(--dms-danger);">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required style="padding-right: 40px;">
                            <span onclick="togglePassword('password_confirmation')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Password Strength Meter -->
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; gap: 0.25rem; margin-bottom: 0.25rem;">
                            <div id="strength-1" style="height: 4px; flex: 1; background: var(--dms-gray-300); border-radius: 2px;"></div>
                            <div id="strength-2" style="height: 4px; flex: 1; background: var(--dms-gray-300); border-radius: 2px;"></div>
                            <div id="strength-3" style="height: 4px; flex: 1; background: var(--dms-gray-300); border-radius: 2px;"></div>
                            <div id="strength-4" style="height: 4px; flex: 1; background: var(--dms-gray-300); border-radius: 2px;"></div>
                        </div>
                        <div id="strength-text" style="font-size: 0.8rem; color: var(--dms-gray-500);">Kekuatan password</div>
                    </div>

                    <button type="submit" class="dms-btn dms-btn-primary" style="width: 100%;" id="submitPassword">
                        <i class="bi bi-shield-lock"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 3: LOGIN HISTORY -->
    <div id="tab-history-content" class="tab-content" style="display: none;">
        <div style="background: var(--dms-gray-50); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--dms-gray-200);">
            <h4 style="font-size: 1rem; font-weight: 600; color: var(--dms-secondary); margin-bottom: 1.5rem;">
                <i class="bi bi-clock-history"></i> Riwayat Login
            </h4>

            <div style="overflow-x: auto;">
                <table class="dms-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Waktu Login</th>
                            <th>IP Address</th>
                            <th>Device</th>
                            <th>Platform</th>
                            <th>Browser</th>
                            <th>Waktu Logout</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(auth()->user()->loginHistories()->latest()->limit(20)->get() as $index => $history)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $history->login_at->format('d M Y H:i:s') }}</td>
                            <td><code>{{ $history->ip_address }}</code></td>
                            <td>{{ ucfirst($history->device_type) }}</td>
                            <td>{{ $history->platform }}</td>
                            <td>{{ $history->browser }}</td>
                            <td>
                                @if($history->logout_at)
                                    {{ $history->logout_at->format('d M Y H:i:s') }}
                                @else
                                    <span class="dms-badge dms-badge-success">Online</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">
                                <i class="bi bi-clock-history" style="font-size: 2rem; color: var(--dms-gray-400);"></i>
                                <p style="margin-top: 0.5rem;">Belum ada riwayat login</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Remove Photo Confirmation Modal -->
<div id="removePhotoModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 400px;">
        <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: var(--dms-warning); display: block; text-align: center; margin-bottom: 1rem;"></i>
        <h4 style="text-align: center; margin-bottom: 1rem;">Hapus Foto Profil?</h4>
        <p style="text-align: center; color: var(--dms-gray-600); margin-bottom: 1.5rem;">Foto profil akan dihapus dan diganti dengan default avatar.</p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button onclick="closeRemoveModal()" class="dms-btn dms-btn-outline">Batal</button>
            <button onclick="confirmRemovePhoto()" class="dms-btn dms-btn-danger">Ya, Hapus</button>
        </div>
    </div>
</div>

<script>
// Tab switching
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Show selected tab
    document.getElementById(`tab-${tabName}-content`).style.display = 'block';
    
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.style.borderBottom = '2px solid transparent';
        btn.style.color = 'var(--dms-gray-600)';
        btn.style.fontWeight = '500';
    });
    
    document.getElementById(`tab-${tabName}`).style.borderBottom = '2px solid var(--dms-primary)';
    document.getElementById(`tab-${tabName}`).style.color = 'var(--dms-primary)';
    document.getElementById(`tab-${tabName}`).style.fontWeight = '600';
}

// Photo preview
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

// Remove photo
function removePhoto() {
    document.getElementById('removePhotoModal').style.display = 'flex';
}

function closeRemoveModal() {
    document.getElementById('removePhotoModal').style.display = 'none';
}

function confirmRemovePhoto() {
    document.getElementById('removePhotoForm').submit();
}

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = event.currentTarget.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Password strength meter
document.getElementById('new_password')?.addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    // Reset all bars
    for (let i = 1; i <= 4; i++) {
        document.getElementById(`strength-${i}`).style.background = 'var(--dms-gray-300)';
    }
    
    // Set strength bars
    const colors = ['#e74c3c', '#f39c12', '#f1c40f', '#27ae60'];
    const texts = ['Lemah', 'Cukup', 'Baik', 'Kuat'];
    
    for (let i = 1; i <= strength && i <= 4; i++) {
        document.getElementById(`strength-${i}`).style.background = colors[strength-1];
    }
    
    if (strength > 0) {
        document.getElementById('strength-text').innerHTML = `Kekuatan: ${texts[strength-1]}`;
        document.getElementById('strength-text').style.color = colors[strength-1];
    } else {
        document.getElementById('strength-text').innerHTML = 'Kekuatan password';
        document.getElementById('strength-text').style.color = 'var(--dms-gray-500)';
    }
});

// Loading states
document.getElementById('profileForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitProfile');
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Menyimpan...';
    btn.disabled = true;
});

document.getElementById('passwordForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitPassword');
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Mengupdate...';
    btn.disabled = true;
});

// Check for hash in URL to open specific tab
window.addEventListener('load', function() {
    if (window.location.hash === '#password') {
        showTab('password');
    } else if (window.location.hash === '#history') {
        showTab('history');
    }
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
.dms-btn-danger {
    background: var(--dms-danger);
    color: white;
    border: none;
}
.dms-btn-danger:hover {
    background: #c0392b;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(231, 76, 60, 0.2);
}
</style>
@endsection