@if ($paginator->hasPages())
    @php
        $total = method_exists($paginator, 'total') ? $paginator->total() : $paginator->count();
        $resultLabel = request()->routeIs('admin.iptv-items.*') ? __('items') : __('channels');
    @endphp
    <nav class="pagination-shell" role="navigation" aria-label="{{ __("Pagination Navigation") }}">
        <div class="pagination-shell__meta">
            {{ __('common.results_range', [
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
                'total' => number_format($total),
                'type' => $resultLabel,
            ]) }}
        </div>

        <div class="pagination-shell__links">
            @if ($paginator->onFirstPage())
                <span class="pagination-shell__button pagination-shell__button--disabled" aria-disabled="true">{{ __("← Prev") }}</span>
            @else
                <a class="pagination-shell__button" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __("Previous page") }}">{{ __("← Prev") }}</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-shell__ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-shell__button pagination-shell__button--active" aria-current="page" aria-label="{{ __('Page :page', ['page' => $page]) }}">{{ $page }}</span>
                        @else
                            <a class="pagination-shell__button" href="{{ $url }}" aria-label="{{ __('Page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-shell__button" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __("Next page") }}">{{ __("Next →") }}</a>
            @else
                <span class="pagination-shell__button pagination-shell__button--disabled" aria-disabled="true">{{ __("Next →") }}</span>
            @endif
        </div>
    </nav>
@endif
