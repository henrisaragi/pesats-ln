@extends('layouts.vertical', ['title' => __('User')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Administrasi'), 'title' => __('User')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar User') }}</h6>
            @can('users.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#user-create-modal" type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah User') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <div class="md:flex items-center md:space-y-0 space-y-4 gap-3">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" id="users-search"
                           placeholder="{{ __('Cari nama, username, email') }}" type="text"/>
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3">
                        <i class="size-3.5 flex items-center text-default-500 fill-default-100"
                           data-lucide="search"></i>
                    </div>
                </div>
                @can('users.view-all')
                    <select class="form-input form-input-sm" id="users-filter-entity">
                        <option value="">{{ __('Semua Entitas') }}</option>
                        @foreach ($entityOptions as $entityOption)
                            <option value="{{ $entityOption->id }}">{{ $entityOption->name }}</option>
                        @endforeach
                    </select>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <x-data-table :ajax-url="route('users.data')" id="users-table">
                <x-slot:head>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Username') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Email') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Entitas') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Role') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('NIP') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tanggal Dibuat') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                </x-slot:head>
            </x-data-table>
        </div>
    </div>

    @can('users.create')
        <div aria-labelledby="user-create-modal-label" id="user-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="user-create-modal-label">
                            {{ __('Tambah User') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#user-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="user-create-modal">
                        @include('users.partials.form', ['prefix' => 'create'])
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#user-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('users.edit')
        <div aria-labelledby="user-edit-modal-label" id="user-edit-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="user-edit-modal-label">
                            {{ __('Ubah User') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#user-edit-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ old('_modal') === 'user-edit-modal' ? route('users.update', old('_modal_user_id')) : route('users.index') }}"
                          data-action-template="{{ route('users.update', ['user' => '__ID__']) }}" id="user-edit-form"
                          method="POST">
                        @csrf
                        @method('PUT')
                        <input name="_modal" type="hidden" value="user-edit-modal">
                        <input name="_modal_user_id" type="hidden" value="{{ old('_modal_user_id') }}">
                        @include('users.partials.form', ['prefix' => 'edit'])
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#user-edit-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('users.delete')
        <div aria-labelledby="user-delete-modal-label" id="user-delete-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div
                    class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                    <div class="absolute top-3 end-3">
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#user-delete-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <div class="text-center">
                        <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                        <div class="mt-5 text-center">
                            <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                            <p class="text-default-500 text-sm font-normal">
                                {{ __('User') }} "<span data-delete-name></span>" {{ __('akan dihapus permanen.') }}
                            </p>
                            <div class="mt-5 flex gap-2 justify-center">
                                <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                        data-hs-overlay="#user-delete-modal" type="button">
                                    {{ __('Batal') }}
                                </button>
                                <form action="{{ route('users.index') }}"
                                      data-action-template="{{ route('users.destroy', ['user' => '__ID__']) }}"
                                      id="user-delete-form" method="POST">
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
    @vite(['resources/js/pages/users-index.js'])
@endsection
