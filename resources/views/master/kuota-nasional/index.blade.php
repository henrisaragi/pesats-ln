@extends('layouts.vertical', ['title' => __('Kuota Nasional')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Database Kuota'), 'title' => __('Kuota Nasional')])

    <div class="card" data-active-year-page>
        <div class="card-header">
            <h6 class="card-title">
                {{ __('Daftar Kuota Nasional') }}
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border border-primary/30 bg-primary/10 text-primary text-xs ms-1">{{ __('Tahun :year', ['year' => $activeYear]) }}</span>
            </h6>
            @can('kuota-nasional.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#kuota-nasional-create-modal"
                        type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Kuota Nasional') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <form action="{{ route('master.kuota-nasional.index') }}"
                  class="md:flex items-center md:space-y-0 space-y-4 gap-3" method="GET">
                <input name="year" type="hidden" value="{{ $activeYear }}"/>
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search"
                           placeholder="{{ __('Cari komoditas atau spesies') }}" type="text"
                           value="{{ request('search') }}"/>
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3">
                        <i class="size-3.5 flex items-center text-default-500 fill-default-100"
                           data-lucide="search"></i>
                    </div>
                </div>
            </form>
        </div>
        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Komoditas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Spesies') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status Asal') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kode Perdagangan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Satuan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kuota') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tahun') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($kuotaNasional as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->komoditas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->species?->scientific_name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->source?->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->tradeCode?->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->tradeCode?->unit_name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->tradeCode?->formatQuota($item->quota) ?? number_format((float) $item->quota, 2, ',', '.') }}</td>
                                    <td class="px-3.5 py-3">{{ $item->year }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('kuota-nasional.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#kuota-nasional-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('kuota-nasional.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#kuota-nasional-delete-modal-{{ $item->id }}"
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
                                        {{ __('Belum ada data kuota nasional.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $kuotaNasional->count() }}</b> {{ __('dari') }}
                    <b>{{ $kuotaNasional->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $kuotaNasional])
            </div>
        </div>
    </div>

    @can('kuota-nasional.create')
        <div aria-labelledby="kuota-nasional-create-modal-label" id="kuota-nasional-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="kuota-nasional-create-modal-label">
                            {{ __('Tambah Kuota Nasional') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#kuota-nasional-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.kuota-nasional.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="kuota-nasional-create-modal">
                        {{-- Sengaja tanpa overflow-y-auto/max-h: itu meng-clip dropdown Choices.js, lihat claude/permohonan-task.md. --}}
                        <div class="p-4">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kuota-nasional-create-komoditas">
                                        {{ __('Komoditas') }}
                                    </label>
                                    <select class="form-input" data-choice id="kuota-nasional-create-komoditas"
                                            name="komoditas_id">
                                        <option value="">{{ __('Pilih Komoditas') }}</option>
                                        @foreach ($komoditasOptions as $option)
                                            <option value="{{ $option->id }}"
                                                    @selected(old('_modal') === 'kuota-nasional-create-modal' && (int) old('komoditas_id') === $option->id)>
                                                {{ $option->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'kuota-nasional-create-modal' && $errors->has('komoditas_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('komoditas_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kuota-nasional-create-species">
                                        {{ __('Spesies') }}
                                    </label>
                                    <x-species-select depends-on="kuota-nasional-create-komoditas"
                                                       id="kuota-nasional-create-species" name="species_id"
                                                       :selected="old('_modal') === 'kuota-nasional-create-modal' ? old('species_id') : null"/>
                                    @if (old('_modal') === 'kuota-nasional-create-modal' && $errors->has('species_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('species_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kuota-nasional-create-source">
                                        {{ __('Status Asal') }}
                                    </label>
                                    <select class="form-input" data-choice id="kuota-nasional-create-source"
                                            name="source_id">
                                        <option value="">{{ __('Pilih Status Asal') }}</option>
                                        @foreach ($sourceOptions as $option)
                                            <option value="{{ $option->id }}"
                                                    @selected(old('_modal') === 'kuota-nasional-create-modal' && (int) old('source_id') === $option->id)>
                                                {{ $option->code }} - {{ $option->description }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'kuota-nasional-create-modal' && $errors->has('source_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('source_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kuota-nasional-create-trade-code">
                                        {{ __('Kode Perdagangan') }}
                                    </label>
                                    <select class="form-input" data-choice id="kuota-nasional-create-trade-code"
                                            name="trade_code_id">
                                        <option value="">{{ __('Pilih Kode Perdagangan') }}</option>
                                        @foreach ($tradeCodeOptions as $option)
                                            <option value="{{ $option->id }}"
                                                    @selected(old('_modal') === 'kuota-nasional-create-modal' && (int) old('trade_code_id') === $option->id)>
                                                {{ $option->code }} - {{ $option->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'kuota-nasional-create-modal' && $errors->has('trade_code_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('trade_code_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="kuota-nasional-create-quota">
                                        {{ __('Kuota') }}
                                    </label>
                                    <input class="form-input" id="kuota-nasional-create-quota" name="quota" step="0.01"
                                           type="number"
                                           value="{{ old('_modal') === 'kuota-nasional-create-modal' ? old('quota') : '' }}"/>
                                    @if (old('_modal') === 'kuota-nasional-create-modal' && $errors->has('quota'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('quota') }}</p>
                                    @endif
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kuota-nasional-create-year">
                                            {{ __('Tahun') }}
                                        </label>
                                        <input class="form-input" id="kuota-nasional-create-year" name="year" type="number"
                                               value="{{ old('_modal') === 'kuota-nasional-create-modal' ? old('year') : now()->year }}"/>
                                        @if (old('_modal') === 'kuota-nasional-create-modal' && $errors->has('year'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('year') }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 sm:mt-7 sm:h-9.25">
                                        <input class="form-checkbox" id="kuota-nasional-create-sats-ln"
                                               name="show_in_sats_ln" type="checkbox" value="1"
                                               @checked(old('_modal') === 'kuota-nasional-create-modal' ? old('show_in_sats_ln') : true)/>
                                        <label class="text-sm font-medium" for="kuota-nasional-create-sats-ln">
                                            {{ __('Tampilkan Data Kuota di SATS-LN') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#kuota-nasional-create-modal"
                                    type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('kuota-nasional.update')
        @foreach ($kuotaNasional as $item)
            <div aria-labelledby="kuota-nasional-edit-modal-{{ $item->id }}-label"
                 id="kuota-nasional-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base"
                                id="kuota-nasional-edit-modal-{{ $item->id }}-label">
                                {{ __('Ubah Kuota Nasional') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#kuota-nasional-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('master.kuota-nasional.update', $item) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="kuota-nasional-edit-modal-{{ $item->id }}">
                            {{-- Sengaja tanpa overflow-y-auto/max-h: itu meng-clip dropdown Choices.js, lihat claude/permohonan-task.md. --}}
                            <div class="p-4">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kuota-nasional-edit-komoditas-{{ $item->id }}">
                                            {{ __('Komoditas') }}
                                        </label>
                                        <select class="form-input" data-choice
                                                id="kuota-nasional-edit-komoditas-{{ $item->id }}" name="komoditas_id">
                                            <option value="">{{ __('Pilih Komoditas') }}</option>
                                            @foreach ($komoditasOptions as $option)
                                                <option value="{{ $option->id }}"
                                                        @selected(old('_modal') === 'kuota-nasional-edit-modal-'.$item->id ? (int) old('komoditas_id') === $option->id : $item->komoditas_id === $option->id)>
                                                    {{ $option->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (old('_modal') === 'kuota-nasional-edit-modal-'.$item->id && $errors->has('komoditas_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('komoditas_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kuota-nasional-edit-species-{{ $item->id }}">
                                            {{ __('Spesies') }}
                                        </label>
                                        <x-species-select depends-on="kuota-nasional-edit-komoditas-{{ $item->id }}"
                                                           id="kuota-nasional-edit-species-{{ $item->id }}"
                                                           name="species_id"
                                                           :selected="old('_modal') === 'kuota-nasional-edit-modal-'.$item->id ? old('species_id') : $item->species_id"/>
                                        @if (old('_modal') === 'kuota-nasional-edit-modal-'.$item->id && $errors->has('species_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('species_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kuota-nasional-edit-source-{{ $item->id }}">
                                            {{ __('Status Asal') }}
                                        </label>
                                        <select class="form-input" data-choice
                                                id="kuota-nasional-edit-source-{{ $item->id }}" name="source_id">
                                            <option value="">{{ __('Pilih Status Asal') }}</option>
                                            @foreach ($sourceOptions as $option)
                                                <option value="{{ $option->id }}"
                                                        @selected(old('_modal') === 'kuota-nasional-edit-modal-'.$item->id ? (int) old('source_id') === $option->id : $item->source_id === $option->id)>
                                                    {{ $option->code }} - {{ $option->description }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (old('_modal') === 'kuota-nasional-edit-modal-'.$item->id && $errors->has('source_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('source_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kuota-nasional-edit-trade-code-{{ $item->id }}">
                                            {{ __('Kode Perdagangan') }}
                                        </label>
                                        <select class="form-input" data-choice
                                                id="kuota-nasional-edit-trade-code-{{ $item->id }}" name="trade_code_id">
                                            <option value="">{{ __('Pilih Kode Perdagangan') }}</option>
                                            @foreach ($tradeCodeOptions as $option)
                                                <option value="{{ $option->id }}"
                                                        @selected(old('_modal') === 'kuota-nasional-edit-modal-'.$item->id ? (int) old('trade_code_id') === $option->id : $item->trade_code_id === $option->id)>
                                                    {{ $option->code }} - {{ $option->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (old('_modal') === 'kuota-nasional-edit-modal-'.$item->id && $errors->has('trade_code_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('trade_code_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kuota-nasional-edit-quota-{{ $item->id }}">
                                            {{ __('Kuota') }}
                                        </label>
                                        <input class="form-input" id="kuota-nasional-edit-quota-{{ $item->id }}"
                                               name="quota" step="0.01" type="number"
                                               value="{{ old('_modal') === 'kuota-nasional-edit-modal-'.$item->id ? old('quota') : $item->quota }}"/>
                                        @if (old('_modal') === 'kuota-nasional-edit-modal-'.$item->id && $errors->has('quota'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('quota') }}</p>
                                        @endif
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="inline-block mb-2 text-sm font-medium"
                                                   for="kuota-nasional-edit-year-{{ $item->id }}">
                                                {{ __('Tahun') }}
                                            </label>
                                            <input class="form-input" id="kuota-nasional-edit-year-{{ $item->id }}"
                                                   name="year" type="number"
                                                   value="{{ old('_modal') === 'kuota-nasional-edit-modal-'.$item->id ? old('year') : $item->year }}"/>
                                            @if (old('_modal') === 'kuota-nasional-edit-modal-'.$item->id && $errors->has('year'))
                                                <p class="mt-1 text-xs text-danger">{{ $errors->first('year') }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 sm:mt-7 sm:h-9.25">
                                            <input class="form-checkbox" id="kuota-nasional-edit-sats-ln-{{ $item->id }}"
                                                   name="show_in_sats_ln" type="checkbox" value="1"
                                                   @checked(old('_modal') === 'kuota-nasional-edit-modal-'.$item->id ? old('show_in_sats_ln') : $item->show_in_sats_ln)/>
                                            <label class="text-sm font-medium" for="kuota-nasional-edit-sats-ln-{{ $item->id }}">
                                                {{ __('Tampilkan Data Kuota di SATS-LN') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150"
                                        data-hs-overlay="#kuota-nasional-edit-modal-{{ $item->id }}" type="button">
                                    {{ __('Batal') }}
                                </button>
                                <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endcan

    @can('kuota-nasional.delete')
        @foreach ($kuotaNasional as $item)
            <div aria-labelledby="kuota-nasional-delete-modal-{{ $item->id }}-label"
                 id="kuota-nasional-delete-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#kuota-nasional-delete-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="{{ asset('images/delete.png') }}"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Kuota nasional untuk ":species" tahun :year akan dihapus permanen.', ['species' => $item->species?->scientific_name, 'year' => $item->year]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#kuota-nasional-delete-modal-{{ $item->id }}"
                                            type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('master.kuota-nasional.destroy', $item) }}" method="POST">
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
