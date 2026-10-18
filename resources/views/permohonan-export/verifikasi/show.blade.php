@extends('layouts.vertical', ['title' => __('Verifikasi Permohonan Ekspor')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => $permohonan->nomor_permohonan])

    @php
        // Tombol aksi khusus verifikasi (Revisi 5) - tiap tombol punya permission sendiri dan untuk
        // sementara aksinya cuma buka modal placeholder (belum ada logika di baliknya). Dipakai
        // untuk render tombol dan modal-nya sekaligus lewat 1 array supaya konsisten. Tombol
        // Verifikasi tidak di sini: verifikasinya per SATS-LN, jadi ada di kartu SATS-LN terpilih.
        $verifikasiActions = [
            ['key' => 'disposisi', 'permission' => 'permohonan-export.disposisi', 'label' => __('Disposisi'), 'icon' => 'share-2', 'class' => 'bg-default-150 hover:bg-default-200'],
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
                    $selectedSatsLnActivities = $permohonan->activities->where('sats_ln_id', $selectedSatsLn->id)->values();
                    $satsLnDocuments = $selectedSatsLn->documents;
                    $firstSatsLnDocument = $satsLnDocuments->first();
                @endphp
                <div class="card mb-4 print:hidden">
                    <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
                        <h6 class="font-semibold text-sm text-default-700">
                            {{ __('SATS-LN Ke #:nomor', ['nomor' => $selectedSatsLn->nomor_urut]) }}
                        </h6>
                        @php
                            $canVerifySatsLn = in_array($permohonan->status, [\App\Enums\PermohonanStatus::AntrianVerifikasi, \App\Enums\PermohonanStatus::DalamProsesVerifikasi], true);
                        @endphp
                        <div class="flex items-center gap-2 flex-wrap">
                        @can('permohonan-export.verify')
                            @if ($selectedSatsLn->isVerified() && $canVerifySatsLn && $selectedSatsLn->canBeReopened())
                                <button class="btn btn-sm border border-success/30 bg-success/10 text-success hover:bg-success/20" type="button"
                                        data-hs-overlay="#satsln-reopen-modal">
                                    <i class="size-4 me-1" data-lucide="badge-check"></i>{{ __('Terverifikasi') }}
                                </button>
                            @elseif ($selectedSatsLn->isVerified())
                                <button class="btn btn-sm border border-success/30 bg-success/10 text-success" type="button" disabled>
                                    <i class="size-4 me-1" data-lucide="badge-check"></i>{{ __('Terverifikasi') }}
                                </button>
                            @elseif ($canVerifySatsLn)
                                <button class="btn btn-sm bg-success text-white hover:bg-success/90" type="button"
                                        data-hs-overlay="#satsln-verify-modal">
                                    <i class="size-4 me-1" data-lucide="check"></i>{{ __('Verifikasi') }}
                                </button>
                            @endif
                        @endcan
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
                            <button class="btn btn-sm bg-default-150 hover:bg-default-200" type="button"
                                    data-hs-overlay="#satsln-activity-modal">
                                <i class="size-4 me-1" data-lucide="history"></i>{{ __('Aktivitas') }} ({{ $selectedSatsLnActivities->count() }})
                            </button>
                        </div>
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

    @if ($selectedSatsLn && ! $selectedSatsLn->isVerified() && $canVerifySatsLn)
        @can('permohonan-export.verify')
            @php
                $isVerifyRedisplay = old('_modal') === 'satsln-verify-modal';
                $requiresSecurityStamp = $selectedSatsLn->requiresSecurityStamp();
                $verifyFieldError = fn (string $field): ?string => $isVerifyRedisplay ? $errors->first($field) ?: null : null;
                $verifyOld = fn (string $field, mixed $default = null): mixed => $isVerifyRedisplay ? old($field, $default) : $default;
                // Verifikasi ulang (sudah pernah terbit nomor): nilai awal diisi dari data SATS-LN sebelumnya.
                $isReverification = $selectedSatsLn->satsln_number_issued !== null;
                $previousMasaBerlaku = $selectedSatsLn->publish_date && $selectedSatsLn->valid_until
                    ? \App\Enums\MasaBerlakuSatsLn::matching($selectedSatsLn->publish_date, $selectedSatsLn->valid_until)
                    : null;
                $defaultYear = $isReverification ? $selectedSatsLn->year : now()->year;
                $selectedMasaBerlaku = $verifyOld('masa_berlaku', ($previousMasaBerlaku ?? \App\Enums\MasaBerlakuSatsLn::TigaBulan)->value);
                $isAgreed = (string) $verifyOld('setuju', '1') === '1';
            @endphp
            <div aria-labelledby="satsln-verify-modal-label" id="satsln-verify-modal"
                class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <form action="{{ route('permohonan-export.verifikasi.sats-ln.verify', [$permohonan, $selectedSatsLn]) }}"
                              data-satsln-verify-form method="POST">
                            @csrf
                            <input name="_modal" type="hidden" value="satsln-verify-modal">
                            {{-- Diisi dari localStorage "tahun_aktif" (Tahun Aktif saat login) oleh permohonan-show.js. --}}
                            <input data-satsln-verify-year @if ($isReverification) data-satsln-verify-year-locked @endif name="year" type="hidden" value="{{ $verifyOld('year', $defaultYear) }}">
                            <div class="card-header">
                                <h3 class="font-bold text-default-800 text-base" id="satsln-verify-modal-label">
                                    {{ __('Verifikasi SATS-LN Ke #:nomor', ['nomor' => $selectedSatsLn->nomor_urut]) }}
                                </h3>
                                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                        data-hs-overlay="#satsln-verify-modal" type="button">
                                    <span class="sr-only">{{ __('Tutup') }}</span>
                                    <i class="size-5" data-lucide="x"></i>
                                </button>
                            </div>
                            <div class="p-4 flex flex-col gap-4">
                                <div>
                                    <p class="mb-2 text-sm font-medium">{{ __('Apakah Anda setuju dengan SATS-LN ini?') }}</p>
                                    <div class="flex items-center gap-6">
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input class="form-radio" data-satsln-verify-agree name="setuju" type="radio" value="1" @checked($isAgreed)>
                                            {{ __('Ya') }}
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input class="form-radio" data-satsln-verify-agree name="setuju" type="radio" value="0" @checked(! $isAgreed)>
                                            {{ __('Tidak') }}
                                        </label>
                                    </div>
                                    @if ($error = $verifyFieldError('setuju'))
                                        <p class="mt-1 text-xs text-danger">{{ $error }}</p>
                                    @endif
                                </div>
                                {{-- <fieldset disabled> ikut menonaktifkan semua input di dalamnya, jadi field grup yang
                                     tersembunyi tidak ikut terkirim/divalidasi browser. Ditoggle oleh permohonan-show.js. --}}
                                <fieldset class="{{ $isAgreed ? 'hidden' : '' }}" data-satsln-verify-group="0" @disabled($isAgreed)>
                                    <label class="inline-block mb-2 text-sm font-medium" for="satsln-verify-pesan">
                                        {{ __('Pesan untuk SATS-LN ini') }}
                                    </label>
                                    <textarea class="form-input" id="satsln-verify-pesan" maxlength="5000" name="pesan" required
                                              rows="4">{{ $verifyOld('pesan') }}</textarea>
                                    @if ($error = $verifyFieldError('pesan'))
                                        <p class="mt-1 text-xs text-danger">{{ $error }}</p>
                                    @endif
                                </fieldset>
                                <fieldset class="flex flex-col gap-4 {{ $isAgreed ? '' : 'hidden' }}" data-satsln-verify-group="1" @disabled(! $isAgreed)>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="satsln-verify-number">
                                        {{ __('Nomor SATS-LN') }}
                                    </label>
                                    <div class="flex">
                                        <span class="inline-flex items-center px-3 rounded-s-md border border-e-0 border-default-200 bg-default-100 text-sm text-default-600"
                                              data-satsln-verify-year-prefix>{{ $verifyOld('year', $defaultYear) }}</span>
                                        <input class="form-input rounded-s-none" id="satsln-verify-number" inputmode="numeric"
                                               data-satsln-number-check="{{ route('permohonan-export.verifikasi.sats-ln.number-check', ['exclude' => $selectedSatsLn->public_id]) }}"
                                               maxlength="5" name="satsln_number" pattern="\d{1,5}" placeholder="00001" required
                                               type="text" value="{{ $verifyOld('satsln_number', $selectedSatsLn->issuedNumberWithoutPrefix()) }}"/>
                                    </div>
                                    <p class="mt-1 text-xs text-default-500">{{ __('Maksimal 5 angka, mis. 00001.') }}</p>
                                    @php
                                        $numberError = $verifyFieldError('satsln_number');
                                    @endphp
                                    <p class="mt-1 text-xs text-danger {{ $numberError ? '' : 'hidden' }}" data-satsln-number-error>{{ $numberError }}</p>
                                </div>
                                @if ($requiresSecurityStamp)
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium" for="satsln-verify-security-stamp">
                                            {{ __('Security Stamp') }}
                                        </label>
                                        <input class="form-input" id="satsln-verify-security-stamp" maxlength="255"
                                               name="security_stamp" required type="text"
                                               value="{{ $verifyOld('security_stamp', $selectedSatsLn->security_stamp) }}"/>
                                        @if ($error = $verifyFieldError('security_stamp'))
                                            <p class="mt-1 text-xs text-danger">{{ $error }}</p>
                                        @endif
                                    </div>
                                @endif
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="satsln-verify-masa-berlaku">
                                        {{ __('Interval Masa Berlaku') }}
                                    </label>
                                    <select class="form-input" data-satsln-verify-interval id="satsln-verify-masa-berlaku"
                                            name="masa_berlaku" required>
                                        @foreach (\App\Enums\MasaBerlakuSatsLn::cases() as $masaBerlaku)
                                            <option value="{{ $masaBerlaku->value }}" @selected($selectedMasaBerlaku === $masaBerlaku->value)>
                                                {{ $masaBerlaku->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($error = $verifyFieldError('masa_berlaku'))
                                        <p class="mt-1 text-xs text-danger">{{ $error }}</p>
                                    @endif
                                </div>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium" for="satsln-verify-publish-date">
                                            {{ __('Tanggal Terbit') }}
                                        </label>
                                        <input class="form-input" data-satsln-verify-publish-date id="satsln-verify-publish-date"
                                               max="{{ today()->format('Y-m-d') }}" name="publish_date" required type="date"
                                               value="{{ $verifyOld('publish_date', $selectedSatsLn->publish_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}"/>
                                        @if ($error = $verifyFieldError('publish_date'))
                                            <p class="mt-1 text-xs text-danger">{{ $error }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium" for="satsln-verify-valid-until">
                                            {{ __('Tanggal Berakhir') }}
                                        </label>
                                        {{-- Hanya pratinjau: valid_until dihitung ulang di server dari tanggal terbit + interval. --}}
                                        <input class="form-input bg-default-100" data-satsln-verify-valid-until
                                               id="satsln-verify-valid-until" readonly type="date"/>
                                    </div>
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="satsln-verify-special-conditions">
                                        {{ __('Syarat Khusus') }} <span class="text-default-400">({{ __('opsional') }})</span>
                                    </label>
                                    <textarea class="form-input" id="satsln-verify-special-conditions" maxlength="5000"
                                              name="special_conditions" rows="3">{{ $verifyOld('special_conditions', $selectedSatsLn->special_conditions) }}</textarea>
                                    @if ($error = $verifyFieldError('special_conditions'))
                                        <p class="mt-1 text-xs text-danger">{{ $error }}</p>
                                    @endif
                                </div>
                                </fieldset>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#satsln-verify-modal" type="button">
                                    {{ __('Batal') }}
                                </button>
                                <button class="btn bg-success text-white hover:bg-success/90" type="submit">
                                    {{ __('Simpan') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endif

    @if ($selectedSatsLn && $selectedSatsLn->isVerified() && $canVerifySatsLn && $selectedSatsLn->canBeReopened())
        @can('permohonan-export.verify')
            @php
                $isReopenRedisplay = old('_modal') === 'satsln-reopen-modal';
            @endphp
            <div aria-labelledby="satsln-reopen-modal-label" id="satsln-reopen-modal"
                class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <form action="{{ route('permohonan-export.verifikasi.sats-ln.reopen', [$permohonan, $selectedSatsLn]) }}" method="POST">
                            @csrf
                            <input name="_modal" type="hidden" value="satsln-reopen-modal">
                            <div class="card-header">
                                <h3 class="font-bold text-default-800 text-base" id="satsln-reopen-modal-label">
                                    {{ __('Ubah Verifikasi SATS-LN Ke #:nomor', ['nomor' => $selectedSatsLn->nomor_urut]) }}
                                </h3>
                                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                        data-hs-overlay="#satsln-reopen-modal" type="button">
                                    <span class="sr-only">{{ __('Tutup') }}</span>
                                    <i class="size-5" data-lucide="x"></i>
                                </button>
                            </div>
                            <div class="p-4">
                                <p class="mb-3 text-sm text-default-500">
                                    {{ __('Setelah disimpan, tombol Verifikasi aktif kembali untuk mengubah data SATS-LN ini.') }}
                                </p>
                                <label class="inline-block mb-2 text-sm font-medium" for="satsln-reopen-pesan">
                                    {{ __('Pesan') }}
                                </label>
                                <textarea class="form-input" id="satsln-reopen-pesan" maxlength="5000" name="pesan" required
                                          rows="4">{{ $isReopenRedisplay ? old('pesan') : '' }}</textarea>
                                @if ($isReopenRedisplay && $errors->has('pesan'))
                                    <p class="mt-1 text-xs text-danger">{{ $errors->first('pesan') }}</p>
                                @endif
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#satsln-reopen-modal" type="button">
                                    {{ __('Batal') }}
                                </button>
                                <button class="btn bg-primary text-white hover:bg-primary/90" type="submit">
                                    {{ __('Simpan') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endif

    @if ($selectedSatsLn)
        <div aria-labelledby="satsln-activity-modal-label" id="satsln-activity-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
            role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="satsln-activity-modal-label">
                            {{ __('Aktivitas SATS-LN Ke #:nomor', ['nomor' => $selectedSatsLn->nomor_urut]) }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#satsln-activity-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <div class="p-5">
                        {{-- Milestone dari terbaru ke terlama: titik + garis vertikal menyambung antar item. --}}
                        @forelse ($selectedSatsLnActivities as $activity)
                            <div class="relative flex gap-4" data-satsln-activity-item>
                                <div class="relative flex flex-col items-center">
                                    <span class="z-10 mt-1 size-3 shrink-0 rounded-full ring-4 ring-primary/15 {{ $loop->first ? 'bg-primary' : 'bg-default-300' }}"></span>
                                    @unless ($loop->last)
                                        <span class="w-px flex-1 bg-default-200"></span>
                                    @endunless
                                </div>
                                <div class="flex-1 {{ $loop->last ? '' : 'pb-6' }}">
                                    <p class="inline-flex items-center gap-1.5 text-xs font-medium text-default-500">
                                        <i class="size-3.5" data-lucide="calendar-clock"></i>
                                        {{ $activity->created_at?->translatedFormat('d M Y, H:i') }}
                                    </p>
                                    <p class="mt-1 text-sm text-default-700 whitespace-pre-line break-words">{{ $activity->messages }}</p>
                                    @if ($activity->user)
                                        <p class="mt-1 inline-flex items-center gap-1 text-xs text-default-400">
                                            <i class="size-3.5" data-lucide="user"></i>{{ $activity->user->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-default-500">{{ __('Belum ada aktivitas.') }}</p>
                        @endforelse
                    </div>
                    <div class="p-4 flex justify-end border-t border-dashed border-default-900/10">
                        <button class="btn bg-default-150" data-hs-overlay="#satsln-activity-modal" type="button">
                            {{ __('Tutup') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
