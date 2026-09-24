@extends('layouts.vertical', ['title' => __('Verifikasi').' - '.$isi->assesment->judul])

@section('css')

@endsection

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <a class="text-primary hover:underline inline-flex items-center gap-1" href="{{ route('assesment-isi.verifikasi.index') }}">
            <i class="size-4" data-lucide="arrow-left"></i>{{ __('Kembali') }}
        </a>
        <div class="text-end">
            <p class="font-semibold text-default-800">{{ $isi->entitas->name }}</p>
            <p class="text-sm text-default-500">{{ $isi->assesment->judul }} - {{ $isi->komoditas->name }}</p>
        </div>
    </div>

    <form action="{{ route('assesment-isi.verifikasi.complete', $isi) }}" method="POST">
        @csrf
        <div class="grid lg:grid-cols-2 gap-4">
            <div class="card lg:sticky lg:top-20 lg:self-start">
                <div class="card-header">
                    <h6 class="card-title">{{ __('Bukti Dukung') }}</h6>
                    @if ($isi->bukti->count() > 1)
                        <select class="form-input form-input-sm w-56" data-bukti-switcher>
                            @foreach ($isi->bukti as $bukti)
                                <option data-preview-url="{{ route('assesment-isi.bukti.preview', $bukti) }}" value="{{ $bukti->id }}">
                                    {{ $bukti->buktiDukung?->label }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="bg-default-200" style="height: 70vh;">
                    @if ($isi->bukti->isNotEmpty())
                        <iframe class="size-full border-0" data-bukti-frame
                                src="{{ route('assesment-isi.bukti.preview', $isi->bukti->first()) }}"
                                title="{{ __('Pratinjau Bukti Dukung') }}"></iframe>
                    @else
                        <div class="flex items-center justify-center size-full text-default-500 text-sm">
                            {{ __('Tidak ada bukti dukung diunggah.') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-y-4">
                @foreach ($pertanyaan as $item)
                    @php
                        $isiJawabanItem = $isiJawaban->get($item->id);
                        $jawabanEntitas = $item->jawaban->firstWhere('id', $isiJawabanItem?->jawaban_entitas_id);
                        $jawabanVerifikatorId = $isiJawabanItem?->jawaban_verifikator_id ?? $isiJawabanItem?->jawaban_entitas_id;
                        $bobot = $item->komoditas->firstWhere('id', $isi->komoditas_id)?->pivot->bobot;
                        $sesuai = $isiJawabanItem === null || $isiJawabanItem->jawaban_verifikator_id === null || $isiJawabanItem->jawaban_verifikator_id === $isiJawabanItem->jawaban_entitas_id;
                    @endphp
                    <div class="card" data-pertanyaan-koreksi>
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs border-primary/30 bg-primary/10 text-primary">
                                    {{ $item->aspek?->nama }}
                                </span>
                                <span class="text-xs text-default-500">{{ __('Bobot') }}: {{ number_format((float) $bobot, 2) }}%</span>
                            </div>
                            <p class="font-medium text-default-800 mb-3">{{ $item->pertanyaan }}</p>

                            <div class="bg-default-150 rounded-lg p-3 mb-3">
                                <p class="text-xs text-default-500 mb-1">{{ __('Jawaban Entitas') }}</p>
                                <p class="text-sm font-medium">{{ $jawabanEntitas?->teks_jawaban ?? '-' }}</p>
                                <p class="text-xs text-default-500 mt-1" data-skor-entitas>
                                    {{ __('Skor') }}: {{ $jawabanEntitas?->skorUntukKomoditas($isi->komoditas_id) ?? '-' }}
                                </p>
                            </div>

                            <label class="inline-flex items-center gap-1.5 text-sm text-default-700 mb-2">
                                <input @checked($sesuai) class="form-checkbox" data-sesuai-toggle type="checkbox">
                                {{ __('Jawaban sesuai') }}
                            </label>

                            <select class="form-input" data-jawaban-entitas-id="{{ $jawabanEntitas?->id }}"
                                    data-jawaban-verifikator-select name="jawaban[{{ $item->id }}]">
                                @foreach ($item->jawaban as $jawabanOption)
                                    <option @selected($jawabanVerifikatorId === $jawabanOption->id)
                                        data-skor="{{ $jawabanOption->skorUntukKomoditas($isi->komoditas_id) }}"
                                        value="{{ $jawabanOption->id }}">
                                        {{ $jawabanOption->teks_jawaban }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-default-500 mt-1">
                                {{ __('Skor (koreksi verifikator)') }}: <span data-skor-verifikator></span>
                            </p>
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-end">
                    <button class="btn bg-success text-white" type="submit">{{ __('Selesaikan Verifikasi') }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    @vite(['resources/js/pages/assesment-isi-verifikasi-show.js'])
@endsection
