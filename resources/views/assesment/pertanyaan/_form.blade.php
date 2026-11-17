{{--
    Form tambah/edit pertanyaan Assesment (dipakai index.blade.php & edit.blade.php).
    Variabel: $action, $method (POST|PUT), $submitLabel, $pertanyaan (null = tambah), $komoditasOptions.
    Nilai awal: old() bila validasi gagal, jika tidak dari $pertanyaan (relasi sudah di-eager-load controller).
--}}
@php
    $checkedKomoditasIds = array_map('intval', (array) old('komoditas_id', $pertanyaan?->komoditas->pluck('id')->all() ?? []));
    $bobotValues = (array) old('bobot', $pertanyaan?->komoditas->mapWithKeys(fn ($komoditas) => [$komoditas->id => (float) $komoditas->pivot->bobot])->all() ?? []);
    $jawabanRows = array_values((array) old('jawaban', $pertanyaan?->jawaban->map(fn ($jawaban) => [
        'teks' => $jawaban->teks_jawaban,
        'skor' => $jawaban->komoditas->mapWithKeys(fn ($komoditas) => [$komoditas->id => (float) $komoditas->pivot->skor])->all(),
    ])->all() ?? [['teks' => '', 'skor' => []], ['teks' => '', 'skor' => []]]));
    $buktiDukungRows = array_values((array) old('bukti_dukung', $pertanyaan?->buktiDukung->pluck('label')->all() ?? ['']));
@endphp
<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <div class="p-4 flex flex-col gap-y-5">
        <div class="relative">
            <label class="inline-block mb-2 text-sm font-medium" for="pertanyaan-aspek-nama">
                {{ __('Indikator / Aspek Penilaian') }}
            </label>
            <input autocomplete="off" class="form-input" data-aspek-input
                   data-aspek-search-url="{{ route('assesment.aspek.search') }}" id="pertanyaan-aspek-nama"
                   name="aspek_nama" type="text" value="{{ old('aspek_nama', $pertanyaan?->aspek?->nama) }}"/>
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
                      rows="2">{{ old('pertanyaan', $pertanyaan?->pertanyaan) }}</textarea>
            @error('pertanyaan')
            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="pertanyaan-informasi">
                {{ __('Informasi untuk Pertanyaan') }}
            </label>
            <textarea class="form-input" id="pertanyaan-informasi" name="informasi"
                      rows="2">{{ old('informasi', $pertanyaan?->informasi) }}</textarea>
            @error('informasi')
            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="inline-block mb-2 text-sm font-medium" for="pertanyaan-metode-verifikasi">
                {{ __('Metode Verifikasi Penilaian') }}
            </label>
            <textarea class="form-input" id="pertanyaan-metode-verifikasi" name="metode_verifikasi"
                      rows="2">{{ old('metode_verifikasi', $pertanyaan?->metode_verifikasi) }}</textarea>
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
                               @checked(in_array($komoditas->id, $checkedKomoditasIds, true))>
                        <label class="text-sm grow" for="pertanyaan-komoditas-{{ $komoditas->id }}">{{ $komoditas->name }}</label>
                        <input class="form-input form-input-sm w-24 hidden" data-bobot-input
                               data-komoditas-id="{{ $komoditas->id }}" min="0" max="100"
                               name="bobot[{{ $komoditas->id }}]" placeholder="{{ __('Bobot %') }}" step="0.01"
                               type="number" value="{{ $bobotValues[$komoditas->id] ?? '' }}">
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
                @include('assesment.pertanyaan._jawaban-row', ['index' => '__INDEX__', 'row' => ['teks' => '', 'skor' => []]])
            </template>

            <div data-repeatable-rows>
                @foreach ($jawabanRows as $index => $row)
                    @include('assesment.pertanyaan._jawaban-row', ['index' => $index, 'row' => $row])
                @endforeach
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
                @foreach ($buktiDukungRows as $index => $label)
                    <div class="flex items-center gap-2 mb-2" data-repeatable-row>
                        <input class="form-input grow" name="bukti_dukung[{{ $index }}]"
                               placeholder="{{ __('Label Bukti Dukung') }}" type="text" value="{{ $label }}">
                        <button aria-label="{{ __('Hapus') }}" class="btn btn-icon size-8 bg-default-150 text-danger"
                                data-repeatable-remove type="button">
                            <i class="size-4" data-lucide="trash-2"></i>
                        </button>
                    </div>
                @endforeach
            </div>
            @error('bukti_dukung')
            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
        @if ($pertanyaan)
            <a class="btn bg-default-150" href="{{ route('assesment.pertanyaan.index', $pertanyaan->assesment_id) }}">{{ __('Batal') }}</a>
        @endif
        <button class="btn bg-primary text-white" type="submit">{{ $submitLabel }}</button>
    </div>
</form>
