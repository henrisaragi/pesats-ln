@extends('layouts.vertical', ['title' => __('Kelurahan')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Master Data'), 'title' => __('Kelurahan')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Kelurahan') }}</h6>
            <div class="flex items-center gap-2">
                @can('kelurahan.sync')
                    <form action="{{ route('master.kelurahan.sync') }}" id="kelurahan-sync-form" method="POST">
                        @csrf
                        <button class="btn btn-sm bg-default-150 text-default-700 hover:bg-default-200"
                                id="kelurahan-sync-button" type="submit">
                            <i class="size-4 me-1" data-lucide="refresh-cw" id="kelurahan-sync-icon"></i>{{ __('Sinkron dari INSW') }}
                        </button>
                    </form>
                @endcan
                @can('kelurahan.create')
                    <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#kelurahan-create-modal" type="button">
                        <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Kelurahan') }}
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-header">
            <form action="{{ route('master.kelurahan.index') }}" class="md:flex items-center md:space-y-0 space-y-4 gap-3"
                  method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search"
                           placeholder="{{ __('Cari nama kelurahan') }}" type="text" value="{{ request('search') }}"/>
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kode') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Kelurahan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kecamatan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($kelurahan as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kecamatan?->name }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('kelurahan.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#kelurahan-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('kelurahan.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-kelurahan-delete
                                                        data-id="{{ $item->id }}"
                                                        data-name="{{ $item->name }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="4">
                                        {{ __('Belum ada data kelurahan.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $kelurahan->count() }}</b> {{ __('dari') }}
                    <b>{{ $kelurahan->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $kelurahan])
            </div>
        </div>
    </div>

    @can('kelurahan.create')
        <div aria-labelledby="kelurahan-create-modal-label" id="kelurahan-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="kelurahan-create-modal-label">
                            {{ __('Tambah Kelurahan') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#kelurahan-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.kelurahan.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="kelurahan-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kelurahan-create-provinsi">
                                        {{ __('Provinsi') }}
                                    </label>
                                    <x-province-select id="kelurahan-create-provinsi" name="provinsi_id"
                                                        :selected="old('_modal') === 'kelurahan-create-modal' ? old('provinsi_id') : null"/>
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kelurahan-create-kota">
                                        {{ __('Kota') }}
                                    </label>
                                    <x-city-select depends-on="kelurahan-create-provinsi" id="kelurahan-create-kota"
                                                    name="kota_id"
                                                    :selected="old('_modal') === 'kelurahan-create-modal' ? old('kota_id') : null"/>
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kelurahan-create-kecamatan">
                                        {{ __('Kecamatan') }}
                                    </label>
                                    <x-district-select depends-on="kelurahan-create-kota" id="kelurahan-create-kecamatan"
                                                        name="kecamatan_id"
                                                        :selected="old('_modal') === 'kelurahan-create-modal' ? old('kecamatan_id') : null"/>
                                    @if (old('_modal') === 'kelurahan-create-modal' && $errors->has('kecamatan_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('kecamatan_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kelurahan-create-code">
                                        {{ __('Kode Kelurahan') }}
                                    </label>
                                    <input class="form-input" id="kelurahan-create-code" name="code" type="text"
                                           value="{{ old('_modal') === 'kelurahan-create-modal' ? old('code') : '' }}"/>
                                    @if (old('_modal') === 'kelurahan-create-modal' && $errors->has('code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kelurahan-create-name">
                                        {{ __('Nama Kelurahan') }}
                                    </label>
                                    <input class="form-input" id="kelurahan-create-name" name="name" type="text"
                                           value="{{ old('_modal') === 'kelurahan-create-modal' ? old('name') : '' }}"/>
                                    @if (old('_modal') === 'kelurahan-create-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#kelurahan-create-modal" type="button">
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
        One edit modal per row (like Entitas). Each x-district-select instance only ever
        renders its single currently-selected option server-side, so this stays cheap even
        with the full Provinsi/Kota/Kecamatan cascade repeated per visible row.
    --}}
    @can('kelurahan.update')
        @foreach ($kelurahan as $item)
            <div aria-labelledby="kelurahan-edit-modal-{{ $item->id }}-label" id="kelurahan-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="kelurahan-edit-modal-{{ $item->id }}-label">
                                {{ __('Ubah Kelurahan') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#kelurahan-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('master.kelurahan.update', $item) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="kelurahan-edit-modal-{{ $item->id }}">
                            <div class="p-4 overflow-y-auto">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kelurahan-edit-provinsi-{{ $item->id }}">
                                            {{ __('Provinsi') }}
                                        </label>
                                        <x-province-select id="kelurahan-edit-provinsi-{{ $item->id }}" name="provinsi_id"
                                                            :selected="old('_modal') === 'kelurahan-edit-modal-'.$item->id ? old('provinsi_id') : $item->kecamatan?->kota?->provinsi_id"/>
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kelurahan-edit-kota-{{ $item->id }}">
                                            {{ __('Kota') }}
                                        </label>
                                        <x-city-select depends-on="kelurahan-edit-provinsi-{{ $item->id }}"
                                                        id="kelurahan-edit-kota-{{ $item->id }}" name="kota_id"
                                                        :selected="old('_modal') === 'kelurahan-edit-modal-'.$item->id ? old('kota_id') : $item->kecamatan?->kota_id"/>
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kelurahan-edit-kecamatan-{{ $item->id }}">
                                            {{ __('Kecamatan') }}
                                        </label>
                                        <x-district-select depends-on="kelurahan-edit-kota-{{ $item->id }}"
                                                            id="kelurahan-edit-kecamatan-{{ $item->id }}" name="kecamatan_id"
                                                            :selected="old('_modal') === 'kelurahan-edit-modal-'.$item->id ? old('kecamatan_id') : $item->kecamatan_id"/>
                                        @if (old('_modal') === 'kelurahan-edit-modal-'.$item->id && $errors->has('kecamatan_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('kecamatan_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kelurahan-edit-code-{{ $item->id }}">
                                            {{ __('Kode Kelurahan') }}
                                        </label>
                                        <input class="form-input" id="kelurahan-edit-code-{{ $item->id }}" name="code"
                                               type="text"
                                               value="{{ old('_modal') === 'kelurahan-edit-modal-'.$item->id ? old('code') : $item->code }}"/>
                                        @if (old('_modal') === 'kelurahan-edit-modal-'.$item->id && $errors->has('code'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kelurahan-edit-name-{{ $item->id }}">
                                            {{ __('Nama Kelurahan') }}
                                        </label>
                                        <input class="form-input" id="kelurahan-edit-name-{{ $item->id }}" name="name"
                                               type="text"
                                               value="{{ old('_modal') === 'kelurahan-edit-modal-'.$item->id ? old('name') : $item->name }}"/>
                                        @if (old('_modal') === 'kelurahan-edit-modal-'.$item->id && $errors->has('name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#kelurahan-edit-modal-{{ $item->id }}"
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

    @can('kelurahan.delete')
        <div aria-labelledby="kelurahan-delete-modal-label" id="kelurahan-delete-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div
                    class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                    <div class="absolute top-3 end-3">
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#kelurahan-delete-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <div class="text-center">
                        <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                        <div class="mt-5 text-center">
                            <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                            <p class="text-default-500 text-sm font-normal">
                                {{ __('Kelurahan') }} "<span data-delete-name></span>" {{ __('akan dihapus permanen.') }}
                            </p>
                            <div class="mt-5 flex gap-2 justify-center">
                                <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                        data-hs-overlay="#kelurahan-delete-modal" type="button">
                                    {{ __('Batal') }}
                                </button>
                                <form action="{{ route('master.kelurahan.index') }}"
                                      data-action-template="{{ route('master.kelurahan.destroy', ['kelurahan' => '__ID__']) }}"
                                      id="kelurahan-delete-form" method="POST">
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
    @vite(['resources/js/pages/kelurahan-index.js'])
    <script>
        document.getElementById('kelurahan-sync-form')?.addEventListener('submit', function () {
            const button = document.getElementById('kelurahan-sync-button');
            const icon = document.getElementById('kelurahan-sync-icon');
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
            icon?.classList.add('animate-spin');
        });
    </script>
@endsection
