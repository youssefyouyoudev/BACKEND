<!DOCTYPE html>
<html class="match-embed-html" lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>{{ $match->home_team }} vs {{ $match->away_team }} - Match option</title>
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js'])
</head>
<body class="match-embed-body">
    <main class="match-embed-shell embed-player-only">
        @php($playerSource = [...$source, 'type' => $source['type'] ?? 'iframe'])
        <x-video-player
            :channel="$match"
            :sources="[$playerSource]"
            :poster="$match->home_flag ?: $match->away_flag"
            embed-only
        />
    </main>
</body>
</html>
