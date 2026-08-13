@extends('layouts.vertical', ['title' => 'Spesies'])

@section('css')

@endsection

@php
    $tradeStatuses = ['I', 'II', 'III', 'Non Appendix'];
@endphp

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Master Data', 'title' => 'Spesies'])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Spesies') }}</h6>
            @can('species.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#species-create-modal" type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Spesies') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <form action="{{ route('master.species.index') }}" class="md:flex items-center md:space-y-0 space-y-4 gap-3"
                  method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search"
                           placeholder="{{ __('Cari nama ilmiah') }}" type="text" value="{{ request('search') }}"/>
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Ilmiah') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Umum') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Lokal') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Komoditas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status Perdagangan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($species as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3 italic">{{ $item->scientific_name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->common_name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->local_name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->komoditas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->trade_status }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('species.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#species-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('species.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#species-delete-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="6">
                                        {{ __('Belum ada data spesies.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $species->count() }}</b> {{ __('dari') }} <b>{{ $species->total() }}</b>
                    {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $species])
            </div>
        </div>
    </div>

    @can('species.create')
        <div aria-labelledby="species-create-modal-label" id="species-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="species-create-modal-label">
                            {{ __('Tambah Spesies') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#species-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.species.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="species-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="species-create-komoditas">
                                        {{ __('Komoditas') }}
                                    </label>
                                    <select class="form-input" id="species-create-komoditas" name="komoditas_id">
                                        <option value="">{{ __('Pilih Komoditas') }}</option>
                                        @foreach ($komoditasOptions as $komoditasOption)
                                            <option value="{{ $komoditasOption->id }}"
                                                    @selected(old('_modal') === 'species-create-modal' && (int) old('komoditas_id') === $komoditasOption->id)>
                                                {{ $komoditasOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'species-create-modal' && $errors->has('komoditas_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('komoditas_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="species-create-scientific-name">
                                        {{ __('Nama Ilmiah') }}
                                    </label>
                                    <input class="form-input" id="species-create-scientific-name" name="scientific_name"
                                           type="text"
                                           value="{{ old('_modal') === 'species-create-modal' ? old('scientific_name') : '' }}"/>
                                    @if (old('_modal') === 'species-create-modal' && $errors->has('scientific_name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('scientific_name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="species-create-common-name">
                                        {{ __('Nama Umum') }}
                                    </label>
                                    <input class="form-input" id="species-create-common-name" name="common_name"
                                           type="text"
                                           value="{{ old('_modal') === 'species-create-modal' ? old('common_name') : '' }}"/>
                                    @if (old('_modal') === 'species-create-modal' && $errors->has('common_name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('common_name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="species-create-local-name">
                                        {{ __('Nama Lokal') }}
                                    </label>
                                    <input class="form-input" id="species-create-local-name" name="local_name"
                                           type="text"
                                           value="{{ old('_modal') === 'species-create-modal' ? old('local_name') : '' }}"/>
                                    @if (old('_modal') === 'species-create-modal' && $errors->has('local_name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('local_name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="species-create-trade-status">
                                        {{ __('Status Perdagangan (CITES)') }}
                                    </label>
                                    <select class="form-input" id="species-create-trade-status" name="trade_status">
                                        @foreach ($tradeStatuses as $statusOption)
                                            <option value="{{ $statusOption }}"
                                                    @selected(old('_modal') === 'species-create-modal' && old('trade_status') === $statusOption)>
                                                {{ $statusOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'species-create-modal' && $errors->has('trade_status'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('trade_status') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#species-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('species.update')
        @foreach ($species as $item)
            <div aria-labelledby="species-edit-modal-{{ $item->id }}-label" id="species-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base"
                                id="species-edit-modal-{{ $item->id }}-label">
                                {{ __('Ubah Spesies') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#species-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('master.species.update', $item) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="species-edit-modal-{{ $item->id }}">
                            <div class="p-4 overflow-y-auto">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="species-edit-komoditas-{{ $item->id }}">
                                            {{ __('Komoditas') }}
                                        </label>
                                        <select class="form-input" id="species-edit-komoditas-{{ $item->id }}"
                                                name="komoditas_id">
                                            <option value="">{{ __('Pilih Komoditas') }}</option>
                                            @foreach ($komoditasOptions as $komoditasOption)
                                                <option value="{{ $komoditasOption->id }}"
                                                        @selected(old('_modal') === 'species-edit-modal-'.$item->id ? (int) old('komoditas_id') === $komoditasOption->id : $item->komoditas_id === $komoditasOption->id)>
                                                    {{ $komoditasOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (old('_modal') === 'species-edit-modal-'.$item->id && $errors->has('komoditas_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('komoditas_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="species-edit-scientific-name-{{ $item->id }}">
                                            {{ __('Nama Ilmiah') }}
                                        </label>
                                        <input class="form-input" id="species-edit-scientific-name-{{ $item->id }}"
                                               name="scientific_name" type="text"
                                               value="{{ old('_modal') === 'species-edit-modal-'.$item->id ? old('scientific_name') : $item->scientific_name }}"/>
                                        @if (old('_modal') === 'species-edit-modal-'.$item->id && $errors->has('scientific_name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('scientific_name') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="species-edit-common-name-{{ $item->id }}">
                                            {{ __('Nama Umum') }}
                                        </label>
                                        <input class="form-input" id="species-edit-common-name-{{ $item->id }}"
                                               name="common_name" type="text"
                                               value="{{ old('_modal') === 'species-edit-modal-'.$item->id ? old('common_name') : $item->common_name }}"/>
                                        @if (old('_modal') === 'species-edit-modal-'.$item->id && $errors->has('common_name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('common_name') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="species-edit-local-name-{{ $item->id }}">
                                            {{ __('Nama Lokal') }}
                                        </label>
                                        <input class="form-input" id="species-edit-local-name-{{ $item->id }}"
                                               name="local_name" type="text"
                                               value="{{ old('_modal') === 'species-edit-modal-'.$item->id ? old('local_name') : $item->local_name }}"/>
                                        @if (old('_modal') === 'species-edit-modal-'.$item->id && $errors->has('local_name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('local_name') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="species-edit-trade-status-{{ $item->id }}">
                                            {{ __('Status Perdagangan (CITES)') }}
                                        </label>
                                        <select class="form-input" id="species-edit-trade-status-{{ $item->id }}"
                                                name="trade_status">
                                            @foreach ($tradeStatuses as $statusOption)
                                                <option value="{{ $statusOption }}"
                                                        @selected(old('_modal') === 'species-edit-modal-'.$item->id ? old('trade_status') === $statusOption : $item->trade_status === $statusOption)>
                                                    {{ $statusOption }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (old('_modal') === 'species-edit-modal-'.$item->id && $errors->has('trade_status'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('trade_status') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#species-edit-modal-{{ $item->id }}"
                                        type="button">
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

    @can('species.delete')
        @foreach ($species as $item)
            <div aria-labelledby="species-delete-modal-{{ $item->id }}-label" id="species-delete-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#species-delete-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Spesies ":name" akan dihapus permanen.', ['name' => $item->scientific_name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#species-delete-modal-{{ $item->id }}" type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('master.species.destroy', $item) }}" method="POST">
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

@section('scripts')

@endsection
