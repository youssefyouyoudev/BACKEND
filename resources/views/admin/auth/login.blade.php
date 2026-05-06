<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | RiFi Media TV</title>
    <meta name="description" content="Administrator login for the RiFi Media TV playlist management dashboard.">
    <link rel="icon" type="image/png" href="{{ asset('brand/rifi-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body auth-body">
    <main class="auth-shell">
        <section class="auth-panel">
            <div class="auth-panel__content">
                <x-logo />
                <p class="auth-panel__eyebrow">Admin playlist operations</p>
                <h1>Secure access to your IPTV control center.</h1>
                <p class="auth-panel__copy">
                    Add legal playlist URLs, parse M3U catalogs in the background, and publish a clean viewing experience for your users.
                </p>

                <x-flash />

                <form method="POST" action="{{ route('admin.login.store') }}" class="form-card">
                    @csrf

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="admin@rifimedia.test">
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                    </div>

                    <label class="checkbox-field">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Keep me signed in on this device</span>
                    </label>

                    <button type="submit" class="button button--primary button--full">Open Admin Dashboard</button>
                </form>

                <p class="auth-panel__hint">Demo admin: <strong>admin@rifimedia.test</strong> / <strong>Password123!</strong></p>
            </div>
        </section>

        <section class="auth-preview">
            <div class="auth-preview__glow"></div>
            <img src="{{ asset('brand/rifi-mockup.png') }}" alt="RiFi Media TV interface preview" class="auth-preview__image">
        </section>
    </main>
</body>
</html>
