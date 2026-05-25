@props([
    'title' => 'RifiMedia - Live TV, Football Scores & Sports Streaming',
    'description' => 'Follow football news, live scores, fixtures, standings, match previews, live TV channels, and sports updates on RifiMedia.',
    'canonical' => url()->current(),
    'image' => asset('brand/rifi-logo.png'),
    'type' => 'website',
    'robots' => 'index,follow',
    'schema' => [],
])

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
@if(! empty($schema))
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endif
