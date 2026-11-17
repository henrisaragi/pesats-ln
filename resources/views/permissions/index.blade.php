@extends('layouts.vertical', ['title' => __('Permission')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Administrasi'), 'title' => __('Permission')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Permission') }}</h6>
            @can('permissions.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#permission-create-modal"
                        type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Permission') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <form action="{{ route('permissions.index') }}" class="md:flex items-center md:space-y-0 space-y-4 gap-3"
                  method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search"
                           placeholder="{{ __('Cari nama permission') }}" type="text" value="{{ request('search') }}"/>
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Permission') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($permissions as $permission)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $permission->name }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('permissions.edit')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#permission-edit-modal-{{ $permission->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('permissions.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#permission-delete-modal-{{ $permission->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="2">
                                        {{ __('Belum ada permission.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $permissions->count() }}</b> {{ __('dari') }}
                    <b>{{ $permissions->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $permissions])
            </div>
        </div>
    </div>

    @can('permissions.create')
        <div aria-labelledby="permission-create-modal-label" id="permission-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="permission-create-modal-label">
                            {{ __('Tambah Permission') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#permission-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('permissions.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="permission-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="lg:col-span-12">
                                <label class="inline-block mb-2 text-sm font-medium" for="permission-create-name">
                                    {{ __('Nama Permission') }}
                                </label>
                                <input class="form-input" id="permission-create-name" name="name"
                                       placeholder="{{ __('contoh: entities.view') }}" type="text"
                                       value="{{ old('_modal') === 'permission-create-modal' ? old('name') : '' }}"/>
                                @if (old('_modal') === 'permission-create-modal' && $errors->has('name'))
                                    <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#permission-create-modal"
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

    @can('permissions.edit')
        @foreach ($permissions as $permission)
            <div aria-labelledby="permission-edit-modal-{{ $permission->id }}-label"
                 id="permission-edit-modal-{{ $permission->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base"
                                id="permission-edit-modal-{{ $permission->id }}-label">
                                {{ __('Ubah Permission') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#permission-edit-modal-{{ $permission->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('permissions.update', $permission) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="permission-edit-modal-{{ $permission->id }}">
                            <div class="p-4 overflow-y-auto">
                                <div class="lg:col-span-12">
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="permission-edit-name-{{ $permission->id }}">
                                        {{ __('Nama Permission') }}
                                    </label>
                                    <input class="form-input" id="permission-edit-name-{{ $permission->id }}"
                                           name="name" type="text"
                                           value="{{ old('_modal') === 'permission-edit-modal-'.$permission->id ? old('name') : $permission->name }}"/>
                                    @if (old('_modal') === 'permission-edit-modal-'.$permission->id && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150"
                                        data-hs-overlay="#permission-edit-modal-{{ $permission->id }}" type="button">
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

    @can('permissions.delete')
        @foreach ($permissions as $permission)
            <div aria-labelledby="permission-delete-modal-{{ $permission->id }}-label"
                 id="permission-delete-modal-{{ $permission->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#permission-delete-modal-{{ $permission->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="{{ asset('images/delete.png') }}"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">
                                    {{ __('Anda yakin?') }}
                                </h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Permission ":name" akan dihapus permanen.', ['name' => $permission->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#permission-delete-modal-{{ $permission->id }}"
                                            type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('permissions.destroy', $permission) }}" method="POST">
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
