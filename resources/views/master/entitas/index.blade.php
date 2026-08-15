@extends('layouts.vertical', ['title' => 'Entitas'])

@section('css')

@endsection

@php
    $entityTypeLabels = [
        'perusahaan' => __('Perusahaan'),
        'penangkaran' => __('Penangkaran'),
        'perorangan' => __('Perorangan'),
        'upt_ksda' => __('UPT KSDA'),
    ];
@endphp

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Administrasi', 'title' => 'Entitas'])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Entitas') }}</h6>
            @can('entitas.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#entitas-create-modal" type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Entitas') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <form action="{{ route('master.entitas.index') }}" class="md:flex items-center md:space-y-0 space-y-4 gap-3"
                  method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search" placeholder="{{ __('Cari nama entitas') }}"
                           type="text" value="{{ request('search') }}"/>
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Entitas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Jenis') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Provinsi') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kota') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($entities as $entity)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $entity->name }}</td>
                                    <td class="px-3.5 py-3">{{ $entityTypeLabels[$entity->entity_type] ?? $entity->entity_type }}</td>
                                    <td class="px-3.5 py-3">{{ $entity->provinsi?->name ?? '-' }}</td>
                                    <td class="px-3.5 py-3">{{ $entity->kota?->name ?? '-' }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('entitas.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#entitas-edit-modal-{{ $entity->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('entitas.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#entitas-delete-modal-{{ $entity->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="5">
                                        {{ __('Belum ada data entitas.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $entities->count() }}</b> {{ __('dari') }}
                    <b>{{ $entities->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $entities])
            </div>
        </div>
    </div>

    @can('entitas.create')
        <div aria-labelledby="entitas-create-modal-label" id="entitas-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="entitas-create-modal-label">
                            {{ __('Tambah Entitas') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#entitas-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.entitas.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="entitas-create-modal">
                        <div class="p-4 overflow-y-auto max-h-[70vh]">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="entitas-create-entity-type">
                                        {{ __('Jenis Entitas') }}
                                    </label>
                                    <select class="form-input" id="entitas-create-entity-type" name="entity_type">
                                        <option value="">{{ __('Pilih Jenis Entitas') }}</option>
                                        @foreach ($entityTypeLabels as $value => $label)
                                            <option value="{{ $value }}"
                                                    @selected(old('_modal') === 'entitas-create-modal' && old('entity_type') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'entitas-create-modal' && $errors->has('entity_type'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('entity_type') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="entitas-create-name">
                                        {{ __('Nama Entitas') }}
                                    </label>
                                    <input class="form-input" id="entitas-create-name" name="name" type="text"
                                           value="{{ old('_modal') === 'entitas-create-modal' ? old('name') : '' }}"/>
                                    @if (old('_modal') === 'entitas-create-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="entitas-create-address">
                                        {{ __('Alamat') }}
                                    </label>
                                    <textarea class="form-input" id="entitas-create-address" name="address"
                                              rows="2">{{ old('_modal') === 'entitas-create-modal' ? old('address') : '' }}</textarea>
                                    @if (old('_modal') === 'entitas-create-modal' && $errors->has('address'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('address') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="entitas-create-provinsi">
                                        {{ __('Provinsi') }}
                                    </label>
                                    <x-province-select id="entitas-create-provinsi" name="provinsi_id"
                                                        :selected="old('_modal') === 'entitas-create-modal' ? old('provinsi_id') : null"/>
                                    @if (old('_modal') === 'entitas-create-modal' && $errors->has('provinsi_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('provinsi_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="entitas-create-kota">
                                        {{ __('Kota') }}
                                    </label>
                                    <x-city-select depends-on="entitas-create-provinsi" id="entitas-create-kota"
                                                    name="kota_id"
                                                    :selected="old('_modal') === 'entitas-create-modal' ? old('kota_id') : null"/>
                                    @if (old('_modal') === 'entitas-create-modal' && $errors->has('kota_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('kota_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="entitas-create-kecamatan">
                                        {{ __('Kecamatan') }}
                                    </label>
                                    <x-district-select depends-on="entitas-create-kota" id="entitas-create-kecamatan"
                                                        name="kecamatan_id"
                                                        :selected="old('_modal') === 'entitas-create-modal' ? old('kecamatan_id') : null"/>
                                    @if (old('_modal') === 'entitas-create-modal' && $errors->has('kecamatan_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('kecamatan_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="entitas-create-kelurahan">
                                        {{ __('Kelurahan') }}
                                    </label>
                                    <x-subdistrict-select depends-on="entitas-create-kecamatan" id="entitas-create-kelurahan"
                                                           name="kelurahan_id"
                                                           :selected="old('_modal') === 'entitas-create-modal' ? old('kelurahan_id') : null"/>
                                    @if (old('_modal') === 'entitas-create-modal' && $errors->has('kelurahan_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('kelurahan_id') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#entitas-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('entitas.update')
        @foreach ($entities as $entity)
            <div aria-labelledby="entitas-edit-modal-{{ $entity->id }}-label" id="entitas-edit-modal-{{ $entity->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base"
                                id="entitas-edit-modal-{{ $entity->id }}-label">
                                {{ __('Ubah Entitas') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#entitas-edit-modal-{{ $entity->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('master.entitas.update', $entity) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="entitas-edit-modal-{{ $entity->id }}">
                            <div class="p-4 overflow-y-auto max-h-[70vh]">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="entitas-edit-entity-type-{{ $entity->id }}">
                                            {{ __('Jenis Entitas') }}
                                        </label>
                                        <select class="form-input" id="entitas-edit-entity-type-{{ $entity->id }}"
                                                name="entity_type">
                                            <option value="">{{ __('Pilih Jenis Entitas') }}</option>
                                            @foreach ($entityTypeLabels as $value => $label)
                                                <option value="{{ $value }}"
                                                        @selected(old('_modal') === 'entitas-edit-modal-'.$entity->id ? old('entity_type') === $value : $entity->entity_type === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (old('_modal') === 'entitas-edit-modal-'.$entity->id && $errors->has('entity_type'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('entity_type') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="entitas-edit-name-{{ $entity->id }}">
                                            {{ __('Nama Entitas') }}
                                        </label>
                                        <input class="form-input" id="entitas-edit-name-{{ $entity->id }}" name="name"
                                               type="text"
                                               value="{{ old('_modal') === 'entitas-edit-modal-'.$entity->id ? old('name') : $entity->name }}"/>
                                        @if (old('_modal') === 'entitas-edit-modal-'.$entity->id && $errors->has('name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="entitas-edit-address-{{ $entity->id }}">
                                            {{ __('Alamat') }}
                                        </label>
                                        <textarea class="form-input" id="entitas-edit-address-{{ $entity->id }}"
                                                  name="address"
                                                  rows="2">{{ old('_modal') === 'entitas-edit-modal-'.$entity->id ? old('address') : $entity->address }}</textarea>
                                        @if (old('_modal') === 'entitas-edit-modal-'.$entity->id && $errors->has('address'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('address') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="entitas-edit-provinsi-{{ $entity->id }}">
                                            {{ __('Provinsi') }}
                                        </label>
                                        <x-province-select id="entitas-edit-provinsi-{{ $entity->id }}" name="provinsi_id"
                                                            :selected="old('_modal') === 'entitas-edit-modal-'.$entity->id ? old('provinsi_id') : $entity->provinsi_id"/>
                                        @if (old('_modal') === 'entitas-edit-modal-'.$entity->id && $errors->has('provinsi_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('provinsi_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="entitas-edit-kota-{{ $entity->id }}">
                                            {{ __('Kota') }}
                                        </label>
                                        <x-city-select depends-on="entitas-edit-provinsi-{{ $entity->id }}"
                                                        id="entitas-edit-kota-{{ $entity->id }}" name="kota_id"
                                                        :selected="old('_modal') === 'entitas-edit-modal-'.$entity->id ? old('kota_id') : $entity->kota_id"/>
                                        @if (old('_modal') === 'entitas-edit-modal-'.$entity->id && $errors->has('kota_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('kota_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="entitas-edit-kecamatan-{{ $entity->id }}">
                                            {{ __('Kecamatan') }}
                                        </label>
                                        <x-district-select depends-on="entitas-edit-kota-{{ $entity->id }}"
                                                            id="entitas-edit-kecamatan-{{ $entity->id }}" name="kecamatan_id"
                                                            :selected="old('_modal') === 'entitas-edit-modal-'.$entity->id ? old('kecamatan_id') : $entity->kecamatan_id"/>
                                        @if (old('_modal') === 'entitas-edit-modal-'.$entity->id && $errors->has('kecamatan_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('kecamatan_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="entitas-edit-kelurahan-{{ $entity->id }}">
                                            {{ __('Kelurahan') }}
                                        </label>
                                        <x-subdistrict-select depends-on="entitas-edit-kecamatan-{{ $entity->id }}"
                                                               id="entitas-edit-kelurahan-{{ $entity->id }}" name="kelurahan_id"
                                                               :selected="old('_modal') === 'entitas-edit-modal-'.$entity->id ? old('kelurahan_id') : $entity->kelurahan_id"/>
                                        @if (old('_modal') === 'entitas-edit-modal-'.$entity->id && $errors->has('kelurahan_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('kelurahan_id') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#entitas-edit-modal-{{ $entity->id }}"
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

    @can('entitas.delete')
        @foreach ($entities as $entity)
            <div aria-labelledby="entitas-delete-modal-{{ $entity->id }}-label" id="entitas-delete-modal-{{ $entity->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#entitas-delete-modal-{{ $entity->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Entitas ":name" akan dihapus permanen.', ['name' => $entity->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#entitas-delete-modal-{{ $entity->id }}" type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('master.entitas.destroy', $entity) }}" method="POST">
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
