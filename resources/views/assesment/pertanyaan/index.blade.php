@extends('layouts.vertical', ['title' => __('Pertanyaan Assesment')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Master Data'), 'title' => $assesment->judul])

    <div class="mb-4">
        <a class="text-primary hover:underline inline-flex items-center gap-1" href="{{ route('assesment.index') }}">
            <i class="size-4" data-lucide="arrow-left"></i>{{ __('Kembali ke Daftar Assesment') }}
        </a>
    </div>

    @can('assesment.create')
        <div class="card mb-6">
            <div class="card-header">
                <h6 class="card-title">{{ __('Tambah Pertanyaan') }}</h6>
            </div>
            @include('assesment.pertanyaan._form', [
                'action' => route('assesment.pertanyaan.store', $assesment),
                'method' => 'POST',
                'submitLabel' => __('Simpan Pertanyaan'),
                'pertanyaan' => null,
            ])
        </div>
    @endcan

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Pertanyaan') }}</h6>
        </div>
        <div class="p-4 flex flex-col gap-y-4">
            @forelse ($assesment->pertanyaan as $item)
                <div class="border border-default-200 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs border-primary/30 bg-primary/10 text-primary mb-2">
                                {{ $item->aspek?->nama }}
                            </span>
                            <p class="font-medium text-default-800">{{ $item->pertanyaan }}</p>
                            @if ($item->informasi)
                                <p class="text-sm text-default-500 mt-1">{{ $item->informasi }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @can('assesment.update')
                                <a aria-label="{{ __('Edit') }}"
                                   class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                   href="{{ route('assesment.pertanyaan.edit', [$assesment, $item]) }}">
                                    <i class="size-4" data-lucide="pencil"></i>
                                </a>
                            @endcan
                            @can('assesment.delete')
                                <button aria-label="{{ __('Hapus') }}"
                                        class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                        data-hs-overlay="#pertanyaan-delete-modal-{{ $item->id }}" type="button">
                                    <i class="size-4" data-lucide="trash-2"></i>
                                </button>
                            @endcan
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($item->komoditas as $komoditas)
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs border-default-300 bg-default-150 text-default-600">
                                {{ $komoditas->name }} - {{ __('Bobot') }} {{ number_format((float) $komoditas->pivot->bobot, 2) }}%
                            </span>
                        @endforeach
                    </div>

                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-default-200 text-sm">
                            <thead>
                            <tr class="text-default-600 text-xs">
                                <th class="px-2 py-1 text-start">{{ __('Jawaban') }}</th>
                                @foreach ($item->komoditas as $komoditas)
                                    <th class="px-2 py-1 text-start">{{ $komoditas->name }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($item->jawaban as $jawaban)
                                <tr class="text-default-800">
                                    <td class="px-2 py-1">{{ $jawaban->teks_jawaban }}</td>
                                    @foreach ($item->komoditas as $komoditas)
                                        <td class="px-2 py-1">{{ $jawaban->skorUntukKomoditas($komoditas->id) ?? '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($item->buktiDukung->isNotEmpty())
                        <div class="mt-3 text-sm text-default-600">
                            <span class="font-medium">{{ __('Bukti Dukung') }}:</span>
                            {{ $item->buktiDukung->pluck('label')->implode(', ') }}
                        </div>
                    @endif
                </div>

                @can('assesment.delete')
                    <div aria-labelledby="pertanyaan-delete-modal-{{ $item->id }}-label" id="pertanyaan-delete-modal-{{ $item->id }}"
                         class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                         role="dialog" tabindex="-1">
                        <div
                            class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                            <div
                                class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                                <div class="absolute top-3 end-3">
                                    <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                            data-hs-overlay="#pertanyaan-delete-modal-{{ $item->id }}" type="button">
                                        <span class="sr-only">{{ __('Tutup') }}</span>
                                        <i class="size-5" data-lucide="x"></i>
                                    </button>
                                </div>
                                <div class="text-center">
                                    <img alt="" class="size-12 mx-auto" src="{{ asset('images/delete.png') }}"/>
                                    <div class="mt-5 text-center">
                                        <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                        <p class="text-default-500 text-sm font-normal">
                                            {{ __('Pertanyaan ini beserta jawaban dan bukti dukungnya akan dihapus permanen.') }}
                                        </p>
                                        <div class="mt-5 flex gap-2 justify-center">
                                            <button class="btn btn-sm bg-default-150"
                                                    data-hs-overlay="#pertanyaan-delete-modal-{{ $item->id }}" type="button">
                                                {{ __('Batal') }}
                                            </button>
                                            <form action="{{ route('assesment.pertanyaan.destroy', [$assesment, $item]) }}" method="POST">
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
            @empty
                <p class="text-center text-default-500 py-6">{{ __('Belum ada pertanyaan untuk assesment ini.') }}</p>
            @endforelse
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/assesment-pertanyaan-form.js'])
@endsection
