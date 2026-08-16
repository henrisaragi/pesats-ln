@extends('layouts.vertical', ['title' => __('Dokumen')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Dokumen'), 'title' => __('Dokumen')])

    <div class="card" data-documents-page>
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Dokumen') }}</h6>
            @can('documents.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#document-create-modal"
                        type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Dokumen') }}
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nomor Dokumen') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tipe Dokumen') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Perusahaan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Permohonan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('SATS-LN') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tanggal Terbit') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Negara Penerbit') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Dokumen') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($documents as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->nomor_document }}</td>
                                    <td class="px-3.5 py-3">{{ $item->type_document->label() }}</td>
                                    <td class="px-3.5 py-3">{{ $item->entitas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->permohonan?->nomor_permohonan ?? '-' }}</td>
                                    <td class="px-3.5 py-3">{{ $item->satsLn->pluck('satsln_number_issued')->join(', ') ?: '-' }}</td>
                                    <td class="px-3.5 py-3">{{ $item->issued_date?->translatedFormat('d M Y') }}</td>
                                    <td class="px-3.5 py-3">{{ $item->issuedCountry?->name }}</td>
                                    <td class="px-3.5 py-3">
                                        <button class="text-primary hover:underline inline-flex items-center gap-1"
                                                data-document-viewer-download-url="{{ route('documents.download', $item) }}"
                                                data-document-viewer-url="{{ route('documents.preview', $item) }}"
                                                data-document-viewer-title="{{ $item->nomor_document }}"
                                                data-hs-overlay="#document-viewer-modal" type="button">
                                            <i class="size-3.5" data-lucide="file-text"></i>{{ __('Lihat Dokumen') }}
                                        </button>
                                    </td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('documents.update')
                                                <button aria-label="{{ __('Ubah') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#document-edit-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('documents.delete')
                                                <button aria-label="{{ __('Hapus') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#document-delete-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="9">
                                        {{ __('Belum ada data dokumen.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $documents->count() }}</b> {{ __('dari') }}
                    <b>{{ $documents->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $documents])
            </div>
        </div>
    </div>

    <x-document-viewer-modal id="document-viewer-modal"/>

    @can('documents.create')
        <div aria-labelledby="document-create-modal-label" id="document-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="document-create-modal-label">
                            {{ __('Tambah Dokumen') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#document-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('documents.store') }}" enctype="multipart/form-data" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="document-create-modal">
                        @include('documents.partials.form-fields', ['document' => null, 'modalId' => 'document-create-modal'])
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#document-create-modal"
                                    type="button">{{ __('Batal') }}</button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('documents.update')
        @foreach ($documents as $item)
            <div aria-labelledby="document-edit-modal-{{ $item->id }}-label" id="document-edit-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="document-edit-modal-{{ $item->id }}-label">
                                {{ __('Ubah Dokumen') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#document-edit-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('documents.update', $item) }}" enctype="multipart/form-data" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="document-edit-modal-{{ $item->id }}">
                            @include('documents.partials.form-fields', ['document' => $item, 'modalId' => 'document-edit-modal-'.$item->id])
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#document-edit-modal-{{ $item->id }}"
                                        type="button">{{ __('Batal') }}</button>
                                <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endcan

    @can('documents.delete')
        @foreach ($documents as $item)
            <div aria-labelledby="document-delete-modal-{{ $item->id }}-label" id="document-delete-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#document-delete-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Dokumen ":nomor" akan dihapus permanen.', ['nomor' => $item->nomor_document]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#document-delete-modal-{{ $item->id }}"
                                            type="button">{{ __('Batal') }}</button>
                                    <form action="{{ route('documents.destroy', $item) }}" method="POST">
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
    @vite(['resources/js/pages/documents-index.js'])
@endsection
