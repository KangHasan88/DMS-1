<x-guest-layout>
    <div class="auth-card-header">
        <h2>Masuk ke DMS</h2>
        <p>Gunakan akun yang sudah terdaftar untuk mengakses dashboard operasional KURMIGO.</p>
    </div>

    @if (session('status'))
        <div class="auth-session">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="auth-field">
            <label for="email" class="auth-label">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@kurmigo.id">
            @error('email') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="auth-field">
            <label for="password" class="auth-label">Password</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password">
            @error('password') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="auth-row">
            <label for="remember_me" class="auth-check">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">Lupa password?</a>
            @endif
        </div>

        <div class="auth-actions">
            <button type="submit" class="auth-button">
                <i class="bi bi-box-arrow-in-right"></i>
                Masuk
            </button>
        </div>
    </form>
</x-guest-layout>
