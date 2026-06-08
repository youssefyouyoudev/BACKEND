@php($compact = $compact ?? false)

<article
    class="rm-live-fallback {{ $compact ? 'rm-live-fallback--compact' : '' }}"
>
    <div class="rm-live-fallback__poster">
        <img
            src="{{ asset('assets/images/fifa_world_cup_2026_tease.png') }}"
            alt="FIFA World Cup 2026 coming soon on Rifi Media TV"
        >
    </div>
    <div class="rm-live-fallback__content">
        <span class="rm-live-fallback__eyebrow wc-badge">Rifi Media TV · World Cup 2026</span>
        <h2>Coming Soon <small dir="rtl">قريباً</small></h2>
        <p>No live TV channels are currently available.</p>
        <strong>Broadcast starts on 11/06 at 8:00 PM GMT+1</strong>
        <p dir="rtl">الانطلاق يوم 11/06 الساعة 8 مساءً GMT+1</p>
        @include('partials.worldcup-countdown', ['compact' => $compact])

        <div class="rm-live-fallback__actions">
            @if ($compact)
                <button type="button" @click="search = ''; activeCategory = 'All Channels'; $refs.search?.focus()">Back to Channels / العودة للقنوات</button>
            @else
                <button type="button" @click="refreshCatalog">Refresh / تحديث</button>
                <button type="button" @click="$refs.search?.scrollIntoView({ behavior: 'smooth', block: 'center' }); $refs.search?.focus()">Back to Channels / العودة للقنوات</button>
            @endif
        </div>
    </div>
</article>
