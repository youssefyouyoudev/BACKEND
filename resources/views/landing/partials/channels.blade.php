<section class="rtv-landing-section" aria-labelledby="rtv-channels-title">
    <div class="rtv-section-heading">
        <div>
            <span class="rtv-kicker">{{ __('TV guide') }}</span>
            <h2 id="rtv-channels-title">{{ __('Featured broadcaster information') }}</h2>
            <p>{{ __('Browse channel names, categories, and quality information available in the RiFiTV match guide.') }}</p>
        </div>
        <a class="rtv-text-link" href="{{ route('tv-guide.index') }}">{{ __('Open TV guide') }} <x-icon name="arrow-up-right" /></a>
    </div>

    @if($featuredChannels->isNotEmpty())
        <div class="rtv-channel-grid">
            @foreach($featuredChannels as $channel)
                <a class="rtv-channel-card" href="{{ route('channels.show', $channel->slug ?: $channel->id) }}" data-reveal>
                    <span class="rtv-channel-card__logo">
                        <img src="{{ $channel->logo ?: asset('brand/rifi-logo.png') }}" alt="{{ $channel->clean_display_name }}" loading="lazy" decoding="async" data-fallback-src="/brand/rifi-logo.png">
                        <b>{{ $channel->quality_label }}</b>
                    </span>
                    <span class="rtv-channel-card__body">
                        <small>{{ $channel->category?->name ?? $channel->group_title ?: __('landing.channels.category') }}</small>
                        <strong>{{ $channel->clean_display_name }}</strong>
                        <em>{{ __('Channel information') }} <x-icon name="arrow-up-right" /></em>
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <div class="rtv-channel-skeletons" aria-label="{{ __('landing.channels.empty_title') }}">
            @for($i = 0; $i < 4; $i++)
                <span><i></i><b></b><small></small></span>
            @endfor
        </div>
        <div class="rtv-landing-empty rtv-landing-empty--compact">
            <h3>{{ __('landing.channels.empty_title') }}</h3>
            <p>{{ __('landing.channels.empty_copy') }}</p>
        </div>
    @endif
</section>
