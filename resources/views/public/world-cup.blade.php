@extends('layouts.app')

@section('title', __("World Cup 2026 Group Stage Schedule | RiFiTV"))
@section('description', __("World Cup 2026 group-stage matches, Morocco kickoff times, selected channels, commentators, and approved watch links."))
@section('image', asset('assets/images/promo/rifitv-world-football-2026-1122.webp'))

@section('content')
@php
    $matchesByDate = $matches->groupBy(fn ($match) => $match->morocco_kickoff_at?->toDateString() ?: 'tbc');
@endphp

<div class="wc-schedule-page">
    <section class="wc-schedule-hero">
        <div>
            <span class="wc-badge"><b>{{ __("Live schedule") }}</b> {{ __("Morocco time") }}</span>
            <h1>{{ __("World Cup 2026") }} <span>{{ __("Group Stage") }}</span></h1>
            <p>{{ __("Watch schedule, channels, commentators and match times. Channel links appear only after an administrator confirms an approved source.") }}</p>
            <div class="wc-hero__actions">
                <a class="wc-button wc-button--primary" href="#world-cup-schedule">{{ app()->isLocale('ar') ? 'شوف الجدول' : 'View schedule' }}</a>
                <a class="wc-button wc-button--ghost" href="{{ config('ads.sponsor_url') }}" target="_blank" rel="nofollow sponsored noopener noreferrer">{{ app()->isLocale('ar') ? 'سجّل دابا' : 'See premium offer' }}</a>
            </div>
        </div>
        <x-promo-banner compact />
    </section>

    <x-ad-slot name="world_cup_after_hero" type="banner" />

    <nav class="wc-schedule-tabs" aria-label="{{ __("Schedule views") }}">
        @foreach(['today' => 'Today', 'upcoming' => 'Upcoming', 'all' => 'All Matches', 'groups' => 'Groups'] as $value => $label)
            <a class="{{ $tab === $value ? 'is-active' : '' }}" href="{{ route('world-cup.index', array_merge(request()->except('page'), ['tab' => $value])) }}">{{ __($label) }}</a>
        @endforeach
    </nav>

    <section class="wc-public-filters">
        <form method="GET" action="{{ route('world-cup.index') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="field">
                <label for="wc-search">{{ __("Search team") }}</label>
                <input id="wc-search" name="search" value="{{ request('search') }}" placeholder="{{ __("Morocco, Brazil, France...") }}">
            </div>
            <div class="field">
                <label for="wc-group">{{ __("Group") }}</label>
                <select id="wc-group" name="group">
                    <option value="">{{ __("All groups") }}</option>
                    @foreach($groups as $group)
                        <option value="{{ $group }}" @selected(request('group') === $group)>{{ $group }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="wc-date">{{ __("Date") }}</label>
                <select id="wc-date" name="date">
                    <option value="">{{ __("All dates") }}</option>
                    @foreach($dates as $date)
                        <option value="{{ $date }}" @selected(request('date') === $date)>{{ \Illuminate\Support\Carbon::parse($date)->format('M d, Y') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="wc-channel">{{ __("Channel") }}</label>
                <select id="wc-channel" name="channel">
                    <option value="">{{ __("All channels") }}</option>
                    @foreach($channels as $channel)
                        <option value="{{ $channel->id }}" @selected((string) request('channel') === (string) $channel->id)>{{ $channel->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="wc-button wc-button--primary" type="submit">{{ __("Show matches") }}</button>
            <a class="wc-button wc-button--ghost" href="{{ route('world-cup.index', ['tab' => $tab]) }}">{{ __("Reset") }}</a>
        </form>
    </section>

    <section id="world-cup-schedule">
        <div class="wc-section__heading">
            <div>
                <span class="wc-badge">{{ trans_choice('common.matches_count', $matches->count(), ['count' => $matches->count()]) }}</span>
                <h2 class="wc-title">{{ __(['today' => 'Today', 'upcoming' => 'Upcoming', 'all' => 'All Matches', 'groups' => 'Groups'][$tab] ?? 'Matches') }}</h2>
            </div>
        </div>

        @if($matchesByDate->isNotEmpty())
            <nav class="wc-date-nav" aria-label="{{ app()->isLocale('ar') ? 'التنقل حسب التاريخ' : 'Schedule dates' }}">
                @foreach($matchesByDate as $date => $dateMatches)
                    <a href="#schedule-{{ $date }}">{{ $date === 'tbc' ? __('TBC') : \Illuminate\Support\Carbon::parse($date)->translatedFormat('D, M j') }}</a>
                @endforeach
            </nav>
        @endif

        @forelse($matchesByDate as $date => $dateMatches)
            <section class="wc-schedule-date" id="schedule-{{ $date }}">
                <div class="wc-schedule-date__heading">
                    <h3>{{ $date === 'tbc' ? __('Date to be confirmed') : \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, F j, Y') }}</h3>
                    <span class="wc-badge">{{ app()->isLocale('ar') ? 'بتوقيت المغرب' : 'Morocco time' }}</span>
                </div>

                @foreach($dateMatches->groupBy('group_name') as $groupName => $groupMatches)
                    <div class="wc-schedule-group">
                        <div class="wc-schedule-group__heading">
                            <h4>{{ $groupName }}</h4>
                            <span>{{ trans_choice('common.matches_count', $groupMatches->count(), ['count' => $groupMatches->count()]) }}</span>
                        </div>
                        <div class="wc-schedule-grid">
                            @foreach($groupMatches as $match)
                                <article class="wc-schedule-card {{ $match->is_featured ? 'is-featured' : '' }}">
                                    <header>
                                        <span>{{ $match->group_name }}</span>
                                        <span class="wc-match-status wc-match-status--{{ $match->broadcast_status }}">{{ str($match->broadcast_status)->headline() }}</span>
                                    </header>
                                    <div class="wc-schedule-card__time">
                                        <strong>{{ $match->morocco_kickoff_at?->format('H:i') ?? '--:--' }}</strong>
                                        <span>{{ $match->morocco_kickoff_at?->format('D, M d') }} · {{ app()->isLocale('ar') ? 'المغرب' : 'Morocco' }}</span>
                                    </div>
                                    <div class="wc-schedule-card__teams">
                                        <strong><x-team-flag :team="$match->home_team" :src="$match->home_flag" size="lg" /><span>{{ $match->home_team }}</span></strong>
                                        <span>VS</span>
                                        <strong><x-team-flag :team="$match->away_team" :src="$match->away_flag" size="lg" /><span>{{ $match->away_team }}</span></strong>
                                    </div>
                                    <p class="wc-schedule-card__venue">{{ $match->venue }} · {{ $match->city }}</p>
                                    <div class="wc-schedule-card__details">
                                        <p><b>{{ __("Channel") }}</b><span>{{ $match->public_channel_name }}</span></p>
                                        <p><b>{{ __("Commentator") }}</b><span>{{ $match->commentator ?: __('Commentator to be confirmed') }}</span></p>
                                    </div>
                                    @if($match->public_watch_links->isNotEmpty())
                                        <div class="wc-schedule-card__watch-options">
                                            @foreach($match->public_watch_links as $watchLink)
                                                <a class="wc-button wc-button--primary wc-schedule-card__watch" href="{{ $watchLink['url'] }}" @if($watchLink['external']) target="_blank" rel="nofollow noopener noreferrer" @endif>
                                                    {{ app()->isLocale('ar') ? 'شاهد' : 'Watch' }} {{ $watchLink['name'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="wc-schedule-card__pending" @if(($match->iptvItems->isNotEmpty() || $match->selectedIptvItem) && $match->watch_available_at) data-watch-unlock-at="{{ $match->watch_available_at->toIso8601String() }}" @endif>
                                            {{ app()->isLocale('ar') ? 'سيتم إضافة الرابط قبل انطلاق المباراة' : 'Link will be added before kickoff' }}
                                        </span>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @unless($loop->last)
                    <x-ad-slot :name="'world_cup_date_'.$loop->iteration" type="inline" compact />
                @endunless
            </section>
        @empty
            <div class="wc-empty-state">
                <x-promo-banner compact />
                <h3>{{ __("No matches match these filters.") }}</h3>
                <p>{{ __("Try another date, group, team, or channel.") }}</p>
                <x-ad-slot name="world_cup_empty" type="empty" compact />
            </div>
        @endforelse
    </section>

    <section class="wc-card wc-schedule-seo-copy">
        <h2>{{ app()->isLocale('ar') ? 'جدول مباريات بطولة العالم 2026 بتوقيت المغرب' : '2026 world football schedule in Morocco time' }}</h2>
        <p>{{ app()->isLocale('ar')
            ? 'تابع مباريات اليوم، مواعيد مباريات بطولة العالم 2026، القنوات الناقلة، المعلقين وتوقيت المغرب. يعمل RiFiTV على الهاتف والتلفاز والحاسوب والتابليت، وتظهر روابط المشاهدة فقط عند تفعيلها من الإدارة.'
            : 'Follow today’s matches, the 2026 world football schedule, channel listings, commentators, and Morocco kickoff times. RiFiTV works across phones, TVs, computers, and tablets, while watch links appear only when enabled by an administrator.' }}</p>
        <p>
            <a href="{{ route('sports.football') }}">{{ app()->isLocale('ar') ? 'مباريات اليوم' : 'Today’s matches' }}</a>
            · <a href="{{ route('live-tv') }}">{{ app()->isLocale('ar') ? 'قنوات كرة القدم' : 'Football channels' }}</a>
            · <a href="{{ route('contact') }}">{{ app()->isLocale('ar') ? 'تواصل معنا' : 'Contact RiFiTV' }}</a>
        </p>
    </section>
</div>
@endsection
