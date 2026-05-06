@if ($paginator->hasPages())
    @php
        $total = method_exists($paginator, 'total') ? $paginator->total() : $paginator->count();
    @endphp
    <nav class="pagination-shell" role="navigation" aria-label="Pagination Navigation">
        <div class="pagination-shell__meta">
            Showing {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }}
            of {{ $total }} channels
        </div>

        <div class="pagination-shell__links">
            @if ($paginator->onFirstPage())
                <span class="pagination-shell__button pagination-shell__button--disabled">Previous</span>
            @else
                <a class="pagination-shell__button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-shell__ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-shell__button pagination-shell__button--active">{{ $page }}</span>
                        @else
                            <a class="pagination-shell__button" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-shell__button" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="pagination-shell__button pagination-shell__button--disabled">Next</span>
            @endif
        </div>
    </nav>
@endif
