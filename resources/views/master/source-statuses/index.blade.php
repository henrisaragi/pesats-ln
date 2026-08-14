@extends('layouts.vertical', ['title' => 'Status Asal'])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Master Data', 'title' => 'Status Asal'])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Status Asal') }}</h6>
            @can('source-statuses.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#source-status-create-modal"
                        type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Status Asal') }}
                </button>
            @endcan
        </div>
        <div class="card-header">
            <form action="{{ route('master.source-statuses.index') }}"
                  class="md:flex items-center md:space-y-0 space-y-4 gap-3" method="GET">
                <div class="relative">
                    <input class="form-input form-input-sm ps-9" name="search" placeholder="{{ __('Cari deskripsi') }}"
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Deskripsi') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($sourceStatuses as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->description }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('source-statuses.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#source-status-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('source-statuses.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#source-status-delete-modal-{{ $item->id }}"
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
                                        {{ __('Belum ada data status asal.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $sourceStatuses->count() }}</b> {{ __('dari') }}
                    <b>{{ $sourceStatuses->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $sourceStatuses])
            </div>
        </div>
    </div>

    @can('source-statuses.create')
        <div aria-labelledby="source-status-create-modal-label" id="source-status-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="source-status-create-modal-label">
                            {{ __('Tambah Status Asal') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#source-status-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.source-statuses.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="source-status-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="source-status-create-code">
                                        {{ __('Kode') }}
                                    </label>
                                    <input class="form-input" id="source-status-create-code" maxlength="5" name="code"
                                           type="text"
                                           value="{{ old('_modal') === 'source-status-create-modal' ? old('code') : '' }}"/>
                                    @if (old('_modal') === 'source-status-create-modal' && $errors->has('code'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="source-status-create-description">
                                        {{ __('Deskripsi') }}
                                    </label>
                                    <textarea class="form-input" id="source-status-create-description"
                                              name="description" rows="3">{{ old('_modal') === 'source-status-create-modal' ? old('description') : '' }}</textarea>
                                    @if (old('_modal') === 'source-status-create-modal' && $errors->has('description'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('description') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#source-status-create-modal"
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

    @can('source-statuses.update')
        @foreach ($sourceStatuses as $item)
            <div aria-labelledby="source-status-edit-modal-{{ $item->id }}-label"
                 id="source-status-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base"
                                id="source-status-edit-modal-{{ $item->id }}-label">
                                {{ __('Ubah Status Asal') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#source-status-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('master.source-statuses.update', $item) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="source-status-edit-modal-{{ $item->id }}">
                            <div class="p-4 overflow-y-auto">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="source-status-edit-code-{{ $item->id }}">
                                            {{ __('Kode') }}
                                        </label>
                                        <input class="form-input" id="source-status-edit-code-{{ $item->id }}"
                                               maxlength="5" name="code" type="text"
                                               value="{{ old('_modal') === 'source-status-edit-modal-'.$item->id ? old('code') : $item->code }}"/>
                                        @if (old('_modal') === 'source-status-edit-modal-'.$item->id && $errors->has('code'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('code') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="source-status-edit-description-{{ $item->id }}">
                                            {{ __('Deskripsi') }}
                                        </label>
                                        <textarea class="form-input" id="source-status-edit-description-{{ $item->id }}"
                                                  name="description"
                                                  rows="3">{{ old('_modal') === 'source-status-edit-modal-'.$item->id ? old('description') : $item->description }}</textarea>
                                        @if (old('_modal') === 'source-status-edit-modal-'.$item->id && $errors->has('description'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('description') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150"
                                        data-hs-overlay="#source-status-edit-modal-{{ $item->id }}" type="button">
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

    @can('source-statuses.delete')
        @foreach ($sourceStatuses as $item)
            <div aria-labelledby="source-status-delete-modal-{{ $item->id }}-label"
                 id="source-status-delete-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#source-status-delete-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Status asal ":name" akan dihapus permanen.', ['name' => $item->code]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#source-status-delete-modal-{{ $item->id }}" type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('master.source-statuses.destroy', $item) }}" method="POST">
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
