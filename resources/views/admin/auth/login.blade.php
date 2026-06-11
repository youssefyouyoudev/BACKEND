<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __("Admin Login | RiFi Media TV") }}</title>
    <meta name="description" content="{{ __("Administrator login for the RiFi Media TV playlist management dashboard.") }}">
    <link rel="icon" type="image/png" href="{{ asset('brand/rifi-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body rm-body rm-auth-body">
    <main class="rm-auth-shell">
        <section class="rm-auth-card">
            <x-logo />
            <span class="rm-live-badge rm-live-badge--gold">{{ __("Control center") }}</span>
            <h1>{{ __("Secure access for RiFi Media operations.") }}</h1>
            <p>{{ __("Manage legal playlist imports, publish approved streams, and keep the live experience clean for viewers.") }}</p>

            <x-flash />

            <form method="POST" action="{{ route('admin.login.store') }}" class="rm-form">
                @csrf

                <div class="field rm-field">
                    <label for="email">{{ __("Email") }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@example.com">
                </div>

                <div class="field rm-field">
                    <label for="password">{{ __("Password") }}</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="{{ __("Enter your password") }}">
                </div>

                <label class="checkbox-field rm-checkbox-field">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>{{ __("Keep me signed in on this device") }}</span>
                </label>

                <button type="submit" class="rm-btn rm-btn-primary rm-btn-full">{{ __("Open Admin Dashboard") }}</button>
            </form>

        </section>

        <section class="rm-auth-preview" aria-label="{{ __("RiFi Media preview") }}">
            <img src="{{ asset('brand/rifi-mockup.png') }}" alt="{{ __("RiFi Media TV interface preview") }}">
            <div class="rm-auth-preview__caption">
                <span class="rm-live-badge"><i></i> {{ __("Live ready") }}</span>
                <strong>{{ __("Premium streaming management") }}</strong>
            </div>
        </section>
    </main>
</body>
</html>
