@extends('layouts.vertical', ['title' => __('Role')])

@section('css')
@endsection

@php
    $permissionGroups = $permissions->groupBy(
        fn($permission) => \Illuminate\Support\Str::before($permission->name, '.'),
    );
@endphp

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Administrasi'), 'title' => __('Role')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Role') }}</h6>
            @can('roles.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#role-create-modal" type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Role') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <form action="{{ route('roles.index') }}" class="md:flex items-center md:space-y-0 space-y-4 gap-3"
                method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search" placeholder="{{ __('Cari nama role') }}"
                        type="text" value="{{ request('search') }}" />
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3">
                        <i class="size-3.5 flex items-center text-default-500 fill-default-100" data-lucide="search"></i>
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
                                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nama Role') }}</th>
                                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Jumlah Permission') }}</th>
                                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $role)
                                    <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                        <td class="px-3.5 py-3">{{ $role->name }}</td>
                                        <td class="px-3.5 py-3">
                                            <span
                                                class="py-0.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-medium bg-primary/10 text-primary rounded">
                                                {{ $role->permissions_count }}
                                            </span>
                                        </td>
                                        <td class="px-3.5 py-3">
                                            <div class="flex items-center gap-2">
                                                @can('roles.edit')
                                                    <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#role-edit-modal-{{ $role->id }}" type="button">
                                                        <i class="size-4" data-lucide="edit"></i>
                                                    </button>
                                                @endcan
                                                @can('roles.delete')
                                                    <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#role-delete-modal-{{ $role->id }}"
                                                        type="button">
                                                        <i class="size-4" data-lucide="trash-2"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-3.5 py-6 text-center text-default-500" colspan="3">
                                            {{ __('Belum ada role.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $roles->count() }}</b> {{ __('dari') }}
                    <b>{{ $roles->total() }}</b>
                    {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $roles])
            </div>
        </div>
    </div>

    @can('roles.create')
        <div aria-labelledby="role-create-modal-label" id="role-create-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
            role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 max-w-full size-full flex">
                <div class="w-full h-full flex flex-col card border-0 rounded-none pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="role-create-modal-label">
                            {{ __('Tambah Role') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                            data-hs-overlay="#role-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('roles.store') }}" class="flex flex-col flex-1 min-h-0" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="role-create-modal">
                        <div class="p-4 flex-1 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="role-create-name">
                                        {{ __('Nama Role') }}
                                    </label>
                                    <input class="form-input" id="role-create-name" name="name"
                                        placeholder="{{ __('Masukkan Nama Role') }}" type="text"
                                        value="{{ old('_modal') === 'role-create-modal' ? old('name') : '' }}" />
                                    @if (old('_modal') === 'role-create-modal' && $errors->has('name'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium">{{ __('Permission') }}</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                        @foreach ($permissionGroups as $group => $groupPermissions)
                                            <div>
                                                <h6 class="mb-2 text-xs font-semibold uppercase text-default-500">
                                                    {{ $group }}
                                                </h6>
                                                @foreach ($groupPermissions as $permission)
                                                    <div class="flex items-center gap-2 mb-1.5">
                                                        <input class="form-checkbox"
                                                            id="role-create-permission-{{ $permission->id }}"
                                                            name="permissions[]" type="checkbox"
                                                            value="{{ $permission->id }}" />
                                                        <label class="text-sm"
                                                            for="role-create-permission-{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#role-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('roles.edit')
        @foreach ($roles as $role)
            <div aria-labelledby="role-edit-modal-{{ $role->id }}-label" id="role-edit-modal-{{ $role->id }}"
                class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 max-w-full size-full flex">
                    <div class="w-full h-full flex flex-col card border-0 rounded-none pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="role-edit-modal-{{ $role->id }}-label">
                                {{ __('Ubah Role') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#role-edit-modal-{{ $role->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('roles.update', $role) }}" class="flex flex-col flex-1 min-h-0" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="role-edit-modal-{{ $role->id }}">
                            <div class="p-4 flex-1 overflow-y-auto">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                            for="role-edit-name-{{ $role->id }}">
                                            {{ __('Nama Role') }}
                                        </label>
                                        <input class="form-input" id="role-edit-name-{{ $role->id }}" name="name"
                                            type="text"
                                            value="{{ old('_modal') === 'role-edit-modal-' . $role->id ? old('name') : $role->name }}" />
                                        @if (old('_modal') === 'role-edit-modal-' . $role->id && $errors->has('name'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium">
                                            {{ __('Permission') }}
                                        </label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                            @foreach ($permissionGroups as $group => $groupPermissions)
                                                <div>
                                                    <h6 class="mb-2 text-xs font-semibold uppercase text-default-500">
                                                        {{ $group }}
                                                    </h6>
                                                    @foreach ($groupPermissions as $permission)
                                                        <div class="flex items-center gap-2 mb-1.5">
                                                            <input class="form-checkbox"
                                                                id="role-edit-permission-{{ $role->id }}-{{ $permission->id }}"
                                                                name="permissions[]" type="checkbox"
                                                                value="{{ $permission->id }}" @checked($role->permissions->contains('id', $permission->id)) />
                                                            <label class="text-sm"
                                                                for="role-edit-permission-{{ $role->id }}-{{ $permission->id }}">
                                                                {{ $permission->name }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#role-edit-modal-{{ $role->id }}"
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

    @can('roles.delete')
        @foreach ($roles as $role)
            <div aria-labelledby="role-delete-modal-{{ $role->id }}-label" id="role-delete-modal-{{ $role->id }}"
                class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#role-delete-modal-{{ $role->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="{{ asset('images/delete.png') }}" />
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">
                                    {{ __('Anda yakin?') }}
                                </h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Role ":name" akan dihapus permanen.', ['name' => $role->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                        data-hs-overlay="#role-delete-modal-{{ $role->id }}" type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST">
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
