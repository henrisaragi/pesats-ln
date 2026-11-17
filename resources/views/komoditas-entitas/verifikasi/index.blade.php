@extends('layouts.vertical', ['title' => __('Verifikasi Komoditas Entitas')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => __('Verifikasi Komoditas')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Pengajuan Komoditas Entitas') }}</h6>
        </div>
        <div class="card-header">
            <form action="{{ route('komoditas-entitas.verifikasi.index') }}"
                  class="md:flex items-center md:space-y-0 space-y-4 gap-3" method="GET">
                <select class="form-input form-input-sm" name="status" onchange="this.form.submit()">
                    @foreach (\App\Enums\KomoditasEntitasStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($activeStatus === $status)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                    <option value="" @selected($activeStatus === null)>{{ __('Semua Status') }}</option>
                </select>
            </form>
        </div>
        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Entitas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Komoditas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nomor Sertifikat') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tanggal Terbit') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tanggal Berakhir') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Dokumen') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Ringkasan Cek') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($items as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->entitas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->komoditas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->nomor_sertifikat ?? '-' }}</td>
                                    <td class="px-3.5 py-3">{{ $item->tanggal_terbit?->format('d/m/Y') }}</td>
                                    <td class="px-3.5 py-3">{{ $item->tanggal_berakhir?->format('d/m/Y') }}</td>
                                    <td class="px-3.5 py-3">
                                        <button class="text-primary hover:underline inline-flex items-center gap-1"
                                                data-document-viewer-download-url="{{ route('komoditas-entitas.download', $item) }}"
                                                data-document-viewer-title="{{ $item->komoditas?->name }} — {{ $item->entitas?->name }}"
                                                data-document-viewer-url="{{ route('komoditas-entitas.preview', $item) }}"
                                                data-hs-overlay="#komoditas-entitas-document-modal" type="button">
                                            <i class="size-3.5" data-lucide="file-text"></i>{{ __('Lihat Dokumen') }}
                                        </button>
                                    </td>
                                    <td class="px-3.5 py-3">
                                        <x-komoditas-entitas.summary-cek :item="$item"/>
                                    </td>
                                    <td class="px-3.5 py-3">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $item->status->badgeClass() }}">
                                            {{ $item->status->label() }}
                                        </span>
                                        @if ($item->isVerifiedOtomatis())
                                            <span class="block mt-1 text-xs text-default-400">{{ __('Otomatis oleh sistem') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3.5 py-3">
                                        @if ($item->status === \App\Enums\KomoditasEntitasStatus::Pending)
                                            <div class="flex items-center gap-2">
                                                <button aria-label="{{ __('Verifikasi') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-success/10 hover:text-success text-default-500"
                                                        data-hs-overlay="#komoditas-entitas-verify-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="check"></i>
                                                </button>
                                                <button aria-label="{{ __('Tolak') }}"
                                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#komoditas-entitas-reject-modal-{{ $item->id }}"
                                                        type="button">
                                                    <i class="size-4" data-lucide="x"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="9">
                                        {{ __('Tidak ada pengajuan komoditas entitas.') }}
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

    @foreach ($items as $item)
        @if ($item->status === \App\Enums\KomoditasEntitasStatus::Pending)
            <div aria-labelledby="komoditas-entitas-verify-modal-{{ $item->id }}-label"
                 id="komoditas-entitas-verify-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#komoditas-entitas-verify-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Verifikasi Pengajuan?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Komoditas ":komoditas" milik ":entitas" akan ditandai terverifikasi.', ['komoditas' => $item->komoditas?->name, 'entitas' => $item->entitas?->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn btn-sm bg-default-150"
                                            data-hs-overlay="#komoditas-entitas-verify-modal-{{ $item->id }}"
                                            type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('komoditas-entitas.verifikasi.verify', $item) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-sm bg-success text-white border-0" type="submit">
                                            {{ __('Ya, Verifikasi') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div aria-labelledby="komoditas-entitas-reject-modal-{{ $item->id }}-label"
                 id="komoditas-entitas-reject-modal-{{ $item->id }}"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#komoditas-entitas-reject-modal-{{ $item->id }}" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="{{ asset('images/delete.png') }}"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Tolak Pengajuan?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Komoditas ":komoditas" milik ":entitas" akan ditandai ditolak.', ['komoditas' => $item->komoditas?->name, 'entitas' => $item->entitas?->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn btn-sm text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#komoditas-entitas-reject-modal-{{ $item->id }}"
                                            type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('komoditas-entitas.verifikasi.reject', $item) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-sm bg-danger text-white border-0" type="submit">
                                            {{ __('Ya, Tolak') }}
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

@endsection
