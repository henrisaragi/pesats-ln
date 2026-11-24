@extends('layouts.vertical', ['title' => __('Assesment')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => __('Assesment')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Assesment Tersedia') }}</h6>
        </div>
        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Judul Assesment') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Komoditas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Skor Jawaban Anda') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Skor Hasil Verifikasi') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status Kelayakan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($rows as $row)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $row['assesment']->judul }}</td>
                                    <td class="px-3.5 py-3">{{ $row['komoditas']->name }}</td>
                                    <td class="px-3.5 py-3">
                                        @if ($row['isi'])
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $row['isi']->status->badgeClass() }}">
                                                {{ $row['isi']->status->label() }}
                                            </span>
                                        @else
                                            <span class="text-default-400 text-xs">{{ __('Belum diikuti') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3.5 py-3">
                                        {{ ($row['hasil']['entitas'] ?? null) !== null ? number_format($row['hasil']['entitas'], 2).'%' : '-' }}
                                    </td>
                                    <td class="px-3.5 py-3">
                                        {{ ($row['hasil']['verifikasi'] ?? null) !== null ? number_format($row['hasil']['verifikasi'], 2).'%' : '-' }}
                                    </td>
                                    <td class="px-3.5 py-3">
                                        @include('assesment-isi.partials.kelayakan', ['isi' => $row['isi'], 'hasil' => $row['hasil']])
                                    </td>
                                    <td class="px-3.5 py-3">
                                        @if ($row['isi'])
                                            <a class="btn btn-sm bg-default-150" href="{{ route('assesment-isi.show', $row['isi']) }}">
                                                @if ($row['isi']->status->value === 'draft')
                                                    {{ __('Lanjutkan') }}
                                                @else
                                                    {{ __('Lihat') }}
                                                @endif
                                            </a>
                                        @else
                                            <button class="btn btn-sm bg-primary text-white" data-assesment-ikuti
                                                    data-assesment-id="{{ $row['assesment']->id }}"
                                                    data-hs-overlay="#assesment-ikuti-modal"
                                                    data-komoditas-id="{{ $row['komoditas']->id }}" type="button">
                                                {{ __('Ikuti Assesment') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="7">
                                        {{ __('Belum ada assesment yang tersedia untuk komoditas terverifikasi entitas Anda.') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('assesment-isi.partials.ikuti-modal')
    <x-document-viewer-modal id="assesment-panduan-viewer-modal"/>
@endsection

@section('scripts')
    @vite(['resources/js/pages/assesment-isi-index.js'])
@endsection
