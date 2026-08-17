@props([
    'id' => 'komoditas-entitas-expiry-alert-modal',
])

{{--
    Alert kedaluwarsa Komoditas Entitas (lihat claude/status-cek-task.md) - selalu ditampilkan
    ulang setiap login berhasil kalau ada komoditas Verified milik entitas user yang
    tanggal_berakhir-nya kurang dari 3 bulan lagi (dihitung oleh
    KomoditasEntitasService::expiringWithin() dan di-flash ke session sekali oleh
    AuthenticationController::login() - lihat data-flash-items di bawah, ikut pola
    data-flash-* yang sudah dipakai <x-alert-modal/>).
--}}
<div aria-labelledby="{{ $id }}-label" id="{{ $id }}"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
     role="dialog" tabindex="-1" data-komoditas-entitas-expiry-alert
     @if (session('komoditas_entitas_expiring_alert'))
         data-flash-items="{{ json_encode(session('komoditas_entitas_expiring_alert')) }}"
     @endif
>
    <div
        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-md m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div
            class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
            <div class="absolute top-3 end-3">
                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                        data-hs-overlay="#{{ $id }}" type="button">
                    <span class="sr-only">{{ __('Tutup') }}</span>
                    <i class="size-5" data-lucide="x"></i>
                </button>
            </div>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center size-16 rounded-full bg-warning/10">
                    <i class="size-8 text-warning" data-lucide="alert-triangle"></i>
                </div>
                <div class="mt-5">
                    <h5 class="mb-1 text-lg font-semibold text-default-800" id="{{ $id }}-label">
                        {{ __('Komoditas Mendekati Kedaluwarsa') }}
                    </h5>
                    <p class="text-default-500 text-sm font-normal">
                        {{ __('Segera ajukan perpanjangan sebelum tanggal berakhir berikut ini:') }}
                    </p>
                    <ul class="mt-3 flex flex-col gap-1.5 text-start" data-expiry-alert-list></ul>
                    <div class="mt-5 flex gap-2 justify-center">
                        <button class="btn text-white btn-sm bg-warning hover:bg-warning/90"
                                data-hs-overlay="#{{ $id }}" type="button">
                            {{ __('Mengerti') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
