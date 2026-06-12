@props([
    'phone' => '0663323824',
    'whatsappPhone' => '212663323824',
    'message' => null,
    'interval' => 30 * 60 * 1000,
    'duration' => 20 * 1000,
])

<a
    {{ $attributes->class(['premium-video-ticker']) }}
    href="https://wa.me/{{ $whatsappPhone }}"
    target="_blank"
    rel="noopener noreferrer"
    dir="rtl"
    data-premium-video-ticker
    data-interval-ms="{{ $interval }}"
    data-duration-ms="{{ $duration }}"
    aria-hidden="true"
    tabindex="-1"
>
    <span class="premium-video-ticker__icon" aria-hidden="true">
        <x-icon name="message" />
    </span>
    <span class="premium-video-ticker__viewport">
        <span class="premium-video-ticker__track">
            @if($message)
                {{ $message }}
            @else
                باش تفرّج بجودة Premium و بث سلس، اشترك مع
                <strong>RiFiMedia</strong>
                دابا أو تواصل معنا فالواتساب:
                <b dir="ltr">{{ $phone }}</b>
            @endif
        </span>
    </span>
</a>
