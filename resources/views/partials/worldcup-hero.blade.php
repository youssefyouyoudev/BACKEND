@php($liveChannelCount = $liveChannelCount ?? 0)

<section class="wc-hero" aria-labelledby="wc-home-title">
    <div class="wc-hero__lights" aria-hidden="true"></div>
    <div class="wc-hero__copy" data-reveal>
        <span class="wc-badge wc-live-badge"><i></i> World Cup 2026 <b>كأس العالم</b></span>
        <h1 id="wc-home-title">
            World Cup 2026
            <span>Live Experience</span>
        </h1>
        <p class="wc-hero__arabic" dir="rtl">تجربة كأس العالم 2026 مباشرة</p>
        <p class="wc-subtitle">
            Watch channels, follow match moments, and enjoy a smooth football broadcast experience on RiFi Media TV.
        </p>
        <p class="wc-subtitle wc-subtitle--arabic" dir="rtl">
            تابع القنوات وأجواء المباريات وتجربة بث كروية سلسة على RiFi Media TV.
        </p>

        <div class="wc-hero__actions">
            <a href="{{ route('live-tv') }}" class="wc-button wc-button--primary">
                <x-icon name="play" /> Watch Live <span>شاهد الآن</span>
            </a>
            <a href="{{ route('live-tv') }}#channels" class="wc-button wc-button--ghost">
                <x-icon name="tv" /> Explore Channels <span>استكشف القنوات</span>
            </a>
        </div>

        @include('partials.worldcup-countdown', ['compact' => true])

        <div class="wc-hero__stats" aria-label="RiFi Media TV highlights">
            <span><strong data-live-channel-count>{{ number_format($liveChannelCount) }}</strong> Live channels</span>
            <span><strong>24/7</strong> Smooth discovery</span>
            <span><strong>Auto</strong> Stream recovery</span>
        </div>
    </div>

    <div class="wc-hero__visual" data-reveal>
        <div class="wc-hero__poster wc-glass">
            <span class="wc-hero__poster-live"><i></i> Coming soon</span>
            <img
                src="{{ asset('assets/images/fifa_world_cup_2026_tease.png') }}"
                alt="RiFi Media TV World Cup 2026 live broadcast announcement"
                width="1122"
                height="1402"
                fetchpriority="high"
            >
        </div>
        <span class="wc-hero__orbit wc-hero__orbit--red" aria-hidden="true"></span>
        <span class="wc-hero__orbit wc-hero__orbit--green" aria-hidden="true"></span>
    </div>
</section>
