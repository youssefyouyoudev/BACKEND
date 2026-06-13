<section class="rtv-landing-section" id="today-matches" aria-labelledby="rtv-matches-title">
    <div class="rtv-section-heading">
        <div>
            <span class="rtv-kicker">{{ __('landing.matches.eyebrow') }}</span>
            <h2 id="rtv-matches-title">{{ $showingUpcomingFallback ? __('Next Upcoming Matches') : __('Today’s Matches') }}</h2>
            <p>{{ __('landing.matches.subtitle') }}</p>
        </div>
        <a class="rtv-text-link" href="{{ route('world-cup.index', ['tab' => 'upcoming']) }}">
            {{ __('landing.matches.view_all') }} <x-icon name="arrow-up-right" />
        </a>
    </div>

    @if($previewMatches->isNotEmpty())
        <div class="rtv-match-grid">
            @foreach($previewMatches as $match)
                @php
                    $watchStatus = $match->watchStatus();
                    $opensSoon = $watchStatus === 'opens_soon'
                        && now('Africa/Casablanca')->diffInMinutes($match->watch_opens_at, false) <= 60;
                    $statusLabel = match ($watchStatus) {
                        'open' => app()->isLocale('ar') ? 'مباشر الآن' : 'Live Now',
                        'expired' => app()->isLocale('ar') ? 'انتهت المباراة' : 'Ended',
                        default => $opensSoon
                            ? (app()->isLocale('ar') ? 'تفتح قريباً' : 'Opens Soon')
                            : (app()->isLocale('ar') ? 'قادمة' : 'Upcoming'),
                    };
                    $statusClass = match ($watchStatus) {
                        'open' => 'live',
                        'expired' => 'ended',
                        default => $opensSoon ? 'soon' : 'upcoming',
                    };
                @endphp
                <article class="rtv-match-card match-card" data-reveal>
                    <header>
                        <span>{{ $match->competition }}{{ $match->group_name ? ' · '.$match->group_name : '' }}</span>
                        <b class="match-window-badge match-window-badge--{{ $statusClass }}">{{ $statusLabel }}</b>
                    </header>
                    <div class="rtv-match-card__time">
                        <strong>{{ $match->kickoff_at_morocco?->format('H:i') ?: '--:--' }}</strong>
                        <span>{{ $match->kickoff_at_morocco?->translatedFormat('M d') }} · {{ app()->isLocale('ar') ? 'بتوقيت المغرب' : 'Morocco Time' }}</span>
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
                        <div>
                            <dt>{{ __('landing.matches.channel') }}</dt>
                            <dd>
                                <span class="channel-confirmation channel-confirmation--{{ $match->broadcast_status === 'confirmed' ? 'confirmed' : 'pending' }}">
                                    {{ $match->broadcast_status === 'confirmed'
                                        ? (app()->isLocale('ar') ? 'مؤكدة' : 'Confirmed')
                                        : (app()->isLocale('ar') ? 'غير مؤكدة' : 'Not confirmed') }}
                                </span>
                                {{ $match->public_channel_name }}
                            </dd>
                        </div>
                        <div><dt>{{ __('landing.matches.commentator') }}</dt><dd>{{ $match->commentator ?: __('landing.matches.commentator_tbc') }}</dd></div>
                    </dl>
                    <a class="rtv-button rtv-button--primary rtv-match-card__action" href="{{ route('matches.watch', $match) }}">
                        <x-icon name="play" />
                        @if($watchStatus === 'expired')
                            {{ app()->isLocale('ar') ? 'انتهت المباراة' : 'Match Ended' }}
                        @elseif($watchStatus === 'opens_soon')
                            {{ app()->isLocale('ar') ? 'تفتح على الساعة' : 'Opens at' }} {{ $match->watch_opens_at?->format('H:i') }}
                        @else
                            {{ app()->isLocale('ar') ? 'شاهد المباراة' : 'Watch Match' }}
                        @endif
                    </a>
                </article>
                @if($loop->iteration % 4 === 0 && ! $loop->last)
                    <x-ad-slot :name="'home_matches_'.$loop->iteration" type="inline" compact />
                @endif
            @endforeach
        </div>
    @else
        <div class="rtv-landing-empty" data-reveal>
            <x-promo-banner compact />
            <span><x-icon name="calendar" /></span>
            <h3>{{ __('landing.matches.empty_title') }}</h3>
            <p>{{ __('landing.matches.empty_copy') }}</p>
            <a class="rtv-button rtv-button--secondary" href="{{ route('world-cup.index', ['tab' => 'upcoming']) }}">{{ __('landing.matches.upcoming_cta') }}</a>
        </div>
    @endif

    <p class="match-window-note">
        {{ app()->isLocale('ar')
            ? 'تفتح صفحة المشاهدة قبل ساعة من البداية وتغلق بعد ساعة من نهاية المباراة.'
            : 'Watch page opens 1 hour before kickoff and closes 1 hour after the match ends.' }}
    </p>
</section>
