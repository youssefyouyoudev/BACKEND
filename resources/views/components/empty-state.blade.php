@props([
    'title' => 'Nothing here yet',
    'message' => 'Content will appear here when it is available.',
    'action' => null,
    'href' => null,
    'secondaryAction' => null,
    'secondaryHref' => null,
])

<div {{ $attributes->merge(['class' => 'rm-empty-state']) }}>
    <span>{{ $title }}</span>
    <strong>{{ $message }}</strong>
    @if(($action && $href) || ($secondaryAction && $secondaryHref))
        <div class="rm-empty-state__actions">
            @if($action && $href)
                <a href="{{ $href }}" class="rm-btn rm-btn-primary rm-btn-sm">{{ $action }}</a>
            @endif
            @if($secondaryAction && $secondaryHref)
                <a href="{{ $secondaryHref }}" class="rm-btn rm-btn-secondary rm-btn-sm">{{ $secondaryAction }}</a>
            @endif
        </div>
    @endif
</div>
