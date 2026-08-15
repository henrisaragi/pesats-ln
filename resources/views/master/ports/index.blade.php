@extends('layouts.vertical', ['title' => 'Pelabuhan'])

@section('css')

@endsection

@php
    $portTypes = ['sea' => __('Laut'), 'air' => __('Udara')];
@endphp

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Master Data', 'title' => 'Pelabuhan'])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Pelabuhan') }}</h6>
            <div class="flex items-center gap-2">
                @can('ports.sync')
                    <form action="{{ route('master.ports.sync') }}" id="port-sync-form" method="POST">
                        @csrf
                        <button class="btn btn-sm bg-default-150 text-default-700 hover:bg-default-200"
                                id="port-sync-button" type="submit">
                            <i class="size-4 me-1" data-lucide="download" id="port-sync-icon"></i>{{ __('Ambil Data Referensi') }}
                        </button>
                    </form>
                @endcan
                @can('ports.create')
                    <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#port-create-modal" type="button">
                        <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Pelabuhan') }}
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-header">
            <div class="md:flex items-center md:space-y-0 space-y-4 gap-3 w-full">
                <div class="relative md:flex-1 md:max-w-sm">
                    <input class="form-input ps-9" id="ports-search"
                           placeholder="{{ __('Cari kode atau nama pelabuhan') }}" type="text"/>
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3">
                        <i class="size-3.5 flex items-center text-default-500 fill-default-100"
                           data-lucide="search"></i>
                    </div>
                </div>
                <div class="md:w-56">
                    <select class="form-input" data-choice id="ports-filter-country">
                        <option value="">{{ __('Semua Negara') }}</option>
                        @foreach ($countryOptions as $countryOption)
                            <option value="{{ $countryOption->id }}">{{ $countryOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <x-data-table :ajax-url="route('master.ports.data')" id="ports-table">
                <x-slot:head>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kode') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Pelabuhan') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Negara') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tipe') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                </x-slot:head>
            </x-data-table>
        </div>
    </div>

    @can('ports.create')
        <div aria-labelledby="port-create-modal-label" id="port-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="port-create-modal-label">
                            {{ __('Tambah Pelabuhan') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#port-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.ports.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="port-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="port-create-country">
                                        {{ __('Negara') }}
                                    </label>
                                    <select class="form-input" id="port-create-country" name="country_id">
                                        <option value="">{{ __('Pilih Negara') }}</option>
                                        @foreach ($countryOptions as $countryOption)
                                            <option value="{{ $countryOption->id }}"
                                                    @selected(old('_modal') === 'port-create-modal' && (int) old('country_id') === $countryOption->id)>
                                                {{ $countryOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'port-create-modal' && $errors->has('country_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('country_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="port-create-code">
                                        {{ __('Kode Pelabuhan') }}
                                    </label>
                                    <input class="form-input" id="port-create-code" name="code" type="text"
                                           value="{{ old('_modal') === 'port-create-modal' ? old('code') : '' }}"/>
                                    @if (old('_modal') === 'port-create-modal' && $errors->has('code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="port-create-name">
                                        {{ __('Nama Pelabuhan') }}
                                    </label>
                                    <input class="form-input" id="port-create-name" name="name" type="text"
                                           value="{{ old('_modal') === 'port-create-modal' ? old('name') : '' }}"/>
                                    @if (old('_modal') === 'port-create-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="port-create-type">
                                        {{ __('Tipe') }}
                                    </label>
                                    <select class="form-input" id="port-create-type" name="type">
                                        @foreach ($portTypes as $value => $label)
                                            <option value="{{ $value }}"
                                                    @selected(old('_modal') === 'port-create-modal' ? old('type') === $value : $value === 'sea')>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'port-create-modal' && $errors->has('type'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('type') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#port-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    {{--
        A single shared edit modal (not one per row) - populated via JS from the clicked row's
        data-* attributes (resources/js/pages/ports-index.js). Rows are rendered by DataTables
        via AJAX, so there's no server-side row list to loop over here anyway.
    --}}
    @can('ports.update')
        <div aria-labelledby="port-edit-modal-label" id="port-edit-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="port-edit-modal-label">
                            {{ __('Ubah Pelabuhan') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#port-edit-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ old('_modal') === 'port-edit-modal' ? route('master.ports.update', old('_modal_port_id')) : route('master.ports.index') }}"
                          data-action-template="{{ route('master.ports.update', ['port' => '__ID__']) }}"
                          id="port-edit-form" method="POST">
                        @csrf
                        @method('PUT')
                        <input name="_modal" type="hidden" value="port-edit-modal">
                        <input name="_modal_port_id" type="hidden" value="{{ old('_modal_port_id') }}">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="port-edit-country">
                                        {{ __('Negara') }}
                                    </label>
                                    <select class="form-input" id="port-edit-country" name="country_id">
                                        <option value="">{{ __('Pilih Negara') }}</option>
                                        @foreach ($countryOptions as $countryOption)
                                            <option value="{{ $countryOption->id }}"
                                                    @selected(old('_modal') === 'port-edit-modal' && (int) old('country_id') === $countryOption->id)>
                                                {{ $countryOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'port-edit-modal' && $errors->has('country_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('country_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="port-edit-code">
                                        {{ __('Kode Pelabuhan') }}
                                    </label>
                                    <input class="form-input" id="port-edit-code" name="code" type="text"
                                           value="{{ old('_modal') === 'port-edit-modal' ? old('code') : '' }}"/>
                                    @if (old('_modal') === 'port-edit-modal' && $errors->has('code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="port-edit-name">
                                        {{ __('Nama Pelabuhan') }}
                                    </label>
                                    <input class="form-input" id="port-edit-name" name="name" type="text"
                                           value="{{ old('_modal') === 'port-edit-modal' ? old('name') : '' }}"/>
                                    @if (old('_modal') === 'port-edit-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="port-edit-type">
                                        {{ __('Tipe') }}
                                    </label>
                                    <select class="form-input" id="port-edit-type" name="type">
                                        @foreach ($portTypes as $value => $label)
                                            <option value="{{ $value }}"
                                                    @selected(old('_modal') === 'port-edit-modal' && old('type') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'port-edit-modal' && $errors->has('type'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('type') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#port-edit-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('ports.delete')
        <div aria-labelledby="port-delete-modal-label" id="port-delete-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div
                    class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                    <div class="absolute top-3 end-3">
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#port-delete-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <div class="text-center">
                        <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                        <div class="mt-5 text-center">
                            <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                            <p class="text-default-500 text-sm font-normal">
                                {{ __('Pelabuhan') }} "<span data-delete-name></span>" {{ __('akan dihapus permanen.') }}
                            </p>
                            <div class="mt-5 flex gap-2 justify-center">
                                <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                        data-hs-overlay="#port-delete-modal" type="button">
                                    {{ __('Batal') }}
                                </button>
                                <form action="{{ route('master.ports.index') }}"
                                      data-action-template="{{ route('master.ports.destroy', ['port' => '__ID__']) }}"
                                      id="port-delete-form" method="POST">
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
    @endcan
@endsection

@section('scripts')
    @vite(['resources/js/pages/ports-index.js'])
    <script>
        document.getElementById('port-sync-form')?.addEventListener('submit', function () {
            const button = document.getElementById('port-sync-button');
            const icon = document.getElementById('port-sync-icon');
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
            icon?.classList.add('animate-spin');
        });
    </script>
@endsection
