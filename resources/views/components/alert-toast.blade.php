@props([
    'id' => 'app-alert-toast',
])

{{--
    Reusable success/error toast (small, top-right corner, auto-dismiss - like the
    SweetAlert2 toast mixin). Distinct from <x-alert-modal /> which is a blocking,
    centered dialog; use this when a success/error should just quietly confirm
    without interrupting the user. Any page/JS can trigger it:
        AppToast.success('Entitas berhasil diperbarui.');
        AppToast.error('Gagal menyimpan data.');
    Flash messages from session('toast_success')/session('toast_error') are
    auto-shown on load via the data-flash-* attributes below.
--}}
<div class="fixed top-4 end-4 z-100 pointer-events-none">
    <div class="hidden w-full max-w-xs pointer-events-auto opacity-0 translate-x-4 transition-all duration-300 ease-out"
         id="{{ $id }}" data-alert-toast
         data-success-message="{{ __('Data berhasil disimpan.') }}"
         data-error-message="{{ __('Terjadi kesalahan, silakan coba lagi.') }}"
         @if (session('toast_success'))
             data-flash-type="success" data-flash-message="{{ session('toast_success') }}"
         @elseif (session('toast_error'))
             data-flash-type="error" data-flash-message="{{ session('toast_error') }}"
         @endif
         role="alert">
        <div class="flex items-start gap-3 p-3 card border border-default-200 shadow-lg rounded-xl">
            <div class="shrink-0 flex items-center justify-center size-8 rounded-full" data-alert-toast-badge>
                <i class="size-4" data-lucide="check-circle-2" data-alert-toast-icon-success></i>
                <i class="hidden size-4" data-lucide="x-circle" data-alert-toast-icon-error></i>
            </div>
            <p class="text-sm text-default-800 pt-1.5 grow" data-alert-toast-message></p>
            <button aria-label="{{ __('Tutup') }}" class="shrink-0 mt-1.5 text-default-500 hover:text-default-800"
                    data-alert-toast-close type="button">
                <i class="size-4" data-lucide="x"></i>
            </button>
        </div>
    </div>
</div>
