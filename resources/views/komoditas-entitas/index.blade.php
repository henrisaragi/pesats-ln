@extends('layouts.vertical', ['title' => __('Komoditas Entitas')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => __('Komoditas Entitas')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Komoditas Entitas') }}</h6>
            @can('komoditas-entitas.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#komoditas-entitas-create-modal"
                        type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Ajukan Komoditas') }}
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
                                @if ($canViewAll)
                                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Entitas') }}</th>
                                @endif
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Komoditas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nomor Sertifikat') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tanggal Terbit') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tanggal Berakhir') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Dokumen') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($items as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    @if ($canViewAll)
                                        <td class="px-3.5 py-3">{{ $item->entitas?->name }}</td>
                                    @endif
                                    <td class="px-3.5 py-3">{{ $item->komoditas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->nomor_sertifikat ?? '-' }}</td>
                                    <td class="px-3.5 py-3">{{ $item->tanggal_terbit?->format('d/m/Y') }}</td>
                                    <td class="px-3.5 py-3">{{ $item->tanggal_berakhir?->format('d/m/Y') }}</td>
                                    <td class="px-3.5 py-3">
                                        <button class="text-primary hover:underline inline-flex items-center gap-1"
                                                data-document-viewer-download-url="{{ route('komoditas-entitas.download', $item) }}"
                                                data-document-viewer-title="{{ $item->komoditas?->name }}"
                                                data-document-viewer-url="{{ route('komoditas-entitas.preview', $item) }}"
                                                data-hs-overlay="#komoditas-entitas-document-modal" type="button">
                                            <i class="size-3.5" data-lucide="file-text"></i>{{ __('Lihat Dokumen') }}
                                        </button>
                                    </td>
                                    <td class="px-3.5 py-3">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $item->status->badgeClass() }}">
                                            {{ $item->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3">
                                        @if ($item->status === \App\Enums\KomoditasEntitasStatus::Rejected && $item->entitas_id === auth()->user()->entity_id)
                                            <div class="flex items-center gap-2">
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#komoditas-entitas-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#komoditas-entitas-delete-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="{{ $canViewAll ? 8 : 7 }}">
                                        {{ __('Belum ada komoditas entitas yang diajukan.') }}
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

    <x-document-viewer-modal id="komoditas-entitas-document-modal"/>

    @can('komoditas-entitas.create')
        <div aria-labelledby="komoditas-entitas-create-modal-label" id="komoditas-entitas-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="komoditas-entitas-create-modal-label">
                            {{ __('Ajukan Komoditas Entitas') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#komoditas-entitas-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('komoditas-entitas.store') }}" enctype="multipart/form-data" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="komoditas-entitas-create-modal">
                        {{-- Sengaja tanpa overflow-y-auto/max-h: itu meng-clip dropdown Choices.js, lihat claude/permohonan-task.md. --}}
                        <div class="p-4">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="komoditas-entitas-create-komoditas">
                                        {{ __('Komoditas') }}
                                    </label>
                                    <select class="form-input" data-choice id="komoditas-entitas-create-komoditas"
                                            name="komoditas_id">
                                        <option value="">{{ __('Pilih Komoditas') }}</option>
                                        @foreach ($komoditasOptions as $option)
                                            <option value="{{ $option->id }}"
                                                    @selected(old('_modal') === 'komoditas-entitas-create-modal' && (int) old('komoditas_id') === $option->id)>
                                                {{ $option->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (old('_modal') === 'komoditas-entitas-create-modal' && $errors->has('komoditas_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('komoditas_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="komoditas-entitas-create-nomor-sertifikat">
                                        {{ __('Nomor Sertifikat Standar') }}
                                    </label>
                                    <input class="form-input" id="komoditas-entitas-create-nomor-sertifikat"
                                           name="nomor_sertifikat" type="text"
                                           value="{{ old('_modal') === 'komoditas-entitas-create-modal' ? old('nomor_sertifikat') : '' }}"/>
                                    @if (old('_modal') === 'komoditas-entitas-create-modal' && $errors->has('nomor_sertifikat'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('nomor_sertifikat') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="komoditas-entitas-create-tanggal-terbit">
                                        {{ __('Tanggal Terbit') }}
                                    </label>
                                    <input class="form-input" data-komoditas-entitas-tanggal-terbit
                                           id="komoditas-entitas-create-tanggal-terbit" name="tanggal_terbit"
                                           type="date"
                                           value="{{ old('_modal') === 'komoditas-entitas-create-modal' ? old('tanggal_terbit') : '' }}"/>
                                    <p class="mt-1 text-xs text-default-500">
                                        {{ __('Tanggal berakhir otomatis 5 tahun dari tanggal terbit') }}
                                        <span data-komoditas-entitas-tanggal-berakhir-preview></span>
                                    </p>
                                    @if (old('_modal') === 'komoditas-entitas-create-modal' && $errors->has('tanggal_terbit'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('tanggal_terbit') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium"
                                           for="komoditas-entitas-create-file">
                                        {{ __('Dokumen Pendukung (PDF)') }}
                                    </label>
                                    <input accept="application/pdf" class="form-input"
                                           id="komoditas-entitas-create-file" name="file" type="file"/>
                                    @if (old('_modal') === 'komoditas-entitas-create-modal' && $errors->has('file'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('file') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#komoditas-entitas-create-modal"
                                    type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Ajukan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @foreach ($items as $item)
        @if ($item->status === \App\Enums\KomoditasEntitasStatus::Rejected && $item->entitas_id === auth()->user()->entity_id)
            <div aria-labelledby="komoditas-entitas-edit-modal-{{ $item->id }}-label"
                 id="komoditas-entitas-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base"
                                id="komoditas-entitas-edit-modal-{{ $item->id }}-label">
                                {{ __('Ubah Pengajuan Komoditas Entitas') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#komoditas-entitas-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('komoditas-entitas.update', $item) }}" enctype="multipart/form-data"
                              method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="komoditas-entitas-edit-modal-{{ $item->id }}">
                            {{-- Sengaja tanpa overflow-y-auto/max-h: itu meng-clip dropdown Choices.js, lihat claude/permohonan-task.md. --}}
                            <div class="p-4">
                                <div class="flex flex-col gap-y-4">
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="komoditas-entitas-edit-komoditas-{{ $item->id }}">
                                            {{ __('Komoditas') }}
                                        </label>
                                        <select class="form-input" data-choice
                                                id="komoditas-entitas-edit-komoditas-{{ $item->id }}"
                                                name="komoditas_id">
                                            <option value="">{{ __('Pilih Komoditas') }}</option>
                                            @foreach ($komoditasOptions as $option)
                                                <option value="{{ $option->id }}"
                                                        @selected(old('_modal') === 'komoditas-entitas-edit-modal-'.$item->id ? (int) old('komoditas_id') === $option->id : $item->komoditas_id === $option->id)>
                                                    {{ $option->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (old('_modal') === 'komoditas-entitas-edit-modal-'.$item->id && $errors->has('komoditas_id'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('komoditas_id') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="komoditas-entitas-edit-nomor-sertifikat-{{ $item->id }}">
                                            {{ __('Nomor Sertifikat Standar') }}
                                        </label>
                                        <input class="form-input" id="komoditas-entitas-edit-nomor-sertifikat-{{ $item->id }}"
                                               name="nomor_sertifikat" type="text"
                                               value="{{ old('_modal') === 'komoditas-entitas-edit-modal-'.$item->id ? old('nomor_sertifikat') : $item->nomor_sertifikat }}"/>
                                        @if (old('_modal') === 'komoditas-entitas-edit-modal-'.$item->id && $errors->has('nomor_sertifikat'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('nomor_sertifikat') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="komoditas-entitas-edit-tanggal-terbit-{{ $item->id }}">
                                            {{ __('Tanggal Terbit') }}
                                        </label>
                                        <input class="form-input" data-komoditas-entitas-tanggal-terbit
                                               id="komoditas-entitas-edit-tanggal-terbit-{{ $item->id }}"
                                               name="tanggal_terbit" type="date"
                                               value="{{ old('_modal') === 'komoditas-entitas-edit-modal-'.$item->id ? old('tanggal_terbit') : $item->tanggal_terbit?->format('Y-m-d') }}"/>
                                        <p class="mt-1 text-xs text-default-500">
                                            {{ __('Tanggal berakhir otomatis 5 tahun dari tanggal terbit') }}
                                            <span data-komoditas-entitas-tanggal-berakhir-preview></span>
                                        </p>
                                        @if (old('_modal') === 'komoditas-entitas-edit-modal-'.$item->id && $errors->has('tanggal_terbit'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('tanggal_terbit') }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="komoditas-entitas-edit-file-{{ $item->id }}">
                                            {{ __('Dokumen Pendukung (PDF)') }}
                                        </label>
                                        <input accept="application/pdf" class="form-input"
                                               id="komoditas-entitas-edit-file-{{ $item->id }}" name="file"
                                               type="file"/>
                                        <p class="mt-1 text-xs text-default-500">
                                            {{ __('Kosongkan jika tidak ingin mengganti dokumen yang sudah ada.') }}
                                        </p>
                                        @if (old('_modal') === 'komoditas-entitas-edit-modal-'.$item->id && $errors->has('file'))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first('file') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150"
                                        data-hs-overlay="#komoditas-entitas-edit-modal-{{ $item->id }}" type="button">
                                    {{ __('Batal') }}
                                </button>
                                <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div aria-labelledby="komoditas-entitas-delete-modal-{{ $item->id }}-label"
                 id="komoditas-entitas-delete-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#komoditas-entitas-delete-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Pengajuan komoditas ":komoditas" yang ditolak akan dihapus permanen.', ['komoditas' => $item->komoditas?->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn btn-sm bg-default-150"
                                            data-hs-overlay="#komoditas-entitas-delete-modal-{{ $item->id }}"
                                            type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('komoditas-entitas.destroy', $item) }}" method="POST">
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
        @endif
    @endforeach
@endsection

@section('scripts')
    @vite(['resources/js/pages/komoditas-entitas-index.js'])
@endsection
