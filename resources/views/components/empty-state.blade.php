@props(['title' => 'Nothing here yet', 'message' => 'Content will appear here when it is available.', 'action' => null, 'href' => null])

<div {{ $attributes->merge(['class' => 'rm-empty-state']) }}>
    <span>{{ $title }}</span>
    <strong>{{ $message }}</strong>
    @if($action && $href)
        <a href="{{ $href }}" class="rm-btn rm-btn-secondary rm-btn-sm">{{ $action }}</a>
    @endif
</div>
