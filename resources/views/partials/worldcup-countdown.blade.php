@php
    $compact = $compact ?? false;
    $target = $target ?? '2026-06-11T20:00:00+01:00';
@endphp

<div
    class="wc-countdown {{ $compact ? 'wc-countdown--compact' : '' }}"
    data-countdown
    data-countdown-target="{{ $target }}"
    aria-label="Countdown to the World Cup 2026 broadcast"
>
    <div class="wc-countdown__status" data-countdown-status>
        Broadcast starts on 11/06 at 8:00 PM GMT+1
    </div>
    <div class="wc-countdown__grid">
        <span><b data-countdown-days>00</b><small>Days <em>أيام</em></small></span>
        <span><b data-countdown-hours>00</b><small>Hours <em>ساعات</em></small></span>
        <span><b data-countdown-minutes>00</b><small>Minutes <em>دقائق</em></small></span>
        <span><b data-countdown-seconds>00</b><small>Seconds <em>ثواني</em></small></span>
    </div>
</div>
