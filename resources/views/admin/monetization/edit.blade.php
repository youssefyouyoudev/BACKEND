@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __('Revenue') }}</p>
        <h1>{{ __('Monetag ad manager') }}</h1>
        <p class="page-header__copy">{{ __('Configure legal, respectful ad placements without hardcoding zone IDs in the codebase.') }}</p>
    </div>
    <a class="button button--ghost" href="{{ route('home') }}" target="_blank" rel="noopener">{{ __('Preview homepage') }}</a>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">{{ __('Publisher rules') }}</p>
            <h2>{{ __('Keep ads compliant') }}</h2>
        </div>
        <span class="surface-card__badge">{{ __('Monetag') }}</span>
    </div>
    <div class="legal-callout">
        <strong>{{ __('Content policy') }}</strong>
        <p>{{ __('Use RiFiTV as a sports schedule, scores, TV guide, news, and match details platform. Do not advertise illegal streams or misleading free-streaming claims.') }}</p>
    </div>
</section>

<form method="POST" action="{{ route('admin.monetization.update') }}" class="surface-card monetization-form">
    @csrf
    @method('PUT')

    <div class="monetization-grid">
        @foreach($settings as $key => $setting)
            <article class="monetization-card">
                <input type="hidden" name="settings[{{ $key }}][placement_key]" value="{{ $setting->placement_key }}">
                <div class="monetization-card__header">
                    <div>
                        <span>{{ $placements[$setting->placement_key] }}</span>
                        <strong>{{ str($setting->placement_key)->replace('_', ' ')->headline() }}</strong>
                    </div>
                    <label class="toggle-line">
                        <input type="checkbox" name="settings[{{ $key }}][enabled]" value="1" @checked(old("settings.$key.enabled", $setting->enabled))>
                        {{ __('Enabled') }}
                    </label>
                </div>

                <div class="field">
                    <label for="device-{{ $key }}">{{ __('Device') }}</label>
                    <select id="device-{{ $key }}" name="settings[{{ $key }}][device]">
                        @foreach(['all' => __('All'), 'mobile' => __('Mobile'), 'desktop' => __('Desktop')] as $value => $label)
                            <option value="{{ $value }}" @selected(old("settings.$key.device", $setting->device ?: 'all') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="script-{{ $key }}">
                        @if($setting->placement_key === 'rifimedia_popup')
                            {{ __('Popup copy JSON') }}
                        @elseif(str_starts_with($setting->placement_key, 'zone_') || $setting->placement_key === 'vignette_11137954')
                            {{ __('Zone reference') }}
                        @else
                            {{ __('Placement note') }}
                        @endif
                    </label>
                    <textarea id="script-{{ $key }}" name="settings[{{ $key }}][script_code]" rows="5" placeholder='{"title":"RifiMedia Premium","message":"..."}'>{{ old("settings.$key.script_code", $setting->script_code) }}</textarea>
                    <small class="field__hint">
                        @if(str_starts_with($setting->placement_key, 'zone_') || $setting->placement_key === 'vignette_11137954')
                            {{ __('The app loads this Monetag zone from the centralized frontend loader. Keep this as a note; do not paste duplicate scripts into layouts.') }}
                        @elseif($setting->placement_key === 'rifimedia_popup')
                            {{ __('Use JSON with title and message keys. The CTA URL is controlled by the URL field below.') }}
                        @else
                            {{ __('Optional internal note for this placement. Raw scripts are not injected from here.') }}
                        @endif
                    </small>
                </div>

                <div class="field">
                    <label for="link-{{ $key }}">{{ __('SmartLink / direct URL') }}</label>
                    <input id="link-{{ $key }}" type="url" name="settings[{{ $key }}][direct_link_url]" value="{{ old("settings.$key.direct_link_url", $setting->direct_link_url) }}" placeholder="https://example.com/offer">
                </div>

                <div class="monetization-card__limits">
                    <div class="field">
                        <label for="frequency-{{ $key }}">{{ __('Frequency seconds') }}</label>
                        <input id="frequency-{{ $key }}" type="number" min="0" max="86400" name="settings[{{ $key }}][frequency_seconds]" value="{{ old("settings.$key.frequency_seconds", $setting->frequency_seconds ?: 300) }}">
                    </div>
                    <div class="field">
                        <label for="session-{{ $key }}">{{ __('Max per session') }}</label>
                        <input id="session-{{ $key }}" type="number" min="0" max="20" name="settings[{{ $key }}][max_per_session]" value="{{ old("settings.$key.max_per_session", $setting->max_per_session ?: 1) }}">
                    </div>
                </div>

                <label class="toggle-line">
                    <input type="checkbox" name="settings[{{ $key }}][test_mode]" value="1" @checked(old("settings.$key.test_mode", $setting->test_mode))>
                    {{ __('Test mode') }}
                </label>
            </article>
        @endforeach
    </div>

    <div class="form-actions">
        <button class="button button--primary" type="submit">{{ __('Save Monetag settings') }}</button>
    </div>
</form>
@endsection
