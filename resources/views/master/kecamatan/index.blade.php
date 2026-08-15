@extends('layouts.vertical', ['title' => __('Kecamatan')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Master Data'), 'title' => __('Kecamatan')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Kecamatan') }}</h6>
            <div class="flex items-center gap-2">
                @can('kecamatan.sync')
                    <form action="{{ route('master.kecamatan.sync') }}" id="kecamatan-sync-form" method="POST">
                        @csrf
                        <button class="btn btn-sm bg-default-150 text-default-700 hover:bg-default-200"
                                id="kecamatan-sync-button" type="submit">
                            <i class="size-4 me-1" data-lucide="refresh-cw" id="kecamatan-sync-icon"></i>{{ __('Sinkron dari INSW') }}
                        </button>
                    </form>
                @endcan
                @can('kecamatan.create')
                    <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#kecamatan-create-modal" type="button">
                        <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Kecamatan') }}
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-header">
            <form action="{{ route('master.kecamatan.index') }}" class="md:flex items-center md:space-y-0 space-y-4 gap-3"
                  method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search"
                           placeholder="{{ __('Cari nama kecamatan') }}" type="text" value="{{ request('search') }}"/>
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Kecamatan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kota') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($kecamatan as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kota?->name }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('kecamatan.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-kecamatan-edit
                                                        data-id="{{ $item->id }}"
                                                        data-code="{{ $item->code }}"
                                                        data-name="{{ $item->name }}"
                                                        data-kota-id="{{ $item->kota_id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('kecamatan.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-kecamatan-delete
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
                                        {{ __('Belum ada data kecamatan.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $kecamatan->count() }}</b> {{ __('dari') }}
                    <b>{{ $kecamatan->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $kecamatan])
            </div>
        </div>
    </div>

    @can('kecamatan.create')
        <div aria-labelledby="kecamatan-create-modal-label" id="kecamatan-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="kecamatan-create-modal-label">
                            {{ __('Tambah Kecamatan') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#kecamatan-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.kecamatan.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="kecamatan-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kecamatan-create-kota">
                                        {{ __('Kota') }}
                                    </label>
                                    <select class="form-input" id="kecamatan-create-kota" name="kota_id">
                                        <option value="">{{ __('Pilih Kota') }}</option>
                                        @foreach ($kotaOptions as $kotaOption)
                                            <option value="{{ $kotaOption->id }}"
                                                    @selected(old('_modal') === 'kecamatan-create-modal' && (int) old('kota_id') === $kotaOption->id)>
                                                {{ $kotaOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'kecamatan-create-modal' && $errors->has('kota_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('kota_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kecamatan-create-code">
                                        {{ __('Kode Kecamatan') }}
                                    </label>
                                    <input class="form-input" id="kecamatan-create-code" name="code" type="text"
                                           value="{{ old('_modal') === 'kecamatan-create-modal' ? old('code') : '' }}"/>
                                    @if (old('_modal') === 'kecamatan-create-modal' && $errors->has('code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kecamatan-create-name">
                                        {{ __('Nama Kecamatan') }}
                                    </label>
                                    <input class="form-input" id="kecamatan-create-name" name="name" type="text"
                                           value="{{ old('_modal') === 'kecamatan-create-modal' ? old('name') : '' }}"/>
                                    @if (old('_modal') === 'kecamatan-create-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#kecamatan-create-modal" type="button">
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
        data-* attributes (resources/js/pages/kecamatan-index.js). Rendering one modal per row
        used to mean the full Kota dropdown (up to 535 options) got duplicated once per visible
        row; with only one modal, the dropdown is rendered exactly once per page load.
    --}}
    @can('kecamatan.update')
        <div aria-labelledby="kecamatan-edit-modal-label" id="kecamatan-edit-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="kecamatan-edit-modal-label">
                            {{ __('Ubah Kecamatan') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#kecamatan-edit-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ old('_modal') === 'kecamatan-edit-modal' ? route('master.kecamatan.update', old('_modal_kecamatan_id')) : route('master.kecamatan.index') }}"
                          data-action-template="{{ route('master.kecamatan.update', ['kecamatan' => '__ID__']) }}"
                          id="kecamatan-edit-form" method="POST">
                        @csrf
                        @method('PUT')
                        <input name="_modal" type="hidden" value="kecamatan-edit-modal">
                        <input name="_modal_kecamatan_id" type="hidden" value="{{ old('_modal_kecamatan_id') }}">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kecamatan-edit-kota">
                                        {{ __('Kota') }}
                                    </label>
                                    <select class="form-input" id="kecamatan-edit-kota" name="kota_id">
                                        <option value="">{{ __('Pilih Kota') }}</option>
                                        @foreach ($kotaOptions as $kotaOption)
                                            <option value="{{ $kotaOption->id }}"
                                                    @selected(old('_modal') === 'kecamatan-edit-modal' && (int) old('kota_id') === $kotaOption->id)>
                                                {{ $kotaOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'kecamatan-edit-modal' && $errors->has('kota_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('kota_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kecamatan-edit-code">
                                        {{ __('Kode Kecamatan') }}
                                    </label>
                                    <input class="form-input" id="kecamatan-edit-code" name="code" type="text"
                                           value="{{ old('_modal') === 'kecamatan-edit-modal' ? old('code') : '' }}"/>
                                    @if (old('_modal') === 'kecamatan-edit-modal' && $errors->has('code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kecamatan-edit-name">
                                        {{ __('Nama Kecamatan') }}
                                    </label>
                                    <input class="form-input" id="kecamatan-edit-name" name="name" type="text"
                                           value="{{ old('_modal') === 'kecamatan-edit-modal' ? old('name') : '' }}"/>
                                    @if (old('_modal') === 'kecamatan-edit-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#kecamatan-edit-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('kecamatan.delete')
        <div aria-labelledby="kecamatan-delete-modal-label" id="kecamatan-delete-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div
                    class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                    <div class="absolute top-3 end-3">
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#kecamatan-delete-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <div class="text-center">
                        <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                        <div class="mt-5 text-center">
                            <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                            <p class="text-default-500 text-sm font-normal">
                                {{ __('Kecamatan') }} "<span data-delete-name></span>" {{ __('akan dihapus permanen.') }}
                            </p>
                            <div class="mt-5 flex gap-2 justify-center">
                                <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                        data-hs-overlay="#kecamatan-delete-modal" type="button">
                                    {{ __('Batal') }}
                                </button>
                                <form action="{{ route('master.kecamatan.index') }}"
                                      data-action-template="{{ route('master.kecamatan.destroy', ['kecamatan' => '__ID__']) }}"
                                      id="kecamatan-delete-form" method="POST">
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
    @vite(['resources/js/pages/kecamatan-index.js'])
    <script>
        document.getElementById('kecamatan-sync-form')?.addEventListener('submit', function () {
            const button = document.getElementById('kecamatan-sync-button');
            const icon = document.getElementById('kecamatan-sync-icon');
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
            icon?.classList.add('animate-spin');
        });
    </script>
@endsection
