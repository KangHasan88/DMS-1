<x-guest-layout>
    <div class="auth-card-header">
        <h2>Reset Password</h2>
        <p>Masukkan email akun. Sistem akan mengirim tautan untuk membuat password baru.</p>
    </div>

    @if (session('status'))
        <div class="auth-session">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="auth-field">
            <label for="email" class="auth-label">Email</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@kurmigo.id">
            @error('email') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="auth-actions">
            <button type="submit" class="auth-button">
                <i class="bi bi-envelope-check"></i>
                Kirim Link Reset
            </button>
        </div>
    </form>
</x-guest-layout>
