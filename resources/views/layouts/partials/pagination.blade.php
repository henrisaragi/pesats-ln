@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="flex items-center gap-2">
        @if ($paginator->onFirstPage())
            <span
                class="btn btn-sm border bg-transparent border-default-200 text-default-400 opacity-50 pointer-events-none">
                <i class="size-4 me-1" data-lucide="chevron-left"></i> {{ __('Sebelumnya') }}
            </span>
        @else
            <a class="btn btn-sm border bg-transparent border-default-200 text-default-600 hover:bg-primary/10 hover:text-primary hover:border-primary/10"
               href="{{ $paginator->previousPageUrl() }}">
                <i class="size-4 me-1" data-lucide="chevron-left"></i> {{ __('Sebelumnya') }}
            </a>
        @endif

        @foreach (range(1, $paginator->lastPage()) as $page)
            <a class="btn size-7.5 {{ $page === $paginator->currentPage() ? 'bg-primary text-white' : 'bg-transparent border border-default-200 text-default-600 hover:bg-primary/10 hover:text-primary hover:border-primary/10' }}"
               href="{{ $paginator->url($page) }}">
                {{ $page }}
            </a>
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="btn btn-sm border bg-transparent border-default-200 text-default-600 hover:bg-primary/10 hover:text-primary hover:border-primary/10"
               href="{{ $paginator->nextPageUrl() }}">
                {{ __('Selanjutnya') }} <i class="size-4 ms-1" data-lucide="chevron-right"></i>
            </a>
        @else
            <span
                class="btn btn-sm border bg-transparent border-default-200 text-default-400 opacity-50 pointer-events-none">
                {{ __('Selanjutnya') }} <i class="size-4 ms-1" data-lucide="chevron-right"></i>
            </span>
        @endif
    </nav>
@endif
