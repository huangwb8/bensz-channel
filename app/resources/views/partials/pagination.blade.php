@if($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
    @endphp

    <nav class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between" aria-label="文章分页">
        <p class="text-sm text-gray-500">
            第 {{ $currentPage }} / {{ $lastPage }} 页，共 {{ $paginator->total() }} 篇
        </p>

        <div class="flex flex-wrap items-center gap-2">
            @if($paginator->onFirstPage())
                <span class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-400">上一页</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 transition-colors hover:border-blue-300 hover:text-blue-700">上一页</a>
            @endif

            @if($startPage > 1)
                <a href="{{ $paginator->url(1) }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-2 text-sm font-medium text-gray-700 transition-colors hover:border-blue-300 hover:text-blue-700">1</a>
                @if($startPage > 2)
                    <span class="inline-flex h-9 min-w-9 items-center justify-center text-sm text-gray-400">...</span>
                @endif
            @endif

            @for($page = $startPage; $page <= $endPage; $page++)
                @if($page === $currentPage)
                    <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-blue-600 px-2 text-sm font-semibold text-white" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-2 text-sm font-medium text-gray-700 transition-colors hover:border-blue-300 hover:text-blue-700">{{ $page }}</a>
                @endif
            @endfor

            @if($endPage < $lastPage)
                @if($endPage < $lastPage - 1)
                    <span class="inline-flex h-9 min-w-9 items-center justify-center text-sm text-gray-400">...</span>
                @endif
                <a href="{{ $paginator->url($lastPage) }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-2 text-sm font-medium text-gray-700 transition-colors hover:border-blue-300 hover:text-blue-700">{{ $lastPage }}</a>
            @endif

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 transition-colors hover:border-blue-300 hover:text-blue-700">下一页</a>
            @else
                <span class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-400">下一页</span>
            @endif
        </div>
    </nav>
@endif
