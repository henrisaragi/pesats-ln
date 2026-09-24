@extends('layouts.vertical', ['title' => __('Verifikasi Assesment')])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => __('Verifikasi Assesment')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Pengajuan Assesment') }}</h6>
        </div>
        <div class="card-header">
            <form action="{{ route('assesment-isi.verifikasi.index') }}"
                  class="md:flex items-center md:space-y-0 space-y-4 gap-3" method="GET">
                <select class="form-input form-input-sm" name="status" onchange="this.form.submit()">
                    @foreach (\App\Enums\AssesmentIsiStatus::cases() as $status)
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Assesment') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Komoditas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tanggal Submit') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Skor Jawaban Entitas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Skor Hasil Verifikasi') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($items as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->entitas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->assesment?->judul }}</td>
                                    <td class="px-3.5 py-3">{{ $item->komoditas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->submitted_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3.5 py-3">{{ $item->skor_entitas !== null ? number_format((float) $item->skor_entitas, 2) : '-' }}</td>
                                    <td class="px-3.5 py-3">{{ $item->skor_total !== null ? number_format((float) $item->skor_total, 2) : '-' }}</td>
                                    <td class="px-3.5 py-3">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $item->status->badgeClass() }}">
                                            {{ $item->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3">
                                        <a class="btn btn-sm bg-default-150" href="{{ route('assesment-isi.verifikasi.show', $item) }}">
                                            {{ __('Detail') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="8">
                                        {{ __('Tidak ada pengajuan assesment.') }}
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
@endsection

@section('scripts')

@endsection
