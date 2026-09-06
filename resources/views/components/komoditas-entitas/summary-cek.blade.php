@props(['item'])

@php
    $summary = $item->summary_cek;
    $modalId = 'komoditas-entitas-summary-cek-modal-'.$item->id;
@endphp

@if ($summary === null)
    <span class="text-default-400 text-xs">{{ __('Belum diperiksa') }}</span>
@else
    <button class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $summary['valid'] ? 'border-success/30 bg-success/10 text-success' : 'border-warning/30 bg-warning/10 text-warning' }}"
            data-hs-overlay="#{{ $modalId }}" type="button">
        <i class="size-3.5" data-lucide="{{ $summary['valid'] ? 'check-circle-2' : 'alert-circle' }}"></i>
        {{ $summary['valid'] ? __('Lolos Otomatis') : __('Perlu Verifikasi Manual') }}
    </button>

    <div aria-labelledby="{{ $modalId }}-label" id="{{ $modalId }}"
         class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none whitespace-normal"
         role="dialog" tabindex="-1">
        <div
            class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
            <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                <div class="card-header">
                    <h3 class="font-bold text-default-800 text-base" id="{{ $modalId }}-label">
                        {{ __('Ringkasan Pemeriksaan Otomatis') }}
                    </h3>
                    <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                            data-hs-overlay="#{{ $modalId }}" type="button">
                        <span class="sr-only">{{ __('Tutup') }}</span>
                        <i class="size-5" data-lucide="x"></i>
                    </button>
                </div>
                <div class="p-4">
                    <p class="text-xs text-default-500 mb-3">
                        {{ __('Diperiksa pada :waktu', ['waktu' => \Illuminate\Support\Carbon::parse($summary['checked_at'])->translatedFormat('d M Y H:i')]) }}
                    </p>
                    <ul class="flex flex-col gap-y-3">
                        @foreach ($summary['checks'] as $check)
                            @php
                                $checkStatus = \App\Enums\DokumenPemeriksaanStatus::from($check['status']);
                            @endphp
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 border text-xs shrink-0 {{ $checkStatus->badgeClass() }}">
                                    {{ $checkStatus->label() }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-default-800 break-words">{{ $check['label'] }}</p>
                                    <p class="text-xs text-default-500 break-words">{{ $check['detail'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                    <button class="btn bg-default-150" data-hs-overlay="#{{ $modalId }}" type="button">
                        {{ __('Tutup') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
