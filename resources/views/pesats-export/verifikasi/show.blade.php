@extends('layouts.vertical', ['title' => __('Verifikasi Permohonan Ekspor')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => $permohonan->nomor_permohonan])

    <div class="card mb-4">
        <div class="p-4 flex items-center justify-between flex-wrap gap-4">
            <div>
                <p class="text-sm text-default-500">{{ __('Entitas') }}: {{ $permohonan->entitas?->name }}</p>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs mt-1 {{ $permohonan->status->badgeClass() }}">
                    {{ $permohonan->status->label() }}
                </span>
            </div>

            @if ($permohonan->status === \App\Enums\PermohonanStatus::AntrianVerifikasi)
                <div class="flex items-center gap-2">
                    <form action="{{ route('pesats-export.verifikasi.verify', $permohonan) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm bg-success text-white" type="submit">
                            <i class="size-4 me-1" data-lucide="check"></i>{{ __('Verifikasi') }}
                        </button>
                    </form>
                    <button class="btn btn-sm bg-danger/10 text-danger" data-hs-overlay="#reject-modal" type="button">
                        <i class="size-4 me-1" data-lucide="x"></i>{{ __('Tolak') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    <div class="flex flex-col gap-4">
        @foreach ($permohonan->satsLn as $satsLn)
            @include('pesats-export.partials.sats-ln-detail', ['satsLn' => $satsLn, 'staffView' => true])
        @endforeach
    </div>

    <div aria-labelledby="reject-modal-label" id="reject-modal"
         class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
         role="dialog" tabindex="-1">
        <div
            class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
            <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                <div class="card-header">
                    <h3 class="font-bold text-default-800 text-base" id="reject-modal-label">{{ __('Tolak Permohonan') }}</h3>
                    <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800" data-hs-overlay="#reject-modal" type="button">
                        <span class="sr-only">{{ __('Tutup') }}</span>
                        <i class="size-5" data-lucide="x"></i>
                    </button>
                </div>
                <form action="{{ route('pesats-export.verifikasi.reject', $permohonan) }}" method="POST">
                    @csrf
                    <div class="p-4">
                        <label class="inline-block mb-2 text-sm font-medium" for="catatan_verifikasi">{{ __('Catatan Penolakan') }}</label>
                        <textarea class="form-input" id="catatan_verifikasi" name="catatan_verifikasi" rows="3"></textarea>
                    </div>
                    <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                        <button class="btn bg-default-150" data-hs-overlay="#reject-modal" type="button">{{ __('Batal') }}</button>
                        <button class="btn bg-danger text-white" type="submit">{{ __('Ya, Tolak') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
