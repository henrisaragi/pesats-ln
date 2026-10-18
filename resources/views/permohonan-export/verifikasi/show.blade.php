@extends('layouts.vertical', ['title' => __('Verifikasi Permohonan Ekspor')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => $permohonan->nomor_permohonan])

    @php
        // Tombol aksi khusus verifikasi (Revisi 5) - tiap tombol punya permission sendiri dan untuk
        // sementara aksinya cuma buka modal placeholder (belum ada logika di baliknya). Dipakai
        // untuk render tombol dan modal-nya sekaligus lewat 1 array supaya konsisten.
        $verifikasiActions = [
            ['key' => 'disposisi', 'permission' => 'permohonan-export.disposisi', 'label' => __('Disposisi'), 'icon' => 'share-2', 'class' => 'bg-default-150 hover:bg-default-200'],
            ['key' => 'verifikasi', 'permission' => 'permohonan-export.verify', 'label' => __('Verifikasi'), 'icon' => 'check', 'class' => 'bg-success text-white hover:bg-success/90'],
            ['key' => 'print', 'permission' => 'permohonan-export.print', 'label' => __('Print/Cetak'), 'icon' => 'printer', 'class' => 'bg-primary text-white hover:bg-primary/90'],
            ['key' => 'update-status', 'permission' => 'permohonan-export.update-status', 'label' => __('Update Status (Dalam Proses Penandatanganan)'), 'icon' => 'pen-line', 'class' => 'bg-default-150 hover:bg-default-200'],
            ['key' => 'siap-diambil', 'permission' => 'permohonan-export.siap-diambil', 'label' => __('Siap Diambil'), 'icon' => 'package-check', 'class' => 'bg-default-150 hover:bg-default-200'],
            ['key' => 'telah-diambil', 'permission' => 'permohonan-export.telah-diambil', 'label' => __('Telah Diambil'), 'icon' => 'circle-check-big', 'class' => 'bg-default-150 hover:bg-default-200'],
        ];
    @endphp

    <div class="card mb-4">
        <div class="p-4 flex items-center justify-between flex-wrap gap-4">
            <div>
                <p class="text-sm text-default-500">{{ __('Entitas') }}: {{ $permohonan->entitas?->name }}</p>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs mt-1 {{ $permohonan->status->badgeClass() }}">
                    {{ $permohonan->status->label() }}
                </span>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                @foreach ($verifikasiActions as $action)
                    @can($action['permission'])
                        <button class="btn btn-sm {{ $action['class'] }}" type="button"
                                data-hs-overlay="#verifikasi-action-modal-{{ $action['key'] }}">
                            <i class="size-4 me-1" data-lucide="{{ $action['icon'] }}"></i>{{ $action['label'] }}
                        </button>
                    @endcan
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-4 grid-cols-1 gap-5">
        <div class="lg:col-span-1 col-span-1 card print:hidden flex flex-col">
            <div class="sticky top-24 flex flex-col h-full">
                <div class="card-body shrink-0">
                    <h6 class="mb-4 card-title">{{ __('Daftar SATS-LN') }}</h6>
                    <div class="relative w-full">
                        <input class="ps-11 form-input form-input-sm" data-satsln-search-input
                            placeholder="{{ __('Cari...') }}" type="text" />
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3">
                            <i class="size-4 flex items-center fill-default-100 text-default-500"
                                data-lucide="search"></i>
                        </div>
                    </div>
                </div>
                <div class="overflow-y-auto flex-1" data-simplebar="">
                    @forelse ($permohonan->satsLn as $satsLn)
                        @php
                            $isSigned = $satsLn->signed_at !== null;
                            $isPrinted = $satsLn->printed_at !== null;
                            $satsLnStatusLabel = $isSigned
                                ? __('Ditandatangani')
                                : ($isPrinted
                                    ? __('Dicetak')
                                    : __('Preview'));
                            $satsLnStatusClass = $isSigned
                                ? 'inline-flex items-center gap-x-1.5 py-0.5 px-2.5 rounded text-xs font-medium bg-success/10 text-success'
                                : ($isPrinted
                                    ? 'inline-flex items-center gap-x-1.5 py-0.5 px-2.5 rounded text-xs font-medium bg-primary/10 text-primary'
                                    : 'text-warning bg-warning/20 rounded text-xs py-0.5 px-2.5');
                            $searchHaystack = collect([
                                $satsLn->nomor_urut,
                                $satsLn->customerCompany?->name,
                                ...$satsLn->items->flatMap(fn ($item) => [
                                    $item->species?->scientific_name,
                                    $item->species?->common_name,
                                ]),
                            ])->filter()->implode(' ');
                        @endphp
                        <div data-satsln-search-item data-search-haystack="{{ \Illuminate\Support\Str::lower($searchHaystack) }}"
                            class="relative border-t p-5 border-default-200 hover:bg-default-50 transition-colors {{ $selectedSatsLn?->id === $satsLn->id ? 'bg-default-100' : '' }}">
                            <a class="absolute inset-0"
                                href="{{ route('permohonan-export.verifikasi.sats-ln.show', [$permohonan, $satsLn]) }}"
                                aria-label="{{ __('Lihat detail SATS-LN Ke #:nomor', ['nomor' => $satsLn->nomor_urut]) }}"></a>
                            <div class="flex justify-between">
                                <div class="w-full">
                                    <h6 class="font-semibold text-sm text-default-700">
                                        {{ __('SATS-LN Ke #:nomor', ['nomor' => $satsLn->nomor_urut]) }}
                                    </h6>
                                    <div>
                                        <div class="flex items-center justify-between gap-6 mt-3">
                                            <span class="inline-flex text-sm items-center">
                                                <i class="size-4 text-default-500 me-1.5" data-lucide="calendar-clock"></i>
                                                {{ $satsLn->publish_date?->translatedFormat('d M Y') ?? __('Menunggu...') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative z-10 flex flex-col items-end gap-2">
                                    <span class="{{ $satsLnStatusClass }}">{{ $satsLnStatusLabel }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="p-5 text-sm text-default-500">{{ __('Belum ada SATS-LN.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="lg:col-span-3 col-span-1">
            @if ($selectedSatsLn)
                @php
                    $satsLnDocuments = $selectedSatsLn->documents;
                    $firstSatsLnDocument = $satsLnDocuments->first();
                @endphp
                <div class="card mb-4 print:hidden">
                    <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
                        <h6 class="font-semibold text-sm text-default-700">
                            {{ __('SATS-LN Ke #:nomor', ['nomor' => $selectedSatsLn->nomor_urut]) }}
                        </h6>
                        @if ($firstSatsLnDocument)
                            <button class="btn btn-sm bg-primary text-white hover:bg-primary/90" type="button"
                                    data-hs-overlay="#satsln-document-view-modal"
                                    data-document-viewer-url="{{ route('permohonan-export.verifikasi.sats-ln.documents.preview', [$permohonan, $selectedSatsLn, $firstSatsLnDocument]) }}"
                                    data-document-viewer-title="{{ $firstSatsLnDocument->nomor_document }}"
                                    data-document-viewer-download-url="{{ route('permohonan-export.verifikasi.sats-ln.documents.download', [$permohonan, $selectedSatsLn, $firstSatsLnDocument]) }}">
                                <i class="size-4 me-1" data-lucide="file-text"></i>{{ __('Lihat Dokumen') }} ({{ $satsLnDocuments->count() }})
                            </button>
                        @else
                            <span class="text-sm text-default-500">{{ __('Belum ada dokumen terlampir.') }}</span>
                        @endif
                    </div>
                </div>
                <div class="card">
                    @include('permohonan-export.satsln', [
                        'satsLn' => $selectedSatsLn,
                        'permohonan' => $permohonan,
                        'realisasiNasionalRunningTotalByItemId' => $realisasiNasionalRunningTotalByItemId,
                    ])
                </div>
            @else
                <div class="card h-full flex items-center justify-center p-10 text-center text-default-500">
                    {{ __('Belum ada SATS-LN.') }}
                </div>
            @endif
        </div>
    </div>

    @can('permohonan-export.disposisi')
        @php
            $isDisposisiRedisplay = old('_modal') === 'verifikasi-action-modal-disposisi';
        @endphp
        <div aria-labelledby="verifikasi-action-modal-disposisi-label" id="verifikasi-action-modal-disposisi"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
            role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <form action="{{ route('permohonan-export.verifikasi.disposisi', $permohonan) }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="verifikasi-action-modal-disposisi">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="verifikasi-action-modal-disposisi-label">
                                {{ __('Disposisi') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#verifikasi-action-modal-disposisi" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        {{--
                            Sengaja TIDAK diberi overflow-y-auto/max-h di sini: meng-clip dropdown Choices.js
                            (lihat claude/permohonan-task.md) - overlay terluar .hs-overlay sudah scroll sendiri.
                        --}}
                        <div class="p-4">
                            <label class="inline-block mb-2 text-sm font-medium" for="disposisi-verifikator-id">
                                {{ __('Disposisi Kepada') }}
                            </label>
                            <x-select id="disposisi-verifikator-id" name="verifikator_id"
                                      :options="$verifikatorOptions"
                                      :selected="$isDisposisiRedisplay ? old('verifikator_id') : null"
                                      :placeholder="__('Pilih Verifikator')" required />
                            @if ($isDisposisiRedisplay && $errors->has('verifikator_id'))
                                <p class="mt-1 text-xs text-danger">{{ $errors->first('verifikator_id') }}</p>
                            @endif
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#verifikasi-action-modal-disposisi" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white hover:bg-primary/90" type="submit">
                                {{ __('Disposisi') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @foreach ($verifikasiActions as $action)
        @continue($action['key'] === 'disposisi')
        @can($action['permission'])
            <div aria-labelledby="verifikasi-action-modal-{{ $action['key'] }}-label"
                id="verifikasi-action-modal-{{ $action['key'] }}"
                class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="verifikasi-action-modal-{{ $action['key'] }}-label">
                                {{ $action['label'] }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#verifikasi-action-modal-{{ $action['key'] }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="p-4 text-sm text-default-600">
                            {{ __('Ini button :label. Fitur ini akan segera tersedia.', ['label' => $action['label']]) }}
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#verifikasi-action-modal-{{ $action['key'] }}" type="button">
                                {{ __('Tutup') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    @endforeach

    @if ($selectedSatsLn?->documents->isNotEmpty())
        {{-- id & data-satsln-document-select sama seperti halaman permohonan-export/show supaya switcher
             dokumen di permohonan-show.js langsung berlaku di sini. --}}
        <x-document-viewer-modal id="satsln-document-view-modal">
            <x-slot:switcher>
                @if ($selectedSatsLn->documents->count() > 1)
                    <select class="form-input form-input-sm w-52" data-satsln-document-select>
                        @foreach ($selectedSatsLn->documents as $document)
                            <option data-download-url="{{ route('permohonan-export.verifikasi.sats-ln.documents.download', [$permohonan, $selectedSatsLn, $document]) }}"
                                    data-preview-url="{{ route('permohonan-export.verifikasi.sats-ln.documents.preview', [$permohonan, $selectedSatsLn, $document]) }}"
                                    data-title="{{ $document->nomor_document }}"
                                    value="{{ $document->id }}"
                                    @selected($loop->first)>
                                {{ $document->nomor_document }} ({{ $document->type_document->label() }})
                            </option>
                        @endforeach
                    </select>
                @endif
            </x-slot:switcher>
            <x-slot:aside>
                @include('permohonan-export.satsln', [
                    'satsLn' => $selectedSatsLn,
                    'permohonan' => $permohonan,
                    'realisasiNasionalRunningTotalByItemId' => $realisasiNasionalRunningTotalByItemId,
                ])
            </x-slot:aside>
        </x-document-viewer-modal>
    @endif
@endsection

@section('scripts')
    @vite(['resources/js/pages/permohonan-show.js'])
@endsection
