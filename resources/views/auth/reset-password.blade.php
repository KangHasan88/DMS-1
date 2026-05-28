<x-guest-layout>
    <div class="auth-card-header">
        <h2>Buat Password Baru</h2>
        <p>Gunakan password baru yang kuat untuk menjaga akses DMS tetap aman.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="auth-field">
            <label for="email" class="auth-label">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="auth-field">
            <label for="password" class="auth-label">Password Baru</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password">
            @error('password') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="auth-field">
            <label for="password_confirmation" class="auth-label">Konfirmasi Password</label>
            <input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password">
            @error('password_confirmation') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="auth-actions">
            <button type="submit" class="auth-button">
                <i class="bi bi-shield-check"></i>
                Simpan Password
            </button>
        </div>
    </form>
</x-guest-layout>
