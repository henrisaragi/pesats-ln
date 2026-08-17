@extends('layouts.vertical', ['title' => __('Permohonan SATS-LN Ekspor')])

@section('css')
@endsection

@section('content')
    @include('layouts.partials/page-title', [
        'subtitle' => __('Layanan Entitas'),
        'title' => $permohonan->nomor_permohonan,
    ])

    <div class="grid lg:grid-cols-4 grid-cols-1 gap-5">
        <div class="lg:col-span-1 col-span-1 card print:hidden flex flex-col">
            <div class="sticky top-24 flex flex-col h-full">
                <div class="card-body shrink-0">
                    <h6 class="mb-4 card-title">{{ __('Daftar SATS-LN') }}</h6>
                    <div class="flex items-center gap-2">
                        <div class="relative w-full">
                            <input class="ps-11 form-input form-input-sm" placeholder="{{ __('Cari...') }}" type="text" />
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3">
                                <i class="size-4 flex items-center fill-default-100 text-default-500"
                                    data-lucide="search"></i>
                            </div>
                        </div>
                        @can('permohonan-export.edit')
                            @if ($permohonan->status->isEditable())
                                <button class="btn size-7.5 bg-primary text-white shrink-0"
                                    data-hs-overlay="#satsln-create-modal" type="button"
                                    aria-label="{{ __('Tambah SATS-LN') }}">
                                    <i class="size-4" data-lucide="plus"></i>
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
                <div class="overflow-y-auto flex-1" data-simplebar="">
                    @forelse ($permohonan->satsLn as $satsLn)
                        @php
                            $isSigned = $satsLn->signed_at !== null;
                            $isPrinted = $satsLn->printed_at !== null;
                            $satsLnStatusLabel = $isSigned
                                ? __('Ditandatangani')
                                : ($isPrinted
                                    ? __('Dicetak')
                                    : __('Preview'));
                            $satsLnStatusClass = $isSigned
                                ? 'inline-flex items-center gap-x-1.5 py-0.5 px-2.5 rounded text-xs font-medium bg-success/10 text-success'
                                : ($isPrinted
                                    ? 'inline-flex items-center gap-x-1.5 py-0.5 px-2.5 rounded text-xs font-medium bg-primary/10 text-primary'
                                    : 'text-warning bg-warning/20 rounded text-xs py-0.5 px-2.5');
                        @endphp
                        <div
                            class="relative border-t p-5 border-default-200 hover:bg-default-50 transition-colors {{ $selectedSatsLn?->id === $satsLn->id ? 'bg-default-100' : '' }}">
                            <a class="absolute inset-0"
                                href="{{ route('permohonan-export.sats-ln.show', [$permohonan, $satsLn]) }}"
                                aria-label="{{ __('Lihat detail SATS-LN Ke #:nomor', ['nomor' => $loop->iteration]) }}"></a>
                            <div class="flex justify-between">
                                <div class="w-full">
                                    <h6 class="font-semibold text-sm text-default-700">
                                        {{ __('SATS-LN Ke #:nomor', ['nomor' => $loop->iteration]) }}
                                    </h6>
                                    <div>
                                        <div class="flex items-center justify-between gap-6 mt-3">
                                            <span class="inline-flex text-sm items-center">
                                                <i class="size-4 text-default-500 me-1.5" data-lucide="calendar-clock"></i>
                                                {{ $satsLn->publish_date?->translatedFormat('d M Y') ?? __('Menunggu...') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative z-10 flex flex-col items-end gap-2">
                                    <span class="{{ $satsLnStatusClass }}">{{ $satsLnStatusLabel }}</span>
                                    @can('permohonan-export.edit')
                                        @if ($permohonan->status->isEditable())
                                            <button class="text-default-500 hover:text-primary" type="button"
                                                data-hs-overlay="#satsln-edit-modal-{{ $satsLn->id }}"
                                                aria-label="{{ __('Ubah') }}">
                                                <i class="size-3.5" data-lucide="edit"></i>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="p-5 text-sm text-default-500">{{ __('Belum ada SATS-LN.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="lg:col-span-3 col-span-1">
            @if ($selectedSatsLn)
                {{-- Navbar aksi SATS-LN - Edit SATS-LN/Lampiran Dokumen sementara pakai url="#"
                     (placeholder, belum ada halaman/aksi di baliknya). Tambah Spesies dan Hapus
                     sudah fungsional. --}}
                @can('permohonan-export.edit')
                    @if ($permohonan->status->isEditable())
                        <nav class="card mb-4">
                            <div class="card-body flex flex-wrap items-center gap-2">
                                <a class="btn btn-sm bg-default-150 hover:bg-default-200" href="#">
                                    <i class="size-4 me-1" data-lucide="edit"></i>{{ __('Edit SATS-LN') }}
                                </a>
                                <button class="btn btn-sm bg-default-150 hover:bg-default-200"
                                        data-hs-overlay="#satsln-item-create-modal" type="button">
                                    <i class="size-4 me-1" data-lucide="leaf"></i>{{ __('Tambah Spesies') }}
                                </button>
                                <a class="btn btn-sm bg-default-150 hover:bg-default-200" href="#">
                                    <i class="size-4 me-1" data-lucide="paperclip"></i>{{ __('Lampiran Dokumen') }}
                                </a>
                                <button class="btn btn-sm bg-danger text-white hover:bg-danger/90 ms-auto"
                                        data-hs-overlay="#satsln-delete-modal-{{ $selectedSatsLn->id }}" type="button">
                                    <i class="size-4 me-1" data-lucide="trash-2"></i>{{ __('Hapus') }}
                                </button>
                            </div>
                        </nav>
                    @endif
                @endcan
                <div class="card">
                    @include('permohonan-export.satsln', [
                        'satsLn' => $selectedSatsLn,
                        'permohonan' => $permohonan,
                        'realisasiNasionalRunningTotalByItemId' => $realisasiNasionalRunningTotalByItemId,
                    ])
                </div>
            @else
                <div class="card h-full flex items-center justify-center p-10 text-center text-default-500">
                    {{ __('Belum ada SATS-LN. Tambahkan SATS-LN pertama lewat tombol + di sebelah kiri.') }}
                </div>
            @endif
        </div>
    </div>

    @can('permohonan-export.edit')
        @if ($permohonan->status->isEditable() && $selectedSatsLn)
            <div aria-labelledby="satsln-item-create-modal-label" id="satsln-item-create-modal"
                class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none [--overlay-backdrop:static]"
                data-hs-overlay-keyboard="false" role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-2xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="satsln-item-create-modal-label">
                                {{ __('Tambah Spesies') }}
                            </h3>
                        </div>
                        @include('permohonan-export.partials.sats-ln-item-modal-form', [
                            'satsLn' => $selectedSatsLn,
                            'item' => null,
                            'modalId' => 'satsln-item-create-modal',
                        ])
                    </div>
                </div>
            </div>

            @foreach ($selectedSatsLn->items as $satsLnItem)
                <div aria-labelledby="satsln-item-edit-modal-{{ $satsLnItem->id }}-label" id="satsln-item-edit-modal-{{ $satsLnItem->id }}"
                    class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none [--overlay-backdrop:static]"
                    data-hs-overlay-keyboard="false" role="dialog" tabindex="-1">
                    <div
                        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-2xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                        <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                            <div class="card-header">
                                <h3 class="font-bold text-default-800 text-base" id="satsln-item-edit-modal-{{ $satsLnItem->id }}-label">
                                    {{ __('Ubah Spesies') }}
                                </h3>
                            </div>
                            @include('permohonan-export.partials.sats-ln-item-modal-form', [
                                'satsLn' => $selectedSatsLn,
                                'item' => $satsLnItem,
                                'modalId' => 'satsln-item-edit-modal-'.$satsLnItem->id,
                            ])
                        </div>
                    </div>
                </div>
            @endforeach

            <div aria-labelledby="satsln-delete-modal-{{ $selectedSatsLn->id }}-label" id="satsln-delete-modal-{{ $selectedSatsLn->id }}"
                class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#satsln-delete-modal-{{ $selectedSatsLn->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('SATS-LN ini akan dihapus permanen.') }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#satsln-delete-modal-{{ $selectedSatsLn->id }}"
                                            type="button">{{ __('Batal') }}</button>
                                    <form action="{{ route('permohonan-export.sats-ln.destroy', [$permohonan, $selectedSatsLn]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input name="return_to" type="hidden" value="show">
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
    @endcan

    @can('permohonan-export.edit')
        @if ($permohonan->status->isEditable())
            <div aria-labelledby="satsln-create-modal-label" id="satsln-create-modal"
                class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none [--overlay-backdrop:static]"
                data-hs-overlay-keyboard="false" role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-6xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="satsln-create-modal-label">
                                {{ __('Tambah SATS-LN') }}
                            </h3>
                        </div>
                        @include('permohonan-export.partials.sats-ln-modal-form', [
                            'satsLn' => null,
                            'modalId' => 'satsln-create-modal',
                        ])
                    </div>
                </div>
            </div>

            @foreach ($permohonan->satsLn as $satsLn)
                <div aria-labelledby="satsln-edit-modal-{{ $satsLn->id }}-label" id="satsln-edit-modal-{{ $satsLn->id }}"
                    class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none [--overlay-backdrop:static]"
                    data-hs-overlay-keyboard="false" role="dialog" tabindex="-1">
                    <div
                        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-6xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                        <div
                            class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                            <div class="card-header">
                                <h3 class="font-bold text-default-800 text-base"
                                    id="satsln-edit-modal-{{ $satsLn->id }}-label">
                                    {{ __('Ubah SATS-LN') }}
                                </h3>
                            </div>
                            @include('permohonan-export.partials.sats-ln-modal-form', [
                                'satsLn' => $satsLn,
                                'modalId' => 'satsln-edit-modal-' . $satsLn->id,
                            ])
                        </div>
                    </div>
                </div>
            @endforeach

            @include('permohonan-export.partials.customer-quick-create-modal')
        @endif
    @endcan
@endsection

@section('scripts')
    @vite(['resources/js/pages/permohonan-show.js'])
@endsection
