@extends('layouts.vertical', ['title' => 'Negara'])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Master Data', 'title' => 'Negara'])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Negara') }}</h6>
            @can('countries.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#country-create-modal" type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Negara') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <form action="{{ route('master.countries.index') }}" class="md:flex items-center md:space-y-0 space-y-4 gap-3"
                  method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search" placeholder="{{ __('Cari nama negara') }}"
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Negara') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kode ISO (2)') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kode ISO (3)') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kode Telepon') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($countries as $country)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $country->name }}</td>
                                    <td class="px-3.5 py-3">{{ $country->iso_code }}</td>
                                    <td class="px-3.5 py-3">{{ $country->iso_code3 }}</td>
                                    <td class="px-3.5 py-3">{{ $country->phone_code }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('countries.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#country-edit-modal-{{ $country->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('countries.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#country-delete-modal-{{ $country->id }}"
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
                                        {{ __('Belum ada data negara.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $countries->count() }}</b> {{ __('dari') }}
                    <b>{{ $countries->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $countries])
            </div>
        </div>
    </div>

    @can('countries.create')
        <div aria-labelledby="country-create-modal-label" id="country-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="country-create-modal-label">
                            {{ __('Tambah Negara') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#country-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.countries.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="country-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="country-create-name">
                                        {{ __('Nama Negara') }}
                                    </label>
                                    <input class="form-input" id="country-create-name" name="name" type="text"
                                           value="{{ old('_modal') === 'country-create-modal' ? old('name') : '' }}"/>
                                    @if (old('_modal') === 'country-create-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="country-create-iso-code">
                                        {{ __('Kode ISO (2 huruf)') }}
                                    </label>
                                    <input class="form-input" id="country-create-iso-code" maxlength="2" name="iso_code"
                                           placeholder="{{ __('contoh: ID') }}" type="text"
                                           value="{{ old('_modal') === 'country-create-modal' ? old('iso_code') : '' }}"/>
                                    @if (old('_modal') === 'country-create-modal' && $errors->has('iso_code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('iso_code') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="country-create-iso-code3">
                                        {{ __('Kode ISO (3 huruf)') }}
                                    </label>
                                    <input class="form-input" id="country-create-iso-code3" maxlength="3"
                                           name="iso_code3" placeholder="{{ __('contoh: IDN') }}" type="text"
                                           value="{{ old('_modal') === 'country-create-modal' ? old('iso_code3') : '' }}"/>
                                    @if (old('_modal') === 'country-create-modal' && $errors->has('iso_code3'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('iso_code3') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="country-create-phone-code">
                                        {{ __('Kode Telepon') }}
                                    </label>
                                    <input class="form-input" id="country-create-phone-code" name="phone_code"
                                           placeholder="{{ __('contoh: +62') }}" type="text"
                                           value="{{ old('_modal') === 'country-create-modal' ? old('phone_code') : '' }}"/>
                                    @if (old('_modal') === 'country-create-modal' && $errors->has('phone_code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('phone_code') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#country-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('countries.update')
        @foreach ($countries as $country)
            <div aria-labelledby="country-edit-modal-{{ $country->id }}-label" id="country-edit-modal-{{ $country->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base"
                                id="country-edit-modal-{{ $country->id }}-label">
                                {{ __('Ubah Negara') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#country-edit-modal-{{ $country->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('master.countries.update', $country) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="country-edit-modal-{{ $country->id }}">
                            <div class="p-4 overflow-y-auto">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="country-edit-name-{{ $country->id }}">
                                            {{ __('Nama Negara') }}
                                        </label>
                                        <input class="form-input" id="country-edit-name-{{ $country->id }}" name="name"
                                               type="text"
                                               value="{{ old('_modal') === 'country-edit-modal-'.$country->id ? old('name') : $country->name }}"/>
                                        @if (old('_modal') === 'country-edit-modal-'.$country->id && $errors->has('name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="country-edit-iso-code-{{ $country->id }}">
                                            {{ __('Kode ISO (2 huruf)') }}
                                        </label>
                                        <input class="form-input" id="country-edit-iso-code-{{ $country->id }}"
                                               maxlength="2" name="iso_code" type="text"
                                               value="{{ old('_modal') === 'country-edit-modal-'.$country->id ? old('iso_code') : $country->iso_code }}"/>
                                        @if (old('_modal') === 'country-edit-modal-'.$country->id && $errors->has('iso_code'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('iso_code') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="country-edit-iso-code3-{{ $country->id }}">
                                            {{ __('Kode ISO (3 huruf)') }}
                                        </label>
                                        <input class="form-input" id="country-edit-iso-code3-{{ $country->id }}"
                                               maxlength="3" name="iso_code3" type="text"
                                               value="{{ old('_modal') === 'country-edit-modal-'.$country->id ? old('iso_code3') : $country->iso_code3 }}"/>
                                        @if (old('_modal') === 'country-edit-modal-'.$country->id && $errors->has('iso_code3'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('iso_code3') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="country-edit-phone-code-{{ $country->id }}">
                                            {{ __('Kode Telepon') }}
                                        </label>
                                        <input class="form-input" id="country-edit-phone-code-{{ $country->id }}"
                                               name="phone_code" type="text"
                                               value="{{ old('_modal') === 'country-edit-modal-'.$country->id ? old('phone_code') : $country->phone_code }}"/>
                                        @if (old('_modal') === 'country-edit-modal-'.$country->id && $errors->has('phone_code'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('phone_code') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#country-edit-modal-{{ $country->id }}"
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

    @can('countries.delete')
        @foreach ($countries as $country)
            <div aria-labelledby="country-delete-modal-{{ $country->id }}-label" id="country-delete-modal-{{ $country->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#country-delete-modal-{{ $country->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Negara ":name" akan dihapus permanen.', ['name' => $country->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#country-delete-modal-{{ $country->id }}" type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('master.countries.destroy', $country) }}" method="POST">
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
