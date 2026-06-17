@once
    @if(config('ads.enabled') && !request()->is('admin*'))
        @php
            $scriptSettings = collect(['multitag', 'in_page_push', 'vignette'])
                ->map(fn (string $placement) => \App\Models\AdSetting::enabledForPlacement($placement))
                ->filter(fn ($setting) => filled($setting?->script_code));
        @endphp
        @foreach($scriptSettings as $setting)
            <div
                class="rifitv-ad-runtime"
                data-ad-script="{{ $setting->placement_key }}"
                data-frequency-seconds="{{ $setting->frequency_seconds }}"
                data-max-per-session="{{ $setting->max_per_session }}"
                hidden
            >{!! $setting->script_code !!}</div>
        @endforeach
        <script>
            document.querySelectorAll('[data-ad-script]').forEach((node) => {
                const key = `rifitv_ad_${node.dataset.adScript}`;
                const frequency = Number(node.dataset.frequencySeconds || 0) * 1000;
                const max = Number(node.dataset.maxPerSession || 1);
                const now = Date.now();
                const lastShown = Number(localStorage.getItem(`${key}_last`) || 0);
                const count = Number(sessionStorage.getItem(`${key}_count`) || 0);

                if (count >= max || (frequency > 0 && now - lastShown < frequency)) {
                    node.remove();
                    return;
                }

                localStorage.setItem(`${key}_last`, String(now));
                sessionStorage.setItem(`${key}_count`, String(count + 1));
                node.hidden = false;
            });
        </script>
    @endif
@endonce
