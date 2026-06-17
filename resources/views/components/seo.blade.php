@props([
    'title' => __('RiFiTV - Football Scores, Schedules, News and TV Guide'),
    'description' => __('Football scores, schedules, results, news, World Cup 2026 coverage, and TV guide information for Morocco and MENA.'),
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
<meta property="og:locale" content="{{ app()->isLocale('ar') ? 'ar_MA' : 'en_US' }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
<link rel="alternate" hreflang="ar-MA" href="{{ route('language.switch', 'ar') }}">
<link rel="alternate" hreflang="en" href="{{ route('language.switch', 'en') }}">
<link rel="alternate" hreflang="x-default" href="{{ $canonical }}">
<x-schema-jsonld :data="$schema" />
