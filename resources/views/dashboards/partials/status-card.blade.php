{{-- @param array{route: string, total: int, statuses: list<array{status: \BackedEnum, total: int}>} $summary --}}
<div class="card">
    <div class="card-header flex items-center justify-between gap-2">
        <h6 class="card-title">{{ $title }}</h6>
        <a class="text-sm font-medium text-primary" href="{{ route($summary['route']) }}">{{ __('Lihat Semua') }}</a>
    </div>
    <div class="card-body">
        <div class="flex items-center gap-4 mb-5">
            <div class="flex items-center justify-center rounded-full size-14 bg-primary/10 shrink-0">
                <i class="size-6 text-primary" data-lucide="{{ $icon }}"></i>
            </div>
            <div>
                <h5 class="text-default-800 font-semibold text-2xl">{{ number_format($summary['total']) }}</h5>
                <p class="text-sm text-default-500">{{ __('Total pengajuan') }}</p>
            </div>
        </div>
        <ul class="divide-y divide-default-200">
            @foreach ($summary['statuses'] as $row)
                <li class="flex items-center justify-between py-2.5">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.75 border text-xs {{ $row['status']->badgeClass() }}">
                        {{ $row['status']->label() }}
                    </span>
                    <span class="font-semibold text-default-800">{{ number_format($row['total']) }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
