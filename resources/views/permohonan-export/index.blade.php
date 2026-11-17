@extends('layouts.vertical', ['title' => __('Permohonan SATS-LN Ekspor')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => __('Permohonan SATS-LN Ekspor')])

    <div class="card" data-active-year-page>
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Permohonan') }}</h6>
            @can('permohonan-export.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#permohonan-create-modal"
                        type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Permohonan Baru') }}
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nomor Permohonan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Jumlah SATS-LN') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Diajukan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($permohonan as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->nomor_permohonan }}</td>
                                    <td class="px-3.5 py-3">{{ $item->sats_ln_count }} / 25</td>
                                    <td class="px-3.5 py-3">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $item->status->badgeClass() }}">
                                            {{ $item->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3">{{ $item->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            <a class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                               href="{{ route('permohonan-export.show', $item) }}" aria-label="{{ __('Lihat') }}">
                                                <i class="size-4" data-lucide="eye"></i>
                                            </a>
                                            @can('permohonan-export.delete')
                                                @if ($item->status->isEditable())
                                                    <button aria-label="{{ __('Hapus') }}"
                                                            class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                            data-hs-overlay="#permohonan-delete-modal-{{ $item->id }}"
                                                            type="button">
                                                        <i class="size-4" data-lucide="trash-2"></i>
                                                    </button>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="5">
                                        {{ __('Belum ada permohonan.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $permohonan->count() }}</b> {{ __('dari') }}
                    <b>{{ $permohonan->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $permohonan])
            </div>
        </div>
    </div>

    @can('permohonan-export.create')
        <div aria-labelledby="permohonan-create-modal-label" id="permohonan-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="permohonan-create-modal-label">
                            {{ __('Permohonan Baru') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#permohonan-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('permohonan-export.store') }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="permohonan-create-modal">
                        <input data-active-year-input name="year" type="hidden" value="{{ old('year') }}">
                        {{-- Sengaja tanpa overflow-y-auto/max-h: itu meng-clip dropdown Choices.js, lihat claude/permohonan-task.md. --}}
                        <div class="p-4">
                            <div class="flex flex-col gap-y-4">
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="permohonan-create-nomor">
                                        {{ __('Nomor Permohonan (Otomatis)') }}
                                    </label>
                                    <input class="form-input" disabled id="permohonan-create-nomor" type="text"
                                           value="{{ $nomorPermohonanPreview }}">
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="permohonan-create-document">
                                        {{ __('Dokumen Permohonan') }}
                                    </label>
                                    <x-select :options="$documentOptions->map(fn ($document) => ['id' => $document->id, 'name' => $document->nomor_document])"
                                              id="permohonan-create-document" name="document_id"
                                              :placeholder="__('Pilih Dokumen Permohonan')" :selected="old('document_id')" required/>
                                    <p class="mt-1 text-xs text-default-400">
                                        {{ __('Hanya menampilkan dokumen bertipe Permohonan milik entitas Anda yang belum dikaitkan.') }}
                                    </p>
                                    @if ($errors->has('document_id'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('document_id') }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="inline-block mb-2 text-sm font-medium" for="permohonan-create-jenis">
                                        {{ __('Jenis Permohonan') }}
                                    </label>
                                    <x-select :options="collect($jenisPermohonanOptions)->map(fn ($case) => ['id' => $case->value, 'name' => $case->label()])"
                                              id="permohonan-create-jenis" name="jenis_permohonan" :selected="old('jenis_permohonan')" required/>
                                    @if ($errors->has('jenis_permohonan'))
                                        <p class="mt-1 text-xs text-danger">{{ $errors->first('jenis_permohonan') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#permohonan-create-modal"
                                    type="button">{{ __('Batal') }}</button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('permohonan-export.delete')
        @foreach ($permohonan as $item)
            @if ($item->status->isEditable())
                <div aria-labelledby="permohonan-delete-modal-{{ $item->id }}-label" id="permohonan-delete-modal-{{ $item->id }}"
                     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                     role="dialog" tabindex="-1">
                    <div
                        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                        <div
                            class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                            <div class="absolute top-3 end-3">
                                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                        data-hs-overlay="#permohonan-delete-modal-{{ $item->id }}" type="button">
                                    <span class="sr-only">{{ __('Tutup') }}</span>
                                    <i class="size-5" data-lucide="x"></i>
                                </button>
                            </div>
                            <div class="text-center">
                                <img alt="" class="size-12 mx-auto" src="{{ asset('images/delete.png') }}"/>
                                <div class="mt-5 text-center">
                                    <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                    <p class="text-default-500 text-sm font-normal">
                                        {{ __('Permohonan ini akan dihapus permanen.') }}
                                    </p>
                                    <div class="mt-5 flex gap-2 justify-center">
                                        <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                                data-hs-overlay="#permohonan-delete-modal-{{ $item->id }}"
                                                type="button">{{ __('Batal') }}</button>
                                        <form action="{{ route('permohonan-export.destroy', $item) }}" method="POST">
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
            @endif
        @endforeach
    @endcan
@endsection
