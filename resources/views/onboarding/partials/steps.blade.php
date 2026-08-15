@php
    $steps = [
        1 => __('Jenis Entitas'),
        2 => __('Data Entitas'),
        3 => __('Detail'),
    ];
    $totalSteps = count($steps);
@endphp

<div class="flex items-center justify-center gap-2 sm:gap-4 mb-8" data-tour="steps">
    @foreach ($steps as $number => $label)
        <div class="flex items-center gap-2">
            <div
                class="flex items-center justify-center size-9 shrink-0 rounded-full text-sm font-semibold transition-colors
                {{ $number < $currentStep ? 'bg-success text-white' : ($number === $currentStep ? 'bg-primary text-white' : 'bg-default-150 text-default-500') }}">
                @if ($number < $currentStep)
                    <i class="size-4" data-lucide="check"></i>
                @else
                    {{ $number }}
                @endif
            </div>
            <span
                class="hidden sm:inline text-sm font-medium {{ $number <= $currentStep ? 'text-default-800' : 'text-default-400' }}">
                {{ $label }}
            </span>
        </div>
        @if ($number < $totalSteps)
            <div class="w-6 sm:w-10 h-0.5 {{ $number < $currentStep ? 'bg-success' : 'bg-default-200' }}"></div>
        @endif
    @endforeach
</div>
