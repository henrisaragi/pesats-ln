{{-- Variabel: $modalId, $message, $action --}}
<div aria-labelledby="{{ $modalId }}-label" id="{{ $modalId }}"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
     role="dialog" tabindex="-1">
    <div
        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center whitespace-normal">
        <div class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
            <div class="absolute top-3 end-3">
                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800" data-hs-overlay="#{{ $modalId }}" type="button">
                    <span class="sr-only">{{ __('Tutup') }}</span>
                    <i class="size-5" data-lucide="x"></i>
                </button>
            </div>
            <div class="text-center">
                <img alt="" class="size-12 mx-auto" src="{{ asset('images/delete.png') }}"/>
                <div class="mt-5 text-center">
                    <h5 class="mb-1 text-lg font-semibold text-default-800" id="{{ $modalId }}-label">{{ __('Anda yakin?') }}</h5>
                    <p class="text-default-500 text-sm font-normal">{{ $message }}</p>
                    <div class="mt-5 flex gap-2 justify-center">
                        <button class="btn btn-sm bg-default-150" data-hs-overlay="#{{ $modalId }}" type="button">{{ __('Batal') }}</button>
                        <form action="{{ $action }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm bg-danger text-white border-0" type="submit">{{ __('Ya, Hapus!') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
