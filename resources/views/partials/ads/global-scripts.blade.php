<!-- RiFiTV global ad scripts: centralized to avoid duplication. Do not paste these scripts in individual pages. -->
@once
    @if(config('ads.enabled') && !request()->is('admin*'))
        @php
            $adRuntime = <<<'HTML'
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="referrer" content="strict-origin-when-cross-origin"></head>
<body>
<script>
    (function (s) {
        s.dataset.zone = '11137954';
        s.src = 'https://n6wxm.com/vignette.min.js';
        s.async = true;
    })(document.body.appendChild(document.createElement('script')));
</script>
<script>
    (function (s) {
        s.dataset.zone = '11137952';
        s.src = 'https://nap5k.com/tag.min.js';
        s.async = true;
    })(document.body.appendChild(document.createElement('script')));
</script>
<script>
    (function (s) {
        s.dataset.zone = '11137947';
        s.src = 'https://al5sm.com/tag.min.js';
        s.async = true;
    })(document.body.appendChild(document.createElement('script')));
</script>
<script src="https://5gvci.com/act/files/tag.min.js?z=11137945" data-cfasync="false" async></script>
<script src="https://quge5.com/88/tag.min.js" data-zone="248721" async data-cfasync="false"></script>
</body>
</html>
HTML;
        @endphp
        <iframe
            class="rifitv-ad-runtime"
            title="{{ __('Sponsored content runtime') }}"
            aria-hidden="true"
            tabindex="-1"
            sandbox="allow-scripts"
            srcdoc="{{ $adRuntime }}"
        ></iframe>
    @endif
@endonce
