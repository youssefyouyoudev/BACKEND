@props([
    'matchesByStage',
    'compact' => false,
])

@php
    $stageLabels = [
        'round_of_32' => 'Round of 32',
        'round_of_16' => 'Round of 16',
        'quarter_final' => 'Quarter-finals',
        'semi_final' => 'Semi-finals',
        'third_place' => 'Third-place',
        'final' => 'Final',
    ];
@endphp

<section {{ $attributes->merge(['class' => $compact ? 'wc-road wc-road--compact' : 'wc-road']) }}>
    <div class="wc-road__rail" aria-label="{{ __('World Cup 2026 knockout stages') }}">
        @foreach($stageLabels as $stage => $label)
            @php($stageMatches = collect($matchesByStage->get($stage, collect())))
            @continue($stageMatches->isEmpty() && $compact)
            <section class="wc-road__stage" id="stage-{{ $stage }}">
                <header>
                    <span>{{ __($label) }}</span>
                    <b>{{ trans_choice('common.matches_count', $stageMatches->count(), ['count' => $stageMatches->count()]) }}</b>
                </header>
                <div class="wc-road__matches">
                    @forelse($stageMatches as $match)
                        <a class="wc-road__match" href="{{ route('matches.watch', $match) }}">
                            <span>{{ __('Match') }} {{ $match->match_number }}</span>
                            <strong>{{ $match->home_display_name }}</strong>
                            <small>{{ __('vs') }} {{ $match->away_display_name }}</small>
                            <em>{{ $match->kickoff_at_morocco?->format('M d, H:i') }} {{ __('Morocco Time') }}</em>
                        </a>
                    @empty
                        <div class="wc-road__match wc-road__match--empty">
                            <span>{{ __('Pending') }}</span>
                            <strong>{{ __('Matches to be confirmed') }}</strong>
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</section>
