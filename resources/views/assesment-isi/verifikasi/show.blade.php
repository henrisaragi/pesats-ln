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

    @php
        $buktiTersimpan = $isi->bukti->keyBy('bukti_dukung_id');
        // Hanya status Submitted yang bisa diverifikasi; selain itu wizard tampil baca-saja.
        $bisaVerifikasi = $isi->status === \App\Enums\AssesmentIsiStatus::Submitted;
        // Mulai dari pertanyaan pertama yang belum disimpan verifikator (lanjutkan progres sebelumnya).
        $langkahAwal = $pertanyaan->search(fn ($item) => $isiJawaban->get($item->id)?->jawaban_verifikator_id === null);
        $langkahAwal = ! $bisaVerifikasi ? 0 : ($langkahAwal === false ? max($pertanyaan->count() - 1, 0) : $langkahAwal);
    @endphp

    <form action="{{ route('assesment-isi.verifikasi.complete', $isi) }}" data-verifikasi-wizard data-langkah-awal="{{ $langkahAwal }}" data-readonly="{{ $bisaVerifikasi ? '0' : '1' }}"
          data-pesan-pilih="{{ __('Pilih "Jawaban sesuai" atau "Jawaban tidak sesuai" terlebih dahulu.') }}"
          data-pesan-beda="{{ __('Pilih jawaban koreksi yang berbeda dari jawaban entitas.') }}"
          data-pesan-gagal="{{ __('Gagal menyimpan jawaban.') }}" method="POST">
        @csrf

        @unless ($bisaVerifikasi)
            <div class="mb-4 rounded-lg border border-info/30 bg-info/10 px-4 py-3 text-sm text-info">
                {{ __('Assesment ini berstatus :status, jawaban hanya dapat dilihat.', ['status' => $isi->status->label()]) }}
            </div>
        @endunless

        @error('jawaban')
            <div class="mb-4 rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ $message }}</div>
        @enderror

        <div class="card mb-4">
            <div class="p-3 overflow-x-auto">
                <ol class="flex min-w-max">
                    @foreach ($pertanyaan as $index => $item)
                        <li class="-ms-2 first:ms-0">
                            <button class="flex items-center gap-2 h-10 {{ $loop->first ? 'ps-4' : 'ps-6' }} pe-7 text-sm font-medium bg-default-200 text-default-600 disabled:cursor-not-allowed"
                                    data-langkah-nav="{{ $index }}"
                                    data-tersimpan="{{ $isiJawaban->get($item->id)?->jawaban_verifikator_id !== null ? '1' : '0' }}"
                                    style="clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 50%, calc(100% - 12px) 100%, 0 100%, {{ $loop->first ? '0' : '12px' }} 50%);"
                                    title="{{ \Illuminate\Support\Str::limit($item->pertanyaan, 80) }}"
                                    type="button">
                                <span class="inline-flex items-center justify-center size-6 rounded-full bg-white/70 text-xs" data-langkah-nomor>{{ $index + 1 }}</span>
                                <i class="size-4 hidden" data-langkah-check data-lucide="check"></i>
                            </button>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-4">
            <div class="card lg:sticky lg:top-20 lg:self-start">
                <div class="card-header">
                    <h6 class="card-title">{{ __('Pratinjau Bukti Dukung') }}</h6>
                    <span class="text-xs text-default-500" data-bukti-judul></span>
                </div>
                <div class="bg-default-200" style="height: 70vh;">
                    <iframe class="size-full border-0 hidden" data-bukti-frame title="{{ __('Pratinjau Bukti Dukung') }}"></iframe>
                    <div class="flex items-center justify-center size-full text-default-500 text-sm" data-bukti-kosong>
                        {{ __('Pilih tombol Lihat pada bukti dukung untuk menampilkan pratinjau.') }}
                    </div>
                </div>
            </div>

            <div>
                @foreach ($pertanyaan as $index => $item)
                    @php
                        $isiJawabanItem = $isiJawaban->get($item->id);
                        $jawabanEntitas = $item->jawaban->firstWhere('id', $isiJawabanItem?->jawaban_entitas_id);
                        $jawabanVerifikatorId = $isiJawabanItem?->jawaban_verifikator_id ?? $isiJawabanItem?->jawaban_entitas_id;
                        $bobot = $item->komoditas->firstWhere('id', $isi->komoditas_id)?->pivot->bobot;
                        // null = belum dipilih (langkah belum pernah disimpan verifikator).
                        $kesesuaian = $isiJawabanItem?->jawaban_verifikator_id === null ? null : ($isiJawabanItem->isSesuai() ? 'sesuai' : 'tidak_sesuai');
                    @endphp
                    <div class="card hidden" data-langkah="{{ $index }}" data-pertanyaan-koreksi
                         data-simpan-url="{{ route('assesment-isi.verifikasi.jawaban', [$isi, $item]) }}">
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs border-primary/30 bg-primary/10 text-primary">
                                    {{ $item->aspek?->nama }}
                                </span>
                                <span class="text-xs text-default-500">
                                    {{ __('Pertanyaan :nomor dari :total', ['nomor' => $index + 1, 'total' => $pertanyaan->count()]) }}
                                    &middot; {{ __('Bobot') }}: {{ number_format((float) $bobot, 2) }}%
                                </span>
                            </div>
                            <p class="font-medium text-default-800 mb-3">{{ $item->pertanyaan }}</p>

                            <div class="bg-default-150 rounded-lg p-3 mb-3">
                                <p class="text-xs text-default-500 mb-1">{{ __('Jawaban Entitas') }}</p>
                                <p class="text-sm font-medium">{{ $jawabanEntitas?->teks_jawaban ?? '-' }}</p>
                                <p class="text-xs text-default-500 mt-1" data-skor-entitas>
                                    {{ __('Skor') }}: {{ $jawabanEntitas?->skorUntukKomoditas($isi->komoditas_id) ?? '-' }}
                                </p>
                            </div>

                            <div class="rounded-lg border border-warning/30 bg-warning/10 p-3 mb-3">
                                <p class="flex items-center gap-1 text-xs font-medium text-warning mb-2">
                                    <i class="size-3.5" data-lucide="paperclip"></i>{{ __('Bukti Dukung') }}
                                </p>
                                @forelse ($item->buktiDukung as $buktiDukung)
                                    @php $bukti = $buktiTersimpan->get($buktiDukung->id); @endphp
                                    <div class="flex items-center justify-between gap-2 bg-card border border-warning/20 rounded-lg px-3 py-2 mb-2 last:mb-0">
                                        <span class="text-sm text-default-700">{{ $buktiDukung->label }}</span>
                                        @if ($bukti?->file_path)
                                            <button class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white"
                                                    data-bukti-label="{{ $buktiDukung->label }}"
                                                    data-bukti-view="{{ route('assesment-isi.bukti.preview', $bukti) }}" type="button">
                                                <i class="size-4" data-lucide="eye"></i>{{ __('Lihat') }}
                                            </button>
                                        @else
                                            <span class="text-xs text-default-400">{{ __('Belum diunggah') }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-default-500">{{ __('Tidak ada bukti dukung untuk pertanyaan ini.') }}</p>
                                @endforelse
                            </div>

                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mb-2">
                                <label class="inline-flex items-center gap-1.5 text-sm text-default-700">
                                    <input @checked($kesesuaian === 'sesuai') @disabled(! $bisaVerifikasi) class="form-checkbox" data-kesesuaian="{{ $item->id }}"
                                           type="checkbox" value="sesuai">
                                    {{ __('Jawaban sesuai') }}
                                </label>
                                <label class="inline-flex items-center gap-1.5 text-sm text-default-700">
                                    <input @checked($kesesuaian === 'tidak_sesuai') @disabled(! $bisaVerifikasi) class="form-checkbox" data-kesesuaian="{{ $item->id }}"
                                           type="checkbox" value="tidak_sesuai">
                                    {{ __('Jawaban tidak sesuai') }}
                                </label>
                            </div>

                            {{-- Dropdown selalu menampilkan jawaban terpilih; hanya aktif saat "Jawaban tidak sesuai" dicentang. --}}
                            <div>
                                <label class="text-xs text-default-500 mb-1 block">{{ __('Jawaban terpilih') }}</label>
                                <select @disabled(! $bisaVerifikasi || $kesesuaian !== 'tidak_sesuai') class="form-input" data-jawaban-entitas-id="{{ $jawabanEntitas?->id }}"
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
                            <p class="hidden text-sm text-danger mt-2" data-simpan-error></p>
                        </div>
                        <div class="card-footer flex items-center justify-between gap-2">
                            <button @disabled($loop->first) class="btn border-default-300 text-default-700 disabled:opacity-50" data-langkah-back type="button">
                                <i class="size-4" data-lucide="arrow-left"></i>{{ __('Sebelumnya') }}
                            </button>
                            @if ($bisaVerifikasi)
                                <button class="btn {{ $loop->last ? 'bg-success' : 'bg-primary' }} text-white disabled:opacity-60" data-langkah-next type="button">
                                    <span class="hidden size-4 animate-spin rounded-full border-2 border-white border-t-transparent" data-simpan-loading></span>
                                    @if ($loop->last)
                                        {{ __('Simpan & Selesaikan Verifikasi') }}
                                    @else
                                        {{ __('Simpan & Lanjut') }}<i class="size-4" data-lucide="arrow-right"></i>
                                    @endif
                                </button>
                            @elseif (! $loop->last)
                                <button class="btn bg-primary text-white" data-langkah-next type="button">
                                    {{ __('Selanjutnya') }}<i class="size-4" data-lucide="arrow-right"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    @vite(['resources/js/pages/assesment-isi-verifikasi-show.js'])
@endsection
