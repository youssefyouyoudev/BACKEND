<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $source['title'] ?? $match->home_team.' vs '.$match->away_team }} - {{ __('Player') }}</title>
    @include('partials.theme-init')
    <script>
        window.rifiLocale = @js(app()->getLocale());
        window.rifiTranslations = Object.assign({}, window.rifiTranslations || {}, @js([
            'This server is not available. Try another server.' => __('This server is not available. Try another server.'),
            'Watch links will appear here before kickoff.' => __('Watch links will appear here before kickoff.'),
        ]));
    </script>
    @vite(['resources/css/app.css', 'resources/css/theme.css', 'resources/js/app.js'])
</head>
<body class="match-embed-body">
    <main class="match-embed-shell">
        @php($playerSource = [...$source, 'type' => $source['type'] ?? 'iframe'])
        <x-video-player
            :channel="$match"
            :sources="[$playerSource]"
            :poster="$match->home_flag ?: $match->away_flag"
        />
    </main>
</body>
</html>
