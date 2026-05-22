@props(['title', 'description', 'features' => []])

<section {{ $attributes->merge(['class' => 'rm-coming-soon']) }}>
    <span class="rm-kicker">Coming soon</span>
    <h1>{{ $title }}</h1>
    <p>{{ $description }}</p>
    <div class="rm-coming-soon__features">
        @foreach($features as $feature)
            <span>{{ $feature }}</span>
        @endforeach
    </div>
    <div class="rm-hero-actions">
        <a href="{{ route('live-tv') }}" class="rm-btn rm-btn-primary">Watch Live TV</a>
        <a href="{{ route('sports.football') }}" class="rm-btn rm-btn-secondary">Football Scores</a>
    </div>
</section>
