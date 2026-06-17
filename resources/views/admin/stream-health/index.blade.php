@extends('layouts.admin')

@section('content')
<section class="page-header">
    <div>
        <p class="page-header__eyebrow">{{ __('Monitoring') }}</p>
        <h1>{{ __('Stream Health') }}</h1>
        <p class="page-header__copy">{{ __('Aggregate stream status for player failover and infrastructure checks. This page does not log video segments.') }}</p>
    </div>
</section>

<section class="dashboard-grid dashboard-grid--compact">
    <article class="stat-card">
        <span>{{ __('Public live IPTV') }}</span>
        <strong>{{ number_format($stats['iptv_public']) }}</strong>
        <small>{{ __('of :total total live items', ['total' => number_format($stats['iptv_total'])]) }}</small>
    </article>
    <article class="stat-card">
        <span>{{ __('Failed IPTV items') }}</span>
        <strong>{{ number_format($stats['iptv_failed']) }}</strong>
        <small>{{ __('Health checks mark these as offline.') }}</small>
    </article>
    <article class="stat-card">
        <span>{{ __('Channel sources') }}</span>
        <strong>{{ number_format($stats['channel_sources']) }}</strong>
        <small>{{ __(':count offline', ['count' => number_format($stats['channel_sources_failed'])]) }}</small>
    </article>
    <article class="stat-card">
        <span>{{ __('Server load') }}</span>
        <strong>{{ $server['load'] ? implode(' / ', array_map(fn ($value) => number_format($value, 2), $server['load'])) : __('Unavailable') }}</strong>
        <small>{{ __('PHP :sapi, memory :memory', ['sapi' => $server['php_sapi'], 'memory' => $server['memory_limit']]) }}</small>
    </article>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">{{ __('Failures') }}</p>
            <h2>{{ __('Most recent failing IPTV items') }}</h2>
        </div>
    </div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>{{ __('Channel') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Last checked') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats['top_failing_items'] as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->health_status }}</td>
                        <td>{{ optional($item->last_checked_at)->diffForHumans() ?? __('Never') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">{{ __('No failing IPTV items found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="surface-card">
    <div class="surface-card__header">
        <div>
            <p class="surface-card__eyebrow">{{ __('Sources') }}</p>
            <h2>{{ __('Most recent failing channel sources') }}</h2>
        </div>
    </div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>{{ __('Channel') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Last checked') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats['top_failing_sources'] as $source)
                    <tr>
                        <td>{{ $source->channel?->name ?? __('Unknown channel') }}</td>
                        <td>{{ $source->health_status }}</td>
                        <td>{{ optional($source->last_checked_at)->diffForHumans() ?? __('Never') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">{{ __('No failing channel sources found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
