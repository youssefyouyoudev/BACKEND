@if ($paginator->hasPages())
    @php
        $total = method_exists($paginator, 'total') ? $paginator->total() : $paginator->count();
    @endphp
    <nav class="pagination-shell" role="navigation" aria-label="Pagination Navigation">
        <div class="pagination-shell__meta">
            Showing
            <strong>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($total) }}</strong> channels
        </div>

        <div class="pagination-shell__links">
            @if ($paginator->onFirstPage())
                <span class="pagination-shell__button pagination-shell__button--disabled" aria-disabled="true">← Prev</span>
            @else
                <a class="pagination-shell__button" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">← Prev</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-shell__ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-shell__button pagination-shell__button--active" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                        @else
                            <a class="pagination-shell__button" href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-shell__button" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">Next →</a>
            @else
                <span class="pagination-shell__button pagination-shell__button--disabled" aria-disabled="true">Next →</span>
            @endif
        </div>
    </nav>
@endif
