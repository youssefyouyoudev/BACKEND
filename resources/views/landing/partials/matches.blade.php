<section class="rtv-landing-section" id="today-matches" aria-labelledby="rtv-matches-title">
    <div class="rtv-section-heading">
        <div>
            <span class="rtv-kicker">{{ __('landing.matches.eyebrow') }}</span>
            <h2 id="rtv-matches-title">{{ $showingUpcomingFallback ? __('Next upcoming matches') : __("Today's matches") }}</h2>
            <p>{{ __('Kickoff times, competitions, teams, and TV information in Morocco time.') }}</p>
        </div>
        <a class="rtv-text-link" href="{{ route('football.today') }}">
            {{ __('All matches') }} <x-icon name="arrow-up-right" />
        </a>
    </div>

    @if($previewMatches->isNotEmpty())
        <div class="rtv-match-grid">
            @foreach($previewMatches as $match)
                @php
                    $statusLabel = $match->public_status_label;
                    $statusClass = match ($match->public_status) {
                        'live', 'halftime' => 'live',
                        'finished', 'cancelled', 'postponed' => 'ended',
                        default => 'upcoming',
                    };
                @endphp
                <article class="rtv-match-card match-card" data-reveal>
                    <header>
                        <span>{{ $match->competition }}{{ $match->group_name ? ' - '.$match->group_name : '' }}</span>
                        <b class="match-window-badge match-window-badge--{{ $statusClass }}">{{ $statusLabel }}</b>
                    </header>
                    <div class="rtv-match-card__time">
                        <strong>{{ $match->kickoff_at_morocco?->format('H:i') ?: '--:--' }}</strong>
                        <span>{{ $match->kickoff_at_morocco?->translatedFormat('M d') }} - {{ __('Morocco time') }}</span>
                    </div>
                    <div class="rtv-match-card__teams">
                        <strong><x-team-flag :team="$match->home_team" :src="$match->home_flag" size="lg" /><span>{{ $match->home_team }}</span></strong>
                        <span>VS</span>
                        <strong><x-team-flag :team="$match->away_team" :src="$match->away_flag" size="lg" /><span>{{ $match->away_team }}</span></strong>
                    </div>
                    @if($match->venue)
                        <p class="rtv-match-card__venue">{{ collect([$match->venue, $match->city])->filter()->implode(', ') }}</p>
                    @endif
                    <dl>
                        <div><dt>{{ __('TV info') }}</dt><dd>{{ $match->public_channel_name }}</dd></div>
                        <div><dt>{{ __('Commentator') }}</dt><dd>{{ $match->commentator ?: __('Not confirmed yet') }}</dd></div>
                    </dl>
                    <a class="rtv-button rtv-button--primary rtv-match-card__action" href="{{ route('matches.watch', $match) }}">
                        <x-icon name="chevron-right" /> {{ __('Match details') }}
                    </a>
                </article>
                @if($loop->iteration % 4 === 0 && ! $loop->last)
                    <x-ad-slot :name="'home_matches_'.$loop->iteration" type="inline" compact />
                @endif
            @endforeach
        </div>
    @else
        <div class="rtv-landing-empty" data-reveal>
            <span><x-icon name="calendar" /></span>
            <h3>{{ __('No matches found for this date.') }}</h3>
            <p>{{ __('Try another date or open the upcoming football schedule.') }}</p>
            <a class="rtv-button rtv-button--secondary" href="{{ route('football.schedules') }}">{{ __('View schedules') }}</a>
        </div>
    @endif
</section>
