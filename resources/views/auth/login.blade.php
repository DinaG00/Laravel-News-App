<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="auth-header">
        <h2>Log In</h2>
        <p>Sign in to your account to access saved news.</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="auth-group">
            <label for="email" class="auth-label">Email</label>
            <input id="email" class="auth-input @error('email') input-error @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
            @error('email')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="auth-group">
            <label for="password" class="auth-label">Password</label>
            <input id="password" class="auth-input @error('password') input-error @enderror" type="password" name="password" required autocomplete="current-password" />
            @error('password')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="auth-group">
            <label for="remember_me" class="remember-label">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Remember me</span>
            </label>
        </div>

        <div class="auth-links">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Forgot your password?</a>
            @endif
        </div>

        <button type="submit" class="auth-btn-submit">Log In</button>
    </form>

    <div class="auth-footer">
        Don't have an account? <a href="{{ route('register') }}">Register</a>
    </div>
</x-guest-layout>
