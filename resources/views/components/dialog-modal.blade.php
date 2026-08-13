@props([
    'id',
    'type' => 'success',
    'title' => null,
    'message' => null,
    'confirmLabel' => null,
])

@php
    $isSuccess = $type === 'success';

    $badgeBg = $isSuccess ? 'bg-success/10' : 'bg-danger/10';
    $iconColor = $isSuccess ? 'text-success' : 'text-danger';
    $iconName = $isSuccess ? 'check-circle-2' : 'x-circle';
    $confirmBtnClass = $isSuccess ? 'bg-success hover:bg-success/90' : 'bg-danger hover:bg-danger/90';

    $resolvedTitle = $title ?? ($isSuccess ? __('Berhasil') : __('Gagal'));
    $resolvedMessage = $message ?? ($isSuccess
        ? __('Data berhasil disimpan.')
        : __('Terjadi kesalahan, silakan coba lagi.'));
    $resolvedConfirmLabel = $confirmLabel ?? __('OK');
@endphp

<div aria-labelledby="{{ $id }}-label" id="{{ $id }}"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
     role="dialog" tabindex="-1">
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
                <div class="mx-auto flex items-center justify-center size-16 rounded-full {{ $badgeBg }}">
                    <i class="size-8 {{ $iconColor }}" data-lucide="{{ $iconName }}"></i>
                </div>
                <div class="mt-5">
                    <h5 class="mb-1 text-lg font-semibold text-default-800" id="{{ $id }}-label">
                        {{ $resolvedTitle }}
                    </h5>
                    <p class="text-default-500 text-sm font-normal">
                        {{ $slot->isEmpty() ? $resolvedMessage : $slot }}
                    </p>
                    <div class="mt-5 flex gap-2 justify-center">
                        <button class="btn text-white {{ $confirmBtnClass }} btn-sm" data-hs-overlay="#{{ $id }}"
                                type="button">
                            {{ $resolvedConfirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
