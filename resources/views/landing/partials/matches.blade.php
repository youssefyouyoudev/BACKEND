<section class="rtv-landing-section" id="today-matches" aria-labelledby="rtv-matches-title">
    <div class="rtv-section-heading">
        <div>
            <span class="rtv-kicker">{{ __('landing.matches.eyebrow') }}</span>
            <h2 id="rtv-matches-title">{{ __('landing.matches.title') }}</h2>
            <p>{{ __('landing.matches.subtitle') }}</p>
        </div>
        <a class="rtv-text-link" href="{{ route('world-cup.index', ['tab' => 'upcoming']) }}">
            {{ __('landing.matches.view_all') }} <x-icon name="arrow-up-right" />
        </a>
    </div>

    @if($previewMatches->isNotEmpty())
        <div class="rtv-match-grid">
            @foreach($previewMatches as $match)
                <article class="rtv-match-card" data-reveal>
                    <header>
                        <span>{{ $match->group_name }}</span>
                        <b class="rtv-status rtv-status--{{ $match->broadcast_status }}">{{ __('landing.status.'.$match->broadcast_status) }}</b>
                    </header>
                    <div class="rtv-match-card__time">
                        <strong>{{ $match->morocco_kickoff_at?->format('H:i') ?: '--:--' }}</strong>
                        <span>{{ $match->morocco_kickoff_at?->format('M d') }} · {{ __('landing.matches.morocco') }}</span>
                    </div>
                    <div class="rtv-match-card__teams">
                        <strong>
                            <x-team-flag :team="$match->home_team" :src="$match->home_flag" size="lg" />
                            <span>{{ $match->home_team }}</span>
                        </strong>
                        <span>VS</span>
                        <strong>
                            <x-team-flag :team="$match->away_team" :src="$match->away_flag" size="lg" />
                            <span>{{ $match->away_team }}</span>
                        </strong>
                    </div>
                    <dl>
                        <div><dt>{{ __('landing.matches.channel') }}</dt><dd>{{ $match->public_channel_name ?: __('landing.matches.channel_tbc') }}</dd></div>
                        <div><dt>{{ __('landing.matches.commentator') }}</dt><dd>{{ $match->commentator ?: __('landing.matches.commentator_tbc') }}</dd></div>
                    </dl>
                    @if($match->public_watch_links->isNotEmpty())
                        <div class="rtv-match-card__watch-options">
                            @foreach($match->public_watch_links as $watchLink)
                                <a
                                    class="rtv-button rtv-button--primary rtv-match-card__action"
                                    href="{{ $watchLink['url'] }}"
                                    @if($watchLink['external']) target="_blank" rel="nofollow noopener noreferrer" @endif
                                ><x-icon name="play" />{{ $watchLink['name'] }}</a>
                            @endforeach
                        </div>
                    @else
                        <span
                            class="rtv-match-card__pending"
                            @if(($match->iptvItems->isNotEmpty() || $match->selectedIptvItem) && $match->watch_available_at)
                                data-watch-unlock-at="{{ $match->watch_available_at->toIso8601String() }}"
                            @endif
                        >
                            @if(($match->iptvItems->isNotEmpty() || $match->selectedIptvItem) && $match->watch_available_at)
                                {{ __('landing.matches.available_at', [
                                    'time' => $match->watch_available_at->timezone('Africa/Casablanca')->format('M d, H:i'),
                                ]) }}
                            @else
                                {{ __('landing.matches.pending') }}
                            @endif
                        </span>
                    @endif
                </article>
            @endforeach
        </div>
    @else
        <div class="rtv-landing-empty" data-reveal>
            <span><x-icon name="calendar" /></span>
            <h3>{{ __('landing.matches.empty_title') }}</h3>
            <p>{{ __('landing.matches.empty_copy') }}</p>
            <a class="rtv-button rtv-button--secondary" href="{{ route('world-cup.index', ['tab' => 'upcoming']) }}">{{ __('landing.matches.upcoming_cta') }}</a>
        </div>
    @endif
</section>
