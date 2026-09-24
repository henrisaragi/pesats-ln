@extends('layouts.vertical', ['title' => __('Pertanyaan Assesment')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Master Data'), 'title' => $assesment->judul])

    <div class="mb-4">
        <a class="text-primary hover:underline inline-flex items-center gap-1" href="{{ route('assesment.index') }}">
            <i class="size-4" data-lucide="arrow-left"></i>{{ __('Kembali ke Daftar Assesment') }}
        </a>
    </div>

    @can('assesment.create')
        <div class="card mb-6">
            <div class="card-header">
                <h6 class="card-title">{{ __('Tambah Pertanyaan') }}</h6>
            </div>
            <form action="{{ route('assesment.pertanyaan.store', $assesment) }}" method="POST">
                @csrf
                <div class="p-4 flex flex-col gap-y-5">
                    <div class="relative">
                        <label class="inline-block mb-2 text-sm font-medium" for="pertanyaan-aspek-nama">
                            {{ __('Indikator / Aspek Penilaian') }}
                        </label>
                        <input autocomplete="off" class="form-input" data-aspek-input
                               data-aspek-search-url="{{ route('assesment.aspek.search') }}" id="pertanyaan-aspek-nama"
                               name="aspek_nama" type="text" value="{{ old('aspek_nama') }}"/>
                        <div class="hidden absolute z-10 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-default-200 bg-white shadow-lg"
                             data-aspek-suggestions></div>
                        <p class="mt-1 text-xs text-default-500">
                            {{ __('Ketik minimal 3 huruf untuk mencari indikator yang sudah ada, atau ketik nama baru.') }}
                        </p>
                        @error('aspek_nama')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="pertanyaan-pertanyaan">
                            {{ __('Pertanyaan') }}
                        </label>
                        <textarea class="form-input" id="pertanyaan-pertanyaan" name="pertanyaan"
                                  rows="2">{{ old('pertanyaan') }}</textarea>
                        @error('pertanyaan')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="pertanyaan-informasi">
                            {{ __('Informasi untuk Pertanyaan') }}
                        </label>
                        <textarea class="form-input" id="pertanyaan-informasi" name="informasi"
                                  rows="2">{{ old('informasi') }}</textarea>
                        @error('informasi')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="pertanyaan-metode-verifikasi">
                            {{ __('Metode Verifikasi Penilaian') }}
                        </label>
                        <textarea class="form-input" id="pertanyaan-metode-verifikasi" name="metode_verifikasi"
                                  rows="2">{{ old('metode_verifikasi') }}</textarea>
                        <p class="mt-1 text-xs text-default-500">{{ __('Panduan bagi verifikator saat memberi nilai.') }}</p>
                        @error('metode_verifikasi')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">{{ __('Komoditas & Bobot Pertanyaan (%)') }}</label>
                        <div class="grid sm:grid-cols-2 gap-3">
                            @foreach ($komoditasOptions as $komoditas)
                                <div class="flex items-center gap-2 border border-default-200 rounded-lg px-3 py-2">
                                    <input class="form-checkbox" data-komoditas-checkbox id="pertanyaan-komoditas-{{ $komoditas->id }}"
                                           name="komoditas_id[]" type="checkbox" value="{{ $komoditas->id }}"
                                           @checked(is_array(old('komoditas_id')) && in_array($komoditas->id, old('komoditas_id')))>
                                    <label class="text-sm grow" for="pertanyaan-komoditas-{{ $komoditas->id }}">{{ $komoditas->name }}</label>
                                    <input class="form-input form-input-sm w-24 hidden" data-bobot-input
                                           data-komoditas-id="{{ $komoditas->id }}" min="0" max="100"
                                           name="bobot[{{ $komoditas->id }}]" placeholder="{{ __('Bobot %') }}" step="0.01"
                                           type="number" value="{{ old('bobot.'.$komoditas->id) }}">
                                </div>
                            @endforeach
                        </div>
                        @error('bobot')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                        @error('komoditas_id')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div data-repeatable id="pertanyaan-jawaban-repeatable">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium">{{ __('Jawaban & Skor per Komoditas') }}</label>
                            <button class="btn btn-sm bg-default-150" data-repeatable-add type="button">
                                <i class="size-3.5 me-1" data-lucide="plus"></i>{{ __('Tambah Jawaban') }}
                            </button>
                        </div>

                        <template data-repeatable-template>
                            <div class="border border-default-200 rounded-lg p-3 mb-3" data-repeatable-row data-jawaban-row>
                                <div class="flex items-center gap-2 mb-2">
                                    <input class="form-input grow" name="jawaban[__INDEX__][teks]"
                                           placeholder="{{ __('Teks Jawaban') }}" type="text">
                                    <button aria-label="{{ __('Hapus') }}" class="btn btn-icon size-8 bg-default-150 text-danger"
                                            data-repeatable-remove type="button">
                                        <i class="size-4" data-lucide="trash-2"></i>
                                    </button>
                                </div>
                                <label class="inline-flex items-center gap-1.5 text-xs text-default-600 mb-2">
                                    <input class="form-checkbox" data-skor-sama-toggle type="checkbox">
                                    {{ __('Skor sama untuk semua komoditas tercentang') }}
                                </label>
                                <div class="grid sm:grid-cols-2 gap-2" data-skor-fields>
                                    @foreach ($komoditasOptions as $komoditas)
                                        <div class="flex items-center gap-2 hidden" data-komoditas-id="{{ $komoditas->id }}" data-skor-field>
                                            <span class="text-xs text-default-500 w-28 truncate">{{ $komoditas->name }}</span>
                                            <input class="form-input form-input-sm" data-komoditas-id="{{ $komoditas->id }}"
                                                   data-skor-input name="jawaban[__INDEX__][skor][{{ $komoditas->id }}]"
                                                   placeholder="{{ __('Skor') }}" step="0.01" type="number">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </template>

                        <div data-repeatable-rows>
                            @for ($i = 0; $i < 2; $i++)
                                <div class="border border-default-200 rounded-lg p-3 mb-3" data-repeatable-row data-jawaban-row>
                                    <div class="flex items-center gap-2 mb-2">
                                        <input class="form-input grow" name="jawaban[{{ $i }}][teks]"
                                               placeholder="{{ __('Teks Jawaban') }}" type="text"
                                               value="{{ old('jawaban.'.$i.'.teks') }}">
                                        <button aria-label="{{ __('Hapus') }}"
                                                class="btn btn-icon size-8 bg-default-150 text-danger" data-repeatable-remove
                                                type="button">
                                            <i class="size-4" data-lucide="trash-2"></i>
                                        </button>
                                    </div>
                                    <label class="inline-flex items-center gap-1.5 text-xs text-default-600 mb-2">
                                        <input class="form-checkbox" data-skor-sama-toggle type="checkbox">
                                        {{ __('Skor sama untuk semua komoditas tercentang') }}
                                    </label>
                                    <div class="grid sm:grid-cols-2 gap-2" data-skor-fields>
                                        @foreach ($komoditasOptions as $komoditas)
                                            <div class="flex items-center gap-2 hidden" data-komoditas-id="{{ $komoditas->id }}"
                                                 data-skor-field>
                                                <span class="text-xs text-default-500 w-28 truncate">{{ $komoditas->name }}</span>
                                                <input class="form-input form-input-sm" data-komoditas-id="{{ $komoditas->id }}"
                                                       data-skor-input name="jawaban[{{ $i }}][skor][{{ $komoditas->id }}]"
                                                       placeholder="{{ __('Skor') }}" step="0.01" type="number"
                                                       value="{{ old('jawaban.'.$i.'.skor.'.$komoditas->id) }}">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endfor
                        </div>
                        @error('jawaban')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div data-repeatable id="pertanyaan-bukti-repeatable">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium">{{ __('Informasi Bukti Pendukung') }}</label>
                            <button class="btn btn-sm bg-default-150" data-repeatable-add type="button">
                                <i class="size-3.5 me-1" data-lucide="plus"></i>{{ __('Tambah Bukti Dukung') }}
                            </button>
                        </div>

                        <template data-repeatable-template>
                            <div class="flex items-center gap-2 mb-2" data-repeatable-row>
                                <input class="form-input grow" name="bukti_dukung[__INDEX__]"
                                       placeholder="{{ __('Label Bukti Dukung') }}" type="text">
                                <button aria-label="{{ __('Hapus') }}" class="btn btn-icon size-8 bg-default-150 text-danger"
                                        data-repeatable-remove type="button">
                                    <i class="size-4" data-lucide="trash-2"></i>
                                </button>
                            </div>
                        </template>

                        <div data-repeatable-rows>
                            <div class="flex items-center gap-2 mb-2" data-repeatable-row>
                                <input class="form-input grow" name="bukti_dukung[0]"
                                       placeholder="{{ __('Label Bukti Dukung') }}" type="text"
                                       value="{{ old('bukti_dukung.0') }}">
                                <button aria-label="{{ __('Hapus') }}" class="btn btn-icon size-8 bg-default-150 text-danger"
                                        data-repeatable-remove type="button">
                                    <i class="size-4" data-lucide="trash-2"></i>
                                </button>
                            </div>
                        </div>
                        @error('bukti_dukung')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="p-4 flex justify-end border-t border-dashed border-default-900/10">
                    <button class="btn bg-primary text-white" type="submit">{{ __('Simpan Pertanyaan') }}</button>
                </div>
            </form>
        </div>
    @endcan

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Pertanyaan') }}</h6>
        </div>
        <div class="p-4 flex flex-col gap-y-4">
            @forelse ($assesment->pertanyaan as $item)
                <div class="border border-default-200 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs border-primary/30 bg-primary/10 text-primary mb-2">
                                {{ $item->aspek?->nama }}
                            </span>
                            <p class="font-medium text-default-800">{{ $item->pertanyaan }}</p>
                            @if ($item->informasi)
                                <p class="text-sm text-default-500 mt-1">{{ $item->informasi }}</p>
                            @endif
                        </div>
                        @can('assesment.delete')
                            <button aria-label="{{ __('Hapus') }}"
                                    class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500 shrink-0"
                                    data-hs-overlay="#pertanyaan-delete-modal-{{ $item->id }}" type="button">
                                <i class="size-4" data-lucide="trash-2"></i>
                            </button>
                        @endcan
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($item->komoditas as $komoditas)
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs border-default-300 bg-default-150 text-default-600">
                                {{ $komoditas->name }} - {{ __('Bobot') }} {{ number_format((float) $komoditas->pivot->bobot, 2) }}%
                            </span>
                        @endforeach
                    </div>

                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-default-200 text-sm">
                            <thead>
                            <tr class="text-default-600 text-xs">
                                <th class="px-2 py-1 text-start">{{ __('Jawaban') }}</th>
                                @foreach ($item->komoditas as $komoditas)
                                    <th class="px-2 py-1 text-start">{{ $komoditas->name }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($item->jawaban as $jawaban)
                                <tr class="text-default-800">
                                    <td class="px-2 py-1">{{ $jawaban->teks_jawaban }}</td>
                                    @foreach ($item->komoditas as $komoditas)
                                        <td class="px-2 py-1">{{ $jawaban->skorUntukKomoditas($komoditas->id) ?? '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($item->buktiDukung->isNotEmpty())
                        <div class="mt-3 text-sm text-default-600">
                            <span class="font-medium">{{ __('Bukti Dukung') }}:</span>
                            {{ $item->buktiDukung->pluck('label')->implode(', ') }}
                        </div>
                    @endif
                </div>

                @can('assesment.delete')
                    <div aria-labelledby="pertanyaan-delete-modal-{{ $item->id }}-label" id="pertanyaan-delete-modal-{{ $item->id }}"
                         class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                         role="dialog" tabindex="-1">
                        <div
                            class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                            <div
                                class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                                <div class="absolute top-3 end-3">
                                    <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                            data-hs-overlay="#pertanyaan-delete-modal-{{ $item->id }}" type="button">
                                        <span class="sr-only">{{ __('Tutup') }}</span>
                                        <i class="size-5" data-lucide="x"></i>
                                    </button>
                                </div>
                                <div class="text-center">
                                    <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                                    <div class="mt-5 text-center">
                                        <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                        <p class="text-default-500 text-sm font-normal">
                                            {{ __('Pertanyaan ini beserta jawaban dan bukti dukungnya akan dihapus permanen.') }}
                                        </p>
                                        <div class="mt-5 flex gap-2 justify-center">
                                            <button class="btn btn-sm bg-default-150"
                                                    data-hs-overlay="#pertanyaan-delete-modal-{{ $item->id }}" type="button">
                                                {{ __('Batal') }}
                                            </button>
                                            <form action="{{ route('assesment.pertanyaan.destroy', [$assesment, $item]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm bg-danger text-white border-0" type="submit">
                                                    {{ __('Ya, Hapus!') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan
            @empty
                <p class="text-center text-default-500 py-6">{{ __('Belum ada pertanyaan untuk assesment ini.') }}</p>
            @endforelse
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/assesment-pertanyaan-form.js'])
@endsection
