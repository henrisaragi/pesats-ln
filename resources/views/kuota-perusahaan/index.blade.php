@extends('layouts.vertical', ['title' => __('Kuota Perusahaan')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Database Kuota'), 'title' => __('Kuota Perusahaan')])

    <div class="card" data-active-year-page>
        <div class="card-header">
            <h6 class="card-title">
                {{ __('Daftar Kuota Perusahaan') }}
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border border-primary/30 bg-primary/10 text-primary text-xs ms-1">{{ __('Tahun :year', ['year' => $activeYear]) }}</span>
            </h6>
            @can('kuota-perusahaan.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#kuota-perusahaan-create-modal"
                        type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Kuota Perusahaan') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <form action="{{ route('kuota-perusahaan.index') }}"
                  class="md:flex items-center md:space-y-0 space-y-4 gap-3" method="GET">
                <input name="year" type="hidden" value="{{ $activeYear }}"/>
                <select class="form-input form-input-sm" name="komoditas_id" onchange="this.form.submit()">
                    <option value="">{{ __('Semua Komoditas') }}</option>
                    @foreach ($komoditasFilterOptions as $option)
                        <option value="{{ $option->id }}" @selected($activeKomoditasId === $option->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Perusahaan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Komoditas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Spesies') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status Asal') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Satuan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kuota Perusahaan / Nasional') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tahun') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($kuotaPerusahaan as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->entitas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kuotaNasional?->komoditas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kuotaNasional?->species?->scientific_name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kuotaNasional?->source?->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kuotaNasional?->tradeCode?->unit_name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->quotaRatioLabel() ?? number_format((float) $item->quota, 2, ',', '.') }}</td>
                                    <td class="px-3.5 py-3">{{ $item->year }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('kuota-perusahaan.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#kuota-perusahaan-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('kuota-perusahaan.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#kuota-perusahaan-delete-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="8">
                                        {{ __('Belum ada data kuota perusahaan.') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <p class="text-default-500 text-sm">
                    {{ __('Menampilkan') }} <b>{{ $kuotaPerusahaan->count() }}</b> {{ __('dari') }}
                    <b>{{ $kuotaPerusahaan->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $kuotaPerusahaan])
            </div>
        </div>
    </div>

    @can('kuota-perusahaan.create')
        <div aria-labelledby="kuota-perusahaan-create-modal-label" id="kuota-perusahaan-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="kuota-perusahaan-create-modal-label">
                            {{ __('Tambah Kuota Perusahaan') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#kuota-perusahaan-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('kuota-perusahaan.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="kuota-perusahaan-create-modal">
                        {{-- Sengaja tanpa overflow-y-auto/max-h: itu meng-clip dropdown Choices.js, lihat claude/permohonan-task.md. --}}
                        <div class="p-4">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kp-create-entity">{{ __('Perusahaan') }}</label>
                                    <select class="form-input" data-choice id="kp-create-entity" name="entity_id">
                                        <option value="">{{ __('Pilih Perusahaan') }}</option>
                                        @foreach ($entitasOptions as $option)
                                            <option value="{{ $option->id }}"
                                                    @selected(old('_modal') === 'kuota-perusahaan-create-modal' && (int) old('entity_id') === $option->id)>
                                                {{ $option->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'kuota-perusahaan-create-modal' && $errors->has('entity_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('entity_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kp-create-komoditas">{{ __('Komoditas') }}</label>
                                    <x-select depends-on="kp-create-entity" id="kp-create-komoditas"
                                               name="komoditas_id"
                                               :placeholder="__('Pilih Komoditas')"
                                               :remote-url="route('kuota-perusahaan.komoditas-options', ['entity' => '__PARENT__'])"/>
                                    @if (old('_modal') === 'kuota-perusahaan-create-modal' && $errors->has('komoditas_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('komoditas_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kp-create-source">{{ __('Status Asal') }}</label>
                                    <x-select depends-on="kp-create-komoditas" id="kp-create-source"
                                               name="source_id"
                                               :placeholder="__('Pilih Status Asal')"
                                               :remote-url="route('kuota-perusahaan.source-status-options', ['komoditas' => '__PARENT__', 'year' => $activeYear])"/>
                                    <p class="mt-1 text-xs text-default-400">
                                        {{ __('Hanya menampilkan status asal yang tersedia di Kuota Nasional.') }}
                                    </p>
                                    @if (old('_modal') === 'kuota-perusahaan-create-modal' && $errors->has('source_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('source_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kp-create-trade-code">{{ __('Kode Perdagangan') }}</label>
                                    <x-select depends-on="kp-create-source" depends-on2="kp-create-komoditas"
                                               id="kp-create-trade-code" name="trade_code_id"
                                               :placeholder="__('Pilih Kode Perdagangan')"
                                               :remote-url="route('kuota-perusahaan.trade-code-options', ['komoditas' => '__PARENT2__', 'source' => '__PARENT__', 'year' => $activeYear])"/>
                                    @if (old('_modal') === 'kuota-perusahaan-create-modal' && $errors->has('trade_code_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('trade_code_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kp-create-species">{{ __('Spesies') }}</label>
                                    <x-select depends-on="kp-create-trade-code" depends-on2="kp-create-source"
                                               depends-on3="kp-create-komoditas"
                                               id="kp-create-species" name="species_id"
                                               :placeholder="__('Pilih Spesies')"
                                               :remote-url="route('kuota-perusahaan.species-options', ['komoditas' => '__PARENT3__', 'source' => '__PARENT2__', 'tradeCode' => '__PARENT__', 'year' => $activeYear])"/>
                                    <p class="mt-1 text-xs text-default-400">
                                        {{ __('Hanya menampilkan spesies yang tersedia di Kuota Nasional.') }}
                                    </p>
                                    @if (old('_modal') === 'kuota-perusahaan-create-modal' && $errors->has('species_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('species_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kp-create-year">{{ __('Tahun') }}</label>
                                    <input class="form-input bg-default-100" id="kp-create-year" name="year"
                                           readonly type="text" value="{{ $activeYear }}"/>
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kp-create-quota">{{ __('Kuota Perusahaan') }}</label>
                                    <input class="form-input" id="kp-create-quota" name="quota" step="0.01"
                                           type="number"
                                           value="{{ old('_modal') === 'kuota-perusahaan-create-modal' ? old('quota') : '' }}"/>
                                    @if (old('_modal') === 'kuota-perusahaan-create-modal' && $errors->has('quota'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('quota') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#kuota-perusahaan-create-modal"
                                    type="button">{{ __('Batal') }}</button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('kuota-perusahaan.update')
        @foreach ($kuotaPerusahaan as $item)
            <div aria-labelledby="kuota-perusahaan-edit-modal-{{ $item->id }}-label"
                 id="kuota-perusahaan-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base"
                                id="kuota-perusahaan-edit-modal-{{ $item->id }}-label">{{ __('Ubah Kuota Perusahaan') }}</h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#kuota-perusahaan-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('kuota-perusahaan.update', $item) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="kuota-perusahaan-edit-modal-{{ $item->id }}">
                            {{-- Sengaja tanpa overflow-y-auto/max-h: itu meng-clip dropdown Choices.js, lihat claude/permohonan-task.md. --}}
                            <div class="p-4">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kp-edit-entity-{{ $item->id }}">{{ __('Perusahaan') }}</label>
                                        <select class="form-input" data-choice id="kp-edit-entity-{{ $item->id }}"
                                                name="entity_id">
                                            <option value="">{{ __('Pilih Perusahaan') }}</option>
                                            @foreach ($entitasOptions as $option)
                                                <option value="{{ $option->id }}"
                                                        @selected(old('_modal') === 'kuota-perusahaan-edit-modal-'.$item->id ? (int) old('entity_id') === $option->id : $item->entity_id === $option->id)>
                                                    {{ $option->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kp-edit-komoditas-{{ $item->id }}">{{ __('Komoditas') }}</label>
                                        <x-select depends-on="kp-edit-entity-{{ $item->id }}"
                                                   id="kp-edit-komoditas-{{ $item->id }}" name="komoditas_id"
                                                   :options="$item->kuotaNasional?->komoditas ? collect([$item->kuotaNasional->komoditas]) : collect()"
                                                   :selected="$item->kuotaNasional?->komoditas_id"
                                                   :placeholder="__('Pilih Komoditas')"
                                                   :remote-url="route('kuota-perusahaan.komoditas-options', ['entity' => '__PARENT__'])"/>
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kp-edit-source-{{ $item->id }}">{{ __('Status Asal') }}</label>
                                        <x-select depends-on="kp-edit-komoditas-{{ $item->id }}"
                                                   id="kp-edit-source-{{ $item->id }}" name="source_id"
                                                   :options="$item->kuotaNasional?->source ? collect([(object) ['id' => $item->kuotaNasional->source->id, 'name' => $item->kuotaNasional->source->code.' - '.$item->kuotaNasional->source->description]]) : collect()"
                                                   :selected="$item->kuotaNasional?->source_id"
                                                   :placeholder="__('Pilih Status Asal')"
                                                   :remote-url="route('kuota-perusahaan.source-status-options', ['komoditas' => '__PARENT__', 'year' => $activeYear])"/>
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kp-edit-trade-code-{{ $item->id }}">{{ __('Kode Perdagangan') }}</label>
                                        <x-select depends-on="kp-edit-source-{{ $item->id }}"
                                                   depends-on2="kp-edit-komoditas-{{ $item->id }}"
                                                   id="kp-edit-trade-code-{{ $item->id }}" name="trade_code_id"
                                                   :options="$item->kuotaNasional?->tradeCode ? collect([(object) ['id' => $item->kuotaNasional->tradeCode->id, 'name' => $item->kuotaNasional->tradeCode->code.' - '.$item->kuotaNasional->tradeCode->name]]) : collect()"
                                                   :selected="$item->kuotaNasional?->trade_code_id"
                                                   :placeholder="__('Pilih Kode Perdagangan')"
                                                   :remote-url="route('kuota-perusahaan.trade-code-options', ['komoditas' => '__PARENT2__', 'source' => '__PARENT__', 'year' => $activeYear])"/>
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kp-edit-species-{{ $item->id }}">{{ __('Spesies') }}</label>
                                        <x-select depends-on="kp-edit-trade-code-{{ $item->id }}"
                                                   depends-on2="kp-edit-source-{{ $item->id }}"
                                                   depends-on3="kp-edit-komoditas-{{ $item->id }}"
                                                   id="kp-edit-species-{{ $item->id }}" name="species_id"
                                                   :options="$item->kuotaNasional?->species ? collect([(object) ['id' => $item->kuotaNasional->species->id, 'name' => $item->kuotaNasional->species->scientific_name]]) : collect()"
                                                   :selected="$item->kuotaNasional?->species_id"
                                                   :placeholder="__('Pilih Spesies')"
                                                   :remote-url="route('kuota-perusahaan.species-options', ['komoditas' => '__PARENT3__', 'source' => '__PARENT2__', 'tradeCode' => '__PARENT__', 'year' => $activeYear])"/>
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kp-edit-year-{{ $item->id }}">{{ __('Tahun') }}</label>
                                        <input class="form-input bg-default-100" id="kp-edit-year-{{ $item->id }}"
                                               name="year" readonly type="text" value="{{ $activeYear }}"/>
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kp-edit-quota-{{ $item->id }}">{{ __('Kuota Perusahaan') }}</label>
                                        <input class="form-input" id="kp-edit-quota-{{ $item->id }}" name="quota"
                                               step="0.01" type="number"
                                               value="{{ old('_modal') === 'kuota-perusahaan-edit-modal-'.$item->id ? old('quota') : $item->quota }}"/>
                                        @if (old('_modal') === 'kuota-perusahaan-edit-modal-'.$item->id && $errors->has('quota'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('quota') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150"
                                        data-hs-overlay="#kuota-perusahaan-edit-modal-{{ $item->id }}"
                                        type="button">{{ __('Batal') }}</button>
                                <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endcan

    @can('kuota-perusahaan.delete')
        @foreach ($kuotaPerusahaan as $item)
            <div aria-labelledby="kuota-perusahaan-delete-modal-{{ $item->id }}-label"
                 id="kuota-perusahaan-delete-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#kuota-perusahaan-delete-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Kuota perusahaan untuk ":entitas" akan dihapus permanen.', ['entitas' => $item->entitas?->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#kuota-perusahaan-delete-modal-{{ $item->id }}"
                                            type="button">{{ __('Batal') }}</button>
                                    <form action="{{ route('kuota-perusahaan.destroy', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="bg-danger text-white btn border-0 btn-sm" type="submit">
                                            {{ __('Ya, Hapus!') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endcan
@endsection
