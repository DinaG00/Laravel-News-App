<x-guest-layout>
    <div class="auth-header">
        <h2>Register</h2>
        <p>Create an account to save your favourite news.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="auth-group">
            <label for="name" class="auth-label">Name</label>
            <input id="name" class="auth-input @error('name') input-error @enderror" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
            @error('name')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="auth-group">
            <label for="email" class="auth-label">Email</label>
            <input id="email" class="auth-input @error('email') input-error @enderror" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
            @error('email')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="auth-group">
            <label for="password" class="auth-label">Password</label>
            <input id="password" class="auth-input @error('password') input-error @enderror" type="password" name="password" required autocomplete="new-password" />
            @error('password')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="auth-group">
            <label for="password_confirmation" class="auth-label">Confirm Password</label>
            <input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <div class="auth-links">
            <a href="{{ route('login') }}">Already registered?</a>
        </div>

        <button type="submit" class="auth-btn-submit">Register</button>
    </form>

    <div class="auth-footer">
        Already a member? <a href="{{ route('login') }}">Log in</a>
    </div>
</x-guest-layout>
