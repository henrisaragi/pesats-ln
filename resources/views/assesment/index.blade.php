@extends('layouts.vertical', ['title' => __('Assesment')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Master Data'), 'title' => __('Assesment')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Assesment') }}</h6>
            @can('assesment.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#assesment-create-modal" type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Assesment') }}
                </button>
            @endcan
        </div>
        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Judul Assesment') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Jumlah Pertanyaan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($items as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->judul }}</td>
                                    <td class="px-3.5 py-3">{{ $item->pertanyaan_count }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('assesment.view')
                                                <a aria-label="{{ __('Kelola Pertanyaan') }}"
                                                   class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                   href="{{ route('assesment.pertanyaan.index', $item) }}">
                                                    <i class="size-4" data-lucide="list-checks"></i>
                                                </a>
                                            @endcan
                                            @can('assesment.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#assesment-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('assesment.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#assesment-delete-modal-{{ $item->id }}"
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
                                        {{ __('Belum ada assesment.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $items->count() }}</b> {{ __('dari') }}
                    <b>{{ $items->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $items])
            </div>
        </div>
    </div>

    @can('assesment.create')
        <div aria-labelledby="assesment-create-modal-label" id="assesment-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="assesment-create-modal-label">
                            {{ __('Tambah Assesment') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#assesment-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('assesment.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="assesment-create-modal">
                        <div class="p-4">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="assesment-create-judul">
                                        {{ __('Judul Assesment') }}
                                    </label>
                                    <input class="form-input" id="assesment-create-judul" name="judul" type="text"
                                           value="{{ old('_modal') === 'assesment-create-modal' ? old('judul') : '' }}"/>
                                    @if (old('_modal') === 'assesment-create-modal' && $errors->has('judul'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('judul') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#assesment-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @foreach ($items as $item)
        @can('assesment.update')
            <div aria-labelledby="assesment-edit-modal-{{ $item->id }}-label" id="assesment-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="assesment-edit-modal-{{ $item->id }}-label">
                                {{ __('Ubah Assesment') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#assesment-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('assesment.update', $item) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="assesment-edit-modal-{{ $item->id }}">
                            <div class="p-4">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="assesment-edit-judul-{{ $item->id }}">
                                            {{ __('Judul Assesment') }}
                                        </label>
                                        <input class="form-input" id="assesment-edit-judul-{{ $item->id }}" name="judul"
                                               type="text"
                                               value="{{ old('_modal') === 'assesment-edit-modal-'.$item->id ? old('judul') : $item->judul }}"/>
                                        @if (old('_modal') === 'assesment-edit-modal-'.$item->id && $errors->has('judul'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('judul') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#assesment-edit-modal-{{ $item->id }}"
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

        @can('assesment.delete')
            <div aria-labelledby="assesment-delete-modal-{{ $item->id }}-label" id="assesment-delete-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#assesment-delete-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Assesment ":judul" akan dihapus permanen beserta seluruh pertanyaannya.', ['judul' => $item->judul]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn btn-sm bg-default-150"
                                            data-hs-overlay="#assesment-delete-modal-{{ $item->id }}" type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('assesment.destroy', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm bg-danger text-white border-0" type="submit">
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
    @endforeach
@endsection

@section('scripts')

@endsection
