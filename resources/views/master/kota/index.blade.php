@extends('layouts.vertical', ['title' => 'Kota'])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Master Data', 'title' => 'Kota'])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Kota') }}</h6>
            <div class="flex items-center gap-2">
                @can('kota.sync')
                    <form action="{{ route('master.kota.sync') }}" id="kota-sync-form" method="POST">
                        @csrf
                        <button class="btn btn-sm bg-default-150 text-default-700 hover:bg-default-200"
                                id="kota-sync-button" type="submit">
                            <i class="size-4 me-1" data-lucide="refresh-cw" id="kota-sync-icon"></i>{{ __('Sinkron dari INSW') }}
                        </button>
                    </form>
                @endcan
                @can('kota.create')
                    <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#kota-create-modal" type="button">
                        <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Kota') }}
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-header">
            <form action="{{ route('master.kota.index') }}" class="md:flex items-center md:space-y-0 space-y-4 gap-3"
                  method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search" placeholder="{{ __('Cari nama kota') }}"
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kode') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Kota') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Provinsi') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($kota as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->provinsi?->name }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('kota.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#kota-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('kota.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#kota-delete-modal-{{ $item->id }}"
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
                                        {{ __('Belum ada data kota.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $kota->count() }}</b> {{ __('dari') }} <b>{{ $kota->total() }}</b>
                    {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $kota])
            </div>
        </div>
    </div>

    @can('kota.create')
        <div aria-labelledby="kota-create-modal-label" id="kota-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="kota-create-modal-label">
                            {{ __('Tambah Kota') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#kota-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.kota.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="kota-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kota-create-provinsi">
                                        {{ __('Provinsi') }}
                                    </label>
                                    <select class="form-input" id="kota-create-provinsi" name="provinsi_id">
                                        <option value="">{{ __('Pilih Provinsi') }}</option>
                                        @foreach ($provinsiOptions as $provinsiOption)
                                            <option value="{{ $provinsiOption->id }}"
                                                    @selected(old('_modal') === 'kota-create-modal' && (int) old('provinsi_id') === $provinsiOption->id)>
                                                {{ $provinsiOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'kota-create-modal' && $errors->has('provinsi_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('provinsi_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kota-create-code">
                                        {{ __('Kode Kota') }}
                                    </label>
                                    <input class="form-input" id="kota-create-code" name="code" type="text"
                                           value="{{ old('_modal') === 'kota-create-modal' ? old('code') : '' }}"/>
                                    @if (old('_modal') === 'kota-create-modal' && $errors->has('code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="kota-create-name">
                                        {{ __('Nama Kota') }}
                                    </label>
                                    <input class="form-input" id="kota-create-name" name="name" type="text"
                                           value="{{ old('_modal') === 'kota-create-modal' ? old('name') : '' }}"/>
                                    @if (old('_modal') === 'kota-create-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#kota-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('kota.update')
        @foreach ($kota as $item)
            <div aria-labelledby="kota-edit-modal-{{ $item->id }}-label" id="kota-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="kota-edit-modal-{{ $item->id }}-label">
                                {{ __('Ubah Kota') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#kota-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('master.kota.update', $item) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="kota-edit-modal-{{ $item->id }}">
                            <div class="p-4 overflow-y-auto">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kota-edit-provinsi-{{ $item->id }}">
                                            {{ __('Provinsi') }}
                                        </label>
                                        <select class="form-input" id="kota-edit-provinsi-{{ $item->id }}"
                                                name="provinsi_id">
                                            <option value="">{{ __('Pilih Provinsi') }}</option>
                                            @foreach ($provinsiOptions as $provinsiOption)
                                                <option value="{{ $provinsiOption->id }}"
                                                        @selected(old('_modal') === 'kota-edit-modal-'.$item->id ? (int) old('provinsi_id') === $provinsiOption->id : $item->provinsi_id === $provinsiOption->id)>
                                                    {{ $provinsiOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (old('_modal') === 'kota-edit-modal-'.$item->id && $errors->has('provinsi_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('provinsi_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kota-edit-code-{{ $item->id }}">
                                            {{ __('Kode Kota') }}
                                        </label>
                                        <input class="form-input" id="kota-edit-code-{{ $item->id }}" name="code"
                                               type="text"
                                               value="{{ old('_modal') === 'kota-edit-modal-'.$item->id ? old('code') : $item->code }}"/>
                                        @if (old('_modal') === 'kota-edit-modal-'.$item->id && $errors->has('code'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="kota-edit-name-{{ $item->id }}">
                                            {{ __('Nama Kota') }}
                                        </label>
                                        <input class="form-input" id="kota-edit-name-{{ $item->id }}" name="name"
                                               type="text"
                                               value="{{ old('_modal') === 'kota-edit-modal-'.$item->id ? old('name') : $item->name }}"/>
                                        @if (old('_modal') === 'kota-edit-modal-'.$item->id && $errors->has('name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#kota-edit-modal-{{ $item->id }}"
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

    @can('kota.delete')
        @foreach ($kota as $item)
            <div aria-labelledby="kota-delete-modal-{{ $item->id }}-label" id="kota-delete-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#kota-delete-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Kota ":name" akan dihapus permanen.', ['name' => $item->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#kota-delete-modal-{{ $item->id }}" type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('master.kota.destroy', $item) }}" method="POST">
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
    <script>
        document.getElementById('kota-sync-form')?.addEventListener('submit', function () {
            const button = document.getElementById('kota-sync-button');
            const icon = document.getElementById('kota-sync-icon');
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
            icon?.classList.add('animate-spin');
        });
    </script>
@endsection
