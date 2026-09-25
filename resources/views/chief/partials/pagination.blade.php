@once
    <style>
        .chief-pagination { display: flex; grid-column: 1 / -1; align-items: center; justify-content: space-between; gap: 14px; margin: 0; padding: 14px 18px; border-top: 1px solid #E9E5DE; color: #4A4E58; font-size: 13px; }
        .chief-pagination__summary strong { color: #111827; font-weight: 900; }
        .chief-pagination__controls { display: inline-flex; align-items: center; gap: 3px; padding: 3px; border: 1px solid #E9E5DE; border-radius: 999px; background: #fff; }
        .chief-pagination__link, .chief-pagination__current, .chief-pagination__disabled, .chief-pagination__ellipsis { display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 10px; border-radius: 999px; color: #4A4E58; font-size: 12px; font-weight: 850; text-decoration: none; }
        .chief-pagination__link:hover { background: #FAF8F5; }
        .chief-pagination__current { background: #E4602C; color: #fff; }
        .chief-pagination__disabled { opacity: .4; cursor: default; }
        .chief-pagination__ellipsis { min-width: 24px; padding: 0 5px; color: #94a3b8; }
        @media (max-width: 767px) {
            .chief-pagination { align-items: stretch; flex-direction: column; padding: 12px; font-size: 12px; }
            .chief-pagination__controls { justify-content: center; overflow-x: auto; border-radius: 14px; }
        }
    </style>
@endonce

@php
    $ariaLabel = $ariaLabel ?? 'Pagination';
    $summaryLabel = $summaryLabel ?? 'results';
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $pageWindow = collect(range(1, max(1, $lastPage)))
        ->filter(fn ($page) => $page === 1 || $page === $lastPage || abs($page - $currentPage) <= 1)
        ->values();
    $previousPage = null;
@endphp

<nav class="chief-pagination {{ $class ?? '' }}" aria-label="{{ $ariaLabel }}">
    <div class="chief-pagination__summary">
        Showing <strong>{{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> {{ $summaryLabel }}
    </div>
    <div class="chief-pagination__controls">
        @if($paginator->onFirstPage())
            <span class="chief-pagination__disabled" aria-disabled="true" aria-label="Previous page">&lsaquo;</span>
        @else
            <a class="chief-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo;</a>
        @endif

        @foreach($pageWindow as $page)
            @if($previousPage && $page - $previousPage > 1)
                <span class="chief-pagination__ellipsis" aria-hidden="true">&hellip;</span>
            @endif

            @if($page === $currentPage)
                <span class="chief-pagination__current" aria-current="page">{{ $page }}</span>
            @else
                <a class="chief-pagination__link" href="{{ $paginator->url($page) }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
            @endif

            @php $previousPage = $page; @endphp
        @endforeach

        @if($paginator->hasMorePages())
            <a class="chief-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&rsaquo;</a>
        @else
            <span class="chief-pagination__disabled" aria-disabled="true" aria-label="Next page">&rsaquo;</span>
        @endif
    </div>
</nav>
