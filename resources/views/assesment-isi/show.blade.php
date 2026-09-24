@extends('layouts.vertical', ['title' => $isi->assesment->judul])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => $isi->assesment->judul])

    <div class="mb-4 flex items-center justify-between flex-wrap gap-2">
        <a class="text-primary hover:underline inline-flex items-center gap-1" href="{{ route('assesment-isi.index') }}">
            <i class="size-4" data-lucide="arrow-left"></i>{{ __('Kembali') }}
        </a>
        <div class="flex items-center gap-2 flex-wrap justify-end">
            @if ($isi->skor_entitas !== null)
                <span class="text-xs text-default-600">
                    {{ __('Skor Jawaban Anda') }}: <strong>{{ number_format((float) $isi->skor_entitas, 2) }}</strong>
                </span>
            @endif
            @if ($isi->skor_total !== null)
                <span class="text-xs text-default-600">
                    {{ __('Skor Hasil Verifikasi') }}: <strong>{{ number_format((float) $isi->skor_total, 2) }}</strong>
                </span>
            @endif
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $isi->status->badgeClass() }}">
                {{ $isi->status->label() }} - {{ $isi->komoditas->name }}
            </span>
        </div>
    </div>

    @if ($isi->status->value !== 'draft')
        <div class="alert bg-default-150 text-default-700 mb-4">
            {{ __('Assesment ini sudah tidak dapat diubah.') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert bg-danger/10 text-danger mb-4">
            <ul class="list-disc ps-4">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('assesment-isi.update', $isi) }}" method="POST">
        @csrf
        <div class="flex flex-col gap-y-4">
            @foreach ($pertanyaan as $item)
                @php
                    $isiJawabanItem = $isiJawaban->get($item->id);
                    $jawabanEntitasId = $isiJawabanItem?->jawaban_entitas_id;
                    $koreksi = $isiJawabanItem?->jawabanVerifikator;
                    $adaKoreksi = $koreksi !== null && $koreksi->id !== $jawabanEntitasId;
                @endphp
                <div class="card">
                    <div class="p-4">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs border-primary/30 bg-primary/10 text-primary mb-2">
                            {{ $item->aspek?->nama }}
                        </span>
                        <p class="font-medium text-default-800">{{ $item->pertanyaan }}</p>
                        @if ($item->informasi)
                            <p class="text-sm text-default-500 mt-1">{{ $item->informasi }}</p>
                        @endif

                        {{-- Jawaban entitas: input yang ikut disubmit, TIDAK pernah diganti oleh
                             koreksi verifikator - koreksi (kalau ada) ditampilkan terpisah di
                             bawah, bukan menimpa pilihan di sini. --}}
                        <div class="mt-3 flex flex-col gap-y-2">
                            @foreach ($item->jawaban as $jawaban)
                                <label class="flex items-center gap-2 border border-default-200 rounded-lg px-3 py-2 cursor-pointer">
                                    <input @checked($jawabanEntitasId === $jawaban->id)
                                        @disabled($isi->status->value !== 'draft') class="form-radio"
                                        name="jawaban[{{ $item->id }}]" type="radio" value="{{ $jawaban->id }}">
                                    <span class="text-sm">{{ $jawaban->teks_jawaban }}</span>
                                </label>
                            @endforeach
                        </div>

                        @if ($adaKoreksi)
                            <div class="mt-3 flex items-center gap-2 rounded-lg border border-warning/30 bg-warning/10 px-3 py-2 text-sm text-warning">
                                <i class="size-4 shrink-0" data-lucide="shield-alert"></i>
                                {{ __('Dikoreksi verifikator menjadi') }}: <strong>{{ $koreksi->teks_jawaban }}</strong>
                            </div>
                        @endif

                        @if ($item->buktiDukung->isNotEmpty())
                            <div class="mt-4 flex flex-col gap-y-3">
                                <p class="text-sm font-medium text-default-700">{{ __('Bukti Dukung') }}</p>
                                @foreach ($item->buktiDukung as $bukti)
                                    @php $existing = $buktiTersimpan->get($bukti->id); @endphp
                                    <div class="border border-default-200 rounded-lg p-3" data-bukti-upload
                                         data-bukti-dukung-id="{{ $bukti->id }}"
                                         data-upload-url="{{ route('assesment-isi.bukti.upload', [$isi, $bukti->id]) }}">
                                        <label class="text-xs text-default-600 mb-1 block">{{ $bukti->label }}</label>
                                        <input @disabled($isi->status->value !== 'draft') accept=".pdf,.jpg,.jpeg,.png"
                                               class="form-input" data-bukti-input type="file">
                                        <div class="mt-2 flex items-center gap-2 text-xs" data-bukti-status>
                                            <span class="hidden text-default-500" data-bukti-loading>
                                                <i class="animate-spin size-3.5 inline-block" data-lucide="loader-circle"></i>
                                                {{ __('Mengunggah...') }}
                                            </span>
                                            <a class="text-primary hover:underline {{ $existing ? '' : 'hidden' }}"
                                               data-bukti-link href="{{ $existing?->file_path ? route('assesment-isi.bukti.preview', $existing) : '#' }}"
                                               target="_blank">
                                                <i class="size-3.5 inline-block" data-lucide="file-text"></i>
                                                <span data-bukti-filename>{{ $existing?->original_filename }}</span>
                                            </a>
                                            <span class="hidden text-danger" data-bukti-error></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($isi->status->value === 'draft')
            <div class="mt-4 flex justify-end gap-2">
                <button class="btn bg-default-150" type="submit">{{ __('Simpan Draft') }}</button>
                <button class="btn bg-success text-white" formaction="{{ route('assesment-isi.submit', $isi) }}"
                        type="submit">
                    {{ __('Submit Assesment') }}
                </button>
            </div>
            <p class="text-end text-xs text-default-500 mt-1">
                {{ __('Semua pertanyaan harus dijawab sebelum submit. Bukti dukung boleh menyusul.') }}
            </p>
        @endif
    </form>
@endsection

@section('scripts')
    @vite(['resources/js/pages/assesment-isi-show.js'])
@endsection
