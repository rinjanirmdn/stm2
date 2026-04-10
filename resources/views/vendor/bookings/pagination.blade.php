@if ($paginator->hasPages())
    <nav class="mb-pagination-nav">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="mb-pagination-btn mb-pagination-btn--disabled">
                <i class="fas fa-chevron-left"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="mb-pagination-btn" rel="prev">
                <i class="fas fa-chevron-left"></i>
            </a>
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="mb-pagination-btn" rel="next">
                <i class="fas fa-chevron-right"></i>
            </a>
        @else
            <span class="mb-pagination-btn mb-pagination-btn--disabled">
                <i class="fas fa-chevron-right"></i>
            </span>
        @endif
    </nav>
@endif
