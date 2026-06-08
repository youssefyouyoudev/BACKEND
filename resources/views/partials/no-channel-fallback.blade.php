@php($compact = $compact ?? false)

<article
    class="rm-live-fallback {{ $compact ? 'rm-live-fallback--compact' : '' }}"
    data-countdown
    data-countdown-target="2026-06-11T20:00:00+01:00"
>
    <div class="rm-live-fallback__poster">
        <img
            src="{{ asset('assets/images/fifa_world_cup_2026_tease.png') }}"
            alt="FIFA World Cup 2026 coming soon on Rifi Media TV"
        >
    </div>
    <div class="rm-live-fallback__content">
        <span class="rm-live-fallback__eyebrow">Rifi Media TV</span>
        <h2>Coming Soon</h2>
        <p>No live TV channels are currently available.</p>
        <strong>Broadcast starts on 11/06 at 8:00 PM GMT+1</strong>
        <h3 dir="rtl">قريباً</h3>
        <p dir="rtl">الانطلاق يوم 11/06 الساعة 8 مساءً GMT+1</p>
        <span class="rm-live-fallback__status" data-countdown-status>Counting down to live coverage</span>

        <div class="rm-live-countdown" aria-label="Countdown to live coverage">
            <span><b data-countdown-days>00</b><small>Days / أيام</small></span>
            <span><b data-countdown-hours>00</b><small>Hours / ساعات</small></span>
            <span><b data-countdown-minutes>00</b><small>Minutes / دقائق</small></span>
            <span><b data-countdown-seconds>00</b><small>Seconds / ثواني</small></span>
        </div>

        <div class="rm-live-fallback__actions">
            @if ($compact)
                <button type="button" @click="search = ''; activeCategory = 'All Channels'; $refs.search?.focus()">Clear search</button>
            @else
                <button type="button" x-show="activeVariant" @click="refreshStream">Try again</button>
                <button type="button" @click="$refs.search?.scrollIntoView({ behavior: 'smooth', block: 'center' }); $refs.search?.focus()">Back to channels</button>
            @endif
        </div>
    </div>
</article>
