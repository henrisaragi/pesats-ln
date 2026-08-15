@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="flex flex-wrap items-center gap-2">
        @if ($paginator->onFirstPage())
            <span
                class="btn btn-sm shrink-0 border bg-transparent border-default-200 text-default-400 opacity-50 pointer-events-none">
                <i class="size-4 me-1" data-lucide="chevron-left"></i> {{ __('Sebelumnya') }}
            </span>
        @else
            <a class="btn btn-sm shrink-0 border bg-transparent border-default-200 text-default-600 hover:bg-primary/10 hover:text-primary hover:border-primary/10"
               href="{{ $paginator->previousPageUrl() }}">
                <i class="size-4 me-1" data-lucide="chevron-left"></i> {{ __('Sebelumnya') }}
            </a>
        @endif

        @php
            $onEachSide = 2;
            $lastPage = $paginator->lastPage();
            $currentPage = $paginator->currentPage();

            $pages = collect([1])
                ->when($lastPage > 1, fn ($collection) => $collection->push($lastPage))
                ->concat(range(max(1, $currentPage - $onEachSide), min($lastPage, $currentPage + $onEachSide)))
                ->unique()
                ->sort()
                ->values();
        @endphp

        @foreach ($pages as $index => $page)
            @if ($index > 0 && $page - $pages[$index - 1] > 1)
                <span class="btn h-7.5 min-w-7.5 shrink-0 px-1.5 bg-transparent border-0 text-default-400 pointer-events-none">&hellip;</span>
            @endif
            <a class="btn h-7.5 min-w-7.5 shrink-0 px-1.5 tabular-nums {{ $page === $currentPage ? 'bg-primary text-white' : 'bg-transparent border border-default-200 text-default-600 hover:bg-primary/10 hover:text-primary hover:border-primary/10' }}"
               href="{{ $paginator->url($page) }}">
                {{ $page }}
            </a>
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="btn btn-sm shrink-0 border bg-transparent border-default-200 text-default-600 hover:bg-primary/10 hover:text-primary hover:border-primary/10"
               href="{{ $paginator->nextPageUrl() }}">
                {{ __('Selanjutnya') }} <i class="size-4 ms-1" data-lucide="chevron-right"></i>
            </a>
        @else
            <span
                class="btn btn-sm shrink-0 border bg-transparent border-default-200 text-default-400 opacity-50 pointer-events-none">
                {{ __('Selanjutnya') }} <i class="size-4 ms-1" data-lucide="chevron-right"></i>
            </span>
        @endif
    </nav>
@endif
