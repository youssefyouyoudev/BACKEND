<section class="rtv-world-cup-promo" data-reveal aria-labelledby="rtv-world-cup-title">
    <div class="rtv-world-cup-promo__visual" aria-hidden="true">
        <div class="rtv-trophy">
            <span class="rtv-trophy__cup"><x-icon name="trophy" /></span>
            <span class="rtv-trophy__ring rtv-trophy__ring--one"></span>
            <span class="rtv-trophy__ring rtv-trophy__ring--two"></span>
            <b>{{ __('landing.world_cup.trophy_label') }}</b>
        </div>
    </div>
    <div class="rtv-world-cup-promo__copy">
        <span class="rtv-kicker">{{ __('landing.world_cup.eyebrow') }}</span>
        <h2 id="rtv-world-cup-title">{{ __('landing.world_cup.title') }}</h2>
        <p>{{ __('landing.world_cup.subtitle') }}</p>
        <div class="rtv-world-cup-points">
            @foreach(['schedule', 'channels', 'commentators', 'time', 'links'] as $point)
                <span><x-icon name="check" />{{ __('landing.world_cup.'.$point) }}</span>
            @endforeach
        </div>
        @if($worldCupMatchesCount > 0)
            <strong class="rtv-world-cup-count">{{ __('landing.world_cup.matches_count', ['count' => $worldCupMatchesCount]) }}</strong>
        @endif
        <a class="rtv-button rtv-button--primary" href="{{ route('world-cup.index') }}">
            <x-icon name="trophy" />{{ __('landing.world_cup.cta') }}
        </a>
    </div>
</section>
