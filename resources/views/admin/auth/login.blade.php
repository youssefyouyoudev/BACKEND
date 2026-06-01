<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | RiFi Media TV</title>
    <meta name="description" content="Administrator login for the RiFi Media TV playlist management dashboard.">
    <link rel="icon" type="image/png" href="{{ asset('brand/rifi-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body rm-body rm-auth-body">
    <main class="rm-auth-shell">
        <section class="rm-auth-card">
            <x-logo />
            <span class="rm-live-badge rm-live-badge--gold">Control center</span>
            <h1>Secure access for RiFi Media operations.</h1>
            <p>Manage legal playlist imports, publish approved streams, and keep the live experience clean for viewers.</p>

            <x-flash />

            <form method="POST" action="{{ route('admin.login.store') }}" class="rm-form">
                @csrf

                <div class="field rm-field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@example.com">
                </div>

                <div class="field rm-field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                </div>

                <label class="checkbox-field rm-checkbox-field">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Keep me signed in on this device</span>
                </label>

                <button type="submit" class="rm-btn rm-btn-primary rm-btn-full">Open Admin Dashboard</button>
            </form>

        </section>

        <section class="rm-auth-preview" aria-label="RiFi Media preview">
            <img src="{{ asset('brand/rifi-mockup.png') }}" alt="RiFi Media TV interface preview">
            <div class="rm-auth-preview__caption">
                <span class="rm-live-badge"><i></i> Live ready</span>
                <strong>Premium streaming management</strong>
            </div>
        </section>
    </main>
</body>
</html>
