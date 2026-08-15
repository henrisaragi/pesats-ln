@extends('layouts.vertical', ['title' => __('Kode Perdagangan')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Master Data'), 'title' => __('Kode Perdagangan')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Kode Perdagangan') }}</h6>
            @can('trade-codes.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#trade-code-create-modal"
                        type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Kode Perdagangan') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <form action="{{ route('master.trade-codes.index') }}" class="md:flex items-center md:space-y-0 space-y-4 gap-3"
                  method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search" placeholder="{{ __('Cari nama kode') }}"
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Satuan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Satuan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Desimal') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Deskripsi') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($tradeCodes as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->unit }}</td>
                                    <td class="px-3.5 py-3">{{ $item->unit_name }}</td>
                                    <td class="px-3.5 py-3">
                                        {{ $item->decimal_places === 0 ? __('Bilangan bulat') : __(':n koma', ['n' => $item->decimal_places]) }}
                                        @if ($item->rounding_enabled)
                                            <span class="text-default-400">({{ __('dibulatkan') }})</span>
                                        @endif
                                    </td>
                                    <td class="px-3.5 py-3">{{ $item->description }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('trade-codes.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#trade-code-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('trade-codes.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#trade-code-delete-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="7">
                                        {{ __('Belum ada data kode perdagangan.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $tradeCodes->count() }}</b> {{ __('dari') }}
                    <b>{{ $tradeCodes->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $tradeCodes])
            </div>
        </div>
    </div>

    @can('trade-codes.create')
        <div aria-labelledby="trade-code-create-modal-label" id="trade-code-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="trade-code-create-modal-label">
                            {{ __('Tambah Kode Perdagangan') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#trade-code-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.trade-codes.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="trade-code-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="trade-code-create-code">
                                        {{ __('Kode') }}
                                    </label>
                                    <input class="form-input" id="trade-code-create-code" name="code" type="text"
                                           value="{{ old('_modal') === 'trade-code-create-modal' ? old('code') : '' }}"/>
                                    @if (old('_modal') === 'trade-code-create-modal' && $errors->has('code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="trade-code-create-name">
                                        {{ __('Nama') }}
                                    </label>
                                    <input class="form-input" id="trade-code-create-name" name="name" type="text"
                                           value="{{ old('_modal') === 'trade-code-create-modal' ? old('name') : '' }}"/>
                                    @if (old('_modal') === 'trade-code-create-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="trade-code-create-unit">
                                        {{ __('Satuan') }}
                                    </label>
                                    <input class="form-input" id="trade-code-create-unit" name="unit" type="text"
                                           value="{{ old('_modal') === 'trade-code-create-modal' ? old('unit') : '' }}"/>
                                    @if (old('_modal') === 'trade-code-create-modal' && $errors->has('unit'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('unit') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="trade-code-create-unit-name">
                                        {{ __('Nama Satuan') }}
                                    </label>
                                    <input class="form-input" id="trade-code-create-unit-name" name="unit_name"
                                           type="text"
                                           value="{{ old('_modal') === 'trade-code-create-modal' ? old('unit_name') : '' }}"/>
                                    @if (old('_modal') === 'trade-code-create-modal' && $errors->has('unit_name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('unit_name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="trade-code-create-decimal-places">
                                        {{ __('Jumlah Desimal') }}
                                    </label>
                                    <select class="form-input" data-choice id="trade-code-create-decimal-places"
                                            name="decimal_places">
                                        @foreach ([0, 1, 2, 3, 4] as $option)
                                            <option value="{{ $option }}"
                                                    @selected((old('_modal') === 'trade-code-create-modal' ? (int) old('decimal_places') : 2) === $option)>
                                                {{ $option === 0 ? __('Tanpa desimal (bilangan bulat)') : __(':n angka di belakang koma', ['n' => $option]) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'trade-code-create-modal' && $errors->has('decimal_places'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('decimal_places') }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <input class="form-checkbox" id="trade-code-create-rounding" name="rounding_enabled"
                                           type="checkbox" value="1"
                                           @checked(old('_modal') === 'trade-code-create-modal' ? old('rounding_enabled') : true)/>
                                    <label class="text-sm font-medium" for="trade-code-create-rounding">
                                        {{ __('Aktifkan pembulatan') }}
                                    </label>
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="trade-code-create-description">
                                        {{ __('Deskripsi') }}
                                    </label>
                                    <textarea class="form-input" id="trade-code-create-description"
                                              name="description" rows="3">{{ old('_modal') === 'trade-code-create-modal' ? old('description') : '' }}</textarea>
                                    @if (old('_modal') === 'trade-code-create-modal' && $errors->has('description'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('description') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#trade-code-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('trade-codes.update')
        @foreach ($tradeCodes as $item)
            <div aria-labelledby="trade-code-edit-modal-{{ $item->id }}-label" id="trade-code-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base"
                                id="trade-code-edit-modal-{{ $item->id }}-label">
                                {{ __('Ubah Kode Perdagangan') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#trade-code-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('master.trade-codes.update', $item) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="trade-code-edit-modal-{{ $item->id }}">
                            <div class="p-4 overflow-y-auto">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="trade-code-edit-code-{{ $item->id }}">
                                            {{ __('Kode') }}
                                        </label>
                                        <input class="form-input" id="trade-code-edit-code-{{ $item->id }}" name="code"
                                               type="text"
                                               value="{{ old('_modal') === 'trade-code-edit-modal-'.$item->id ? old('code') : $item->code }}"/>
                                        @if (old('_modal') === 'trade-code-edit-modal-'.$item->id && $errors->has('code'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="trade-code-edit-name-{{ $item->id }}">
                                            {{ __('Nama') }}
                                        </label>
                                        <input class="form-input" id="trade-code-edit-name-{{ $item->id }}" name="name"
                                               type="text"
                                               value="{{ old('_modal') === 'trade-code-edit-modal-'.$item->id ? old('name') : $item->name }}"/>
                                        @if (old('_modal') === 'trade-code-edit-modal-'.$item->id && $errors->has('name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="trade-code-edit-unit-{{ $item->id }}">
                                            {{ __('Satuan') }}
                                        </label>
                                        <input class="form-input" id="trade-code-edit-unit-{{ $item->id }}" name="unit"
                                               type="text"
                                               value="{{ old('_modal') === 'trade-code-edit-modal-'.$item->id ? old('unit') : $item->unit }}"/>
                                        @if (old('_modal') === 'trade-code-edit-modal-'.$item->id && $errors->has('unit'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('unit') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="trade-code-edit-unit-name-{{ $item->id }}">
                                            {{ __('Nama Satuan') }}
                                        </label>
                                        <input class="form-input" id="trade-code-edit-unit-name-{{ $item->id }}"
                                               name="unit_name" type="text"
                                               value="{{ old('_modal') === 'trade-code-edit-modal-'.$item->id ? old('unit_name') : $item->unit_name }}"/>
                                        @if (old('_modal') === 'trade-code-edit-modal-'.$item->id && $errors->has('unit_name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('unit_name') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="trade-code-edit-decimal-places-{{ $item->id }}">
                                            {{ __('Jumlah Desimal') }}
                                        </label>
                                        <select class="form-input" data-choice
                                                id="trade-code-edit-decimal-places-{{ $item->id }}"
                                                name="decimal_places">
                                            @foreach ([0, 1, 2, 3, 4] as $option)
                                                <option value="{{ $option }}"
                                                        @selected((old('_modal') === 'trade-code-edit-modal-'.$item->id ? (int) old('decimal_places') : $item->decimal_places) === $option)>
                                                    {{ $option === 0 ? __('Tanpa desimal (bilangan bulat)') : __(':n angka di belakang koma', ['n' => $option]) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (old('_modal') === 'trade-code-edit-modal-'.$item->id && $errors->has('decimal_places'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('decimal_places') }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input class="form-checkbox" id="trade-code-edit-rounding-{{ $item->id }}"
                                               name="rounding_enabled" type="checkbox" value="1"
                                               @checked(old('_modal') === 'trade-code-edit-modal-'.$item->id ? old('rounding_enabled') : $item->rounding_enabled)/>
                                        <label class="text-sm font-medium" for="trade-code-edit-rounding-{{ $item->id }}">
                                            {{ __('Aktifkan pembulatan') }}
                                        </label>
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="trade-code-edit-description-{{ $item->id }}">
                                            {{ __('Deskripsi') }}
                                        </label>
                                        <textarea class="form-input" id="trade-code-edit-description-{{ $item->id }}"
                                                  name="description"
                                                  rows="3">{{ old('_modal') === 'trade-code-edit-modal-'.$item->id ? old('description') : $item->description }}</textarea>
                                        @if (old('_modal') === 'trade-code-edit-modal-'.$item->id && $errors->has('description'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('description') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#trade-code-edit-modal-{{ $item->id }}"
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

    @can('trade-codes.delete')
        @foreach ($tradeCodes as $item)
            <div aria-labelledby="trade-code-delete-modal-{{ $item->id }}-label" id="trade-code-delete-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#trade-code-delete-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Kode perdagangan ":name" akan dihapus permanen.', ['name' => $item->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#trade-code-delete-modal-{{ $item->id }}" type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('master.trade-codes.destroy', $item) }}" method="POST">
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
