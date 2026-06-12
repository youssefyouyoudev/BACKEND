@php($compact = $compact ?? false)

<article
    class="rm-live-fallback {{ $compact ? 'rm-live-fallback--compact' : '' }}"
>
    <div class="rm-live-fallback__poster">
        <x-promo-banner :compact="$compact" />
    </div>
    <div class="rm-live-fallback__content">
        <span class="rm-live-fallback__eyebrow wc-badge">{{ __("Rifi Media TV · World Cup 2026") }}</span>
        <h2>{{ __("Coming Soon") }} <small dir="rtl">قريباً</small></h2>
        <p>{{ __("No live TV channels are currently available.") }}</p>
        <strong>{{ __("Broadcast starts on 11/06 at 8:00 PM GMT+1") }}</strong>
        <p dir="rtl">{{ __("الانطلاق يوم 11/06 الساعة 8 مساءً GMT+1") }}</p>
        @include('partials.worldcup-countdown', ['compact' => $compact])

        <div class="rm-live-fallback__actions">
            @if ($compact)
                <button type="button" @click="search = ''; activeCategory = 'All Channels'; $refs.search?.focus()">{{ __("Back to Channels / العودة للقنوات") }}</button>
            @else
                <button type="button" @click="refreshCatalog">{{ __("Refresh / تحديث") }}</button>
                <button type="button" @click="$refs.search?.scrollIntoView({ behavior: 'smooth', block: 'center' }); $refs.search?.focus()">{{ __("Back to Channels / العودة للقنوات") }}</button>
            @endif
        </div>
        <x-ad-slot name="channels_empty_state" type="empty" compact />
    </div>
</article>
