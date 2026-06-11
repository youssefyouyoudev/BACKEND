<section class="rtv-landing-section" aria-labelledby="rtv-features-title">
    <div class="rtv-section-heading rtv-section-heading--center">
        <div>
            <span class="rtv-kicker">{{ __('landing.features.eyebrow') }}</span>
            <h2 id="rtv-features-title">{{ __('landing.features.title') }}</h2>
            <p>{{ __('landing.features.subtitle') }}</p>
        </div>
    </div>
    <div class="rtv-feature-grid">
        @foreach(__('landing.features.items') as $feature)
            <article class="rtv-feature-card" data-reveal>
                <span><x-icon :name="$feature['icon']" /></span>
                <h3>{{ $feature['title'] }}</h3>
                <p>{{ $feature['copy'] }}</p>
            </article>
        @endforeach
    </div>
</section>

<section class="rtv-landing-section" aria-labelledby="rtv-how-title">
    <div class="rtv-section-heading">
        <div>
            <span class="rtv-kicker">{{ __('landing.how.eyebrow') }}</span>
            <h2 id="rtv-how-title">{{ __('landing.how.title') }}</h2>
        </div>
    </div>
    <div class="rtv-how-grid">
        @foreach(__('landing.how.steps') as $step)
            <article data-reveal>
                <b>{{ $step['number'] }}</b>
                <span><x-icon :name="$loop->iteration === 1 ? 'calendar' : ($loop->iteration === 2 ? 'tv' : 'play')" /></span>
                <h3>{{ $step['title'] }}</h3>
                <p>{{ $step['copy'] }}</p>
            </article>
        @endforeach
    </div>
</section>

<section class="rtv-trust-section" data-reveal aria-labelledby="rtv-trust-title">
    <div>
        <span class="rtv-kicker">{{ __('landing.trust.eyebrow') }}</span>
        <h2 id="rtv-trust-title">{{ __('landing.trust.title') }}</h2>
    </div>
    <div class="rtv-trust-grid">
        @foreach(__('landing.trust.items') as $item)
            <article><x-icon name="check" /><span><strong>{{ $item['title'] }}</strong><small>{{ $item['copy'] }}</small></span></article>
        @endforeach
    </div>
</section>
