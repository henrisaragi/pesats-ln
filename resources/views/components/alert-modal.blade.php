@props([
    'id' => 'app-alert-modal',
])

{{--
    Reusable success/error alert dialog (Preline overlay).
    Static markup only - all text/color/visibility is controlled at runtime via
    window.AppAlert (resources/js/alert.js), so any page/JS can trigger it:
        AppAlert.success('Data berhasil disimpan.');
        AppAlert.error('Role super-admin tidak dapat dihapus.');
    Flash messages from session('success')/session('error') are auto-shown on
    load via the data-flash-* attributes below.
--}}
<div aria-labelledby="{{ $id }}-label" id="{{ $id }}"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
     role="dialog" tabindex="-1" data-alert-modal
     data-success-title="{{ __('Berhasil') }}"
     data-success-message="{{ __('Data berhasil disimpan.') }}"
     data-error-title="{{ __('Gagal') }}"
     data-error-message="{{ __('Terjadi kesalahan, silakan coba lagi.') }}"
     @if (session('success'))
         data-flash-type="success" data-flash-message="{{ session('success') }}"
     @elseif (session('error'))
         data-flash-type="error" data-flash-message="{{ session('error') }}"
     @endif
>
    <div
        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div
            class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
            <div class="absolute top-3 end-3">
                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800" data-hs-overlay="#{{ $id }}"
                        type="button">
                    <span class="sr-only">{{ __('Tutup') }}</span>
                    <i class="size-5" data-lucide="x"></i>
                </button>
            </div>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center size-16 rounded-full" data-alert-badge>
                    <i class="size-8" data-lucide="check-circle-2" data-alert-icon-success></i>
                    <i class="hidden size-8" data-lucide="x-circle" data-alert-icon-error></i>
                </div>
                <div class="mt-5">
                    <h5 class="mb-1 text-lg font-semibold text-default-800" id="{{ $id }}-label"
                        data-alert-title></h5>
                    <p class="text-default-500 text-sm font-normal" data-alert-message></p>
                    <div class="mt-5 flex gap-2 justify-center">
                        <button class="btn text-white btn-sm" data-hs-overlay="#{{ $id }}" data-alert-confirm
                                data-default-label="{{ __('OK') }}" type="button">
                            {{ __('OK') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
