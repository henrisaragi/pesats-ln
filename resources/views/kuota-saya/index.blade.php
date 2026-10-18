@extends('layouts.vertical', ['title' => __('Kuota Saya')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => __('Kuota Saya')])

    <div class="card" data-active-year-page>
        <div class="card-header">
            <h6 class="card-title">
                {{ __('Kuota :entitas', ['entitas' => $entity->name]) }}
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border border-primary/30 bg-primary/10 text-primary text-xs ms-1">{{ __('Tahun :year', ['year' => $activeYear]) }}</span>
            </h6>
        </div>
        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Komoditas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Spesies') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status Asal') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kode Perdagangan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Satuan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kuota Saya / Nasional') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Tahun') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($kuotaPerusahaan as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->kuotaNasional?->komoditas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kuotaNasional?->species?->scientific_name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kuotaNasional?->source?->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kuotaNasional?->tradeCode?->code }}</td>
                                    <td class="px-3.5 py-3">{{ $item->kuotaNasional?->tradeCode?->unit_name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->quotaRatioLabel() }}</td>
                                    <td class="px-3.5 py-3">{{ $item->year }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="7">
                                        {{ __('Belum ada kuota nasional yang dialokasikan untuk entitas Anda pada tahun ini.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $kuotaPerusahaan->count() }}</b> {{ __('dari') }}
                    <b>{{ $kuotaPerusahaan->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $kuotaPerusahaan])
            </div>
        </div>
    </div>
@endsection
