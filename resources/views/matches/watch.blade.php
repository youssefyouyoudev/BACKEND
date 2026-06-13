@extends('layouts.app')

@section('title', "{$match->home_team} vs {$match->away_team} - Watch Match - RiFiTV")
@section('description', "Watch {$match->home_team} vs {$match->away_team} match info, kickoff time, channel and commentator in Morocco time on RiFiTV.")
@section('image', $match->home_flag ?: asset('assets/images/promo/rifitv-world-football-2026-1122.webp'))

@section('content')
<div class="match-watch-page">
    <nav class="football-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span>
        <a href="{{ route('world-cup.index') }}">{{ __('Matches') }}</a><span>/</span>
        <span>{{ $match->home_team }} vs {{ $match->away_team }}</span>
    </nav>

    <section class="match-watch-hero match-hero-card">
        <div class="match-watch-hero__topline">
            <span>{{ $match->competition }}{{ $match->stage ? ' · '.$match->stage : '' }}</span>
            <b class="match-window-badge match-window-badge--{{ $status === 'open' ? 'live' : ($status === 'expired' ? 'ended' : 'soon') }}">
                {{ $status === 'open'
                    ? (app()->isLocale('ar') ? 'مباشر الآن' : 'Live Now')
                    : ($status === 'expired'
                        ? (app()->isLocale('ar') ? 'انتهت المباراة' : 'Match Ended')
                        : (app()->isLocale('ar') ? 'تفتح قريباً' : 'Opens Soon')) }}
            </b>
        </div>
        <div class="match-scoreboard">
            <div class="match-scoreboard__team"><x-team-flag :team="$match->home_team" :src="$match->home_flag" size="lg" /><strong>{{ $match->home_team }}</strong></div>
            <div class="match-scoreboard__center"><span>{{ $match->kickoff_at_morocco?->translatedFormat('D, M j') }}</span><b>{{ $match->kickoff_at_morocco?->format('H:i') }}</b><small>{{ app()->isLocale('ar') ? 'بتوقيت المغرب' : 'Morocco Time' }}</small></div>
            <div class="match-scoreboard__team"><x-team-flag :team="$match->away_team" :src="$match->away_flag" size="lg" /><strong>{{ $match->away_team }}</strong></div>
        </div>
        <div class="match-watch-facts">
            @if($match->venue)<span><x-icon name="location" /> {{ collect([$match->venue, $match->city])->filter()->implode(', ') }}</span>@endif
            <span><x-icon name="tv" /> {{ $match->public_channel_name }}</span>
            @if($match->commentator)<span><x-icon name="user" /> {{ $match->commentator }}</span>@endif
        </div>
    </section>

    <x-ad-slot name="match_watch_before_content" type="inline" compact />

    @if($status === 'opens_soon')
        <section class="match-watch-state">
            <span class="rm-kicker">{{ app()->isLocale('ar') ? 'موعد فتح المشاهدة' : 'Watch window' }}</span>
            <h2>{{ app()->isLocale('ar') ? 'تفتح صفحة المشاهدة على الساعة' : 'Watch page opens at' }} {{ $match->watch_opens_at?->format('H:i') }}</h2>
            <p>{{ app()->isLocale('ar') ? 'بتوقيت المغرب' : 'Morocco time' }}</p>
            <div class="match-countdown" data-countdown data-countdown-target="{{ $match->watch_opens_at?->toIso8601String() }}">
                @foreach(['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds'] as $field => $label)
                    <span><b data-countdown-{{ $field }}>00</b><small>{{ __($label) }}</small></span>
                @endforeach
            </div>
            <button type="button" class="rtv-button rtv-button--secondary" disabled><x-icon name="clock" /> {{ app()->isLocale('ar') ? 'أضف تذكيراً' : 'Add to reminder' }}</button>
        </section>
    @elseif($status === 'open')
        <section class="match-watch-player">
            <div class="match-watch-player__heading">
                <div><span class="rm-kicker">{{ app()->isLocale('ar') ? 'المشاهدة المباشرة' : 'Watch live' }}</span><h2>{{ $match->home_team }} vs {{ $match->away_team }}</h2></div>
                <small>{{ app()->isLocale('ar') ? 'حدّث الصفحة إذا لم يعمل البث.' : 'Refresh the page if the stream does not load.' }}</small>
            </div>
            @if($sources->isNotEmpty())
                <x-video-player :channel="$match" :sources="$sources" :poster="$match->home_flag ?: $match->away_flag" />
                <div class="match-watch-servers">
                    @foreach($watchItems as $index => $item)
                        <span>{{ __('Server :number', ['number' => $index + 1]) }} · {{ $item->qualityLabel() }} · {{ $item->name }}</span>
                    @endforeach
                </div>
            @elseif($manualWatchUrl)
                <a class="rtv-button rtv-button--primary" href="{{ $manualWatchUrl }}" target="_blank" rel="nofollow noopener noreferrer"><x-icon name="play" /> {{ app()->isLocale('ar') ? 'شاهد المباراة' : 'Watch Match' }}</a>
            @else
                <div class="match-watch-empty">{{ app()->isLocale('ar') ? 'ستظهر روابط المشاهدة هنا قبل انطلاق المباراة.' : 'Watch links will appear here before kickoff.' }}</div>
            @endif
        </section>
    @else
        <section class="match-watch-state match-watch-state--ended">
            <span class="rm-kicker">{{ app()->isLocale('ar') ? 'انتهت نافذة المشاهدة' : 'Watch window closed' }}</span>
            <h2>{{ app()->isLocale('ar') ? 'انتهت هذه المباراة.' : 'This match has ended.' }}</h2>
            <p>{{ app()->isLocale('ar') ? 'اختر مباراة قادمة من القائمة أدناه.' : 'Choose an upcoming match below.' }}</p>
        </section>
    @endif

    <x-ad-slot name="match_watch_under_content" type="inline" compact />
    <section class="match-watch-premium">
        <span><x-icon name="message" /></span>
        <div><strong>{{ app()->isLocale('ar') ? 'لجودة Premium تواصل معنا عبر واتساب.' : 'For premium quality contact us on WhatsApp.' }}</strong><small>RiFiMedia · 0663323824</small></div>
        <a href="https://wa.me/212663323824" target="_blank" rel="noopener noreferrer">{{ app()->isLocale('ar') ? 'تواصل معنا' : 'Contact us' }}</a>
    </section>

    @if($upcomingMatches->isNotEmpty())
        <section class="match-watch-upcoming">
            <div class="rtv-section-heading"><div><span class="rtv-kicker">{{ __('Up next') }}</span><h2>{{ app()->isLocale('ar') ? 'المباريات القادمة' : 'Upcoming Matches' }}</h2></div></div>
            <div class="rtv-match-grid">
                @foreach($upcomingMatches as $upcoming)
                    <a class="match-watch-suggestion" href="{{ route('matches.watch', $upcoming) }}">
                        <span>{{ $upcoming->competition }}</span>
                        <strong>{{ $upcoming->home_team }} <small>vs</small> {{ $upcoming->away_team }}</strong>
                        <b>{{ $upcoming->kickoff_at_morocco?->translatedFormat('M j · H:i') }} {{ app()->isLocale('ar') ? 'المغرب' : 'Morocco' }}</b>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
