{{-- Modal dasar (pola Preline yang sama dengan halaman kuota lain). Isi via $slot. --}}
@props(['id', 'title', 'size' => 'sm:max-w-lg'])
<div aria-labelledby="{{ $id }}-label" id="{{ $id }}"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none whitespace-normal"
     role="dialog" tabindex="-1">
    <div class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 {{ $size }} sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
            <div class="card-header">
                <h3 class="font-bold text-default-800 text-base" id="{{ $id }}-label">{{ $title }}</h3>
                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800" data-hs-overlay="#{{ $id }}" type="button">
                    <span class="sr-only">{{ __('Tutup') }}</span>
                    <i class="size-5" data-lucide="x"></i>
                </button>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
