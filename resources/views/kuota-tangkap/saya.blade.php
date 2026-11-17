@extends('layouts.vertical', ['title' => __('Kuota Ekspor Saya')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => __('Kuota Ekspor Saya')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Kuota Ekspor yang Ditetapkan') }}</h6>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-default-200">
                <thead class="bg-default-150">
                <tr class="text-sm text-default-700 whitespace-nowrap">
                    <th class="px-3.5 py-3 text-start">{{ __('Tahun') }}</th>
                    <th class="px-3.5 py-3 text-start">{{ __('Provinsi') }}</th>
                    <th class="px-3.5 py-3 text-start">{{ __('Jenis TSL') }}</th>
                    <th class="px-3.5 py-3 text-start">{{ __('Kode Perdagangan') }}</th>
                    <th class="px-3.5 py-3 text-end">{{ __('Kuota Ditetapkan') }}</th>
                    <th class="px-3.5 py-3 text-end">{{ __('Realisasi Ekspor') }}</th>
                    <th class="px-3.5 py-3 text-start">{{ __('SK Kuota Ekspor') }}</th>
                    <th class="px-3.5 py-3 text-start">{{ __('Status') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($allocations as $allocation)
                    <tr class="text-sm text-default-800 whitespace-nowrap">
                        <td class="px-3.5 py-3">{{ $allocation->period->year }}</td>
                        <td class="px-3.5 py-3">{{ $allocation->period->provinsi?->name }}</td>
                        <td class="px-3.5 py-3 italic">{{ $allocation->period->species?->scientific_name }}</td>
                        <td class="px-3.5 py-3">{{ $allocation->period->tradeCode?->name }}</td>
                        <td class="px-3.5 py-3 text-end font-semibold">{{ number_format((float) $allocation->ditetapkan, 0, ',', '.') }}</td>
                        <td class="px-3.5 py-3 text-end">{{ $allocation->realisasi_tangkap !== null ? number_format((float) $allocation->realisasi_tangkap, 0, ',', '.') : '-' }}</td>
                        <td class="px-3.5 py-3">
                            {{ $allocation->sk_tangkap_nomor ?? '-' }}
                            @if ($allocation->sk_tangkap_path)
                                <a class="text-primary text-xs ms-1" href="{{ route('kuota-tangkap.realisasi.sk', [$allocation->period, $allocation]) }}">{{ __('Unduh') }}</a>
                            @endif
                        </td>
                        <td class="px-3.5 py-3"><span class="inline-flex items-center rounded-full px-2.5 py-0.75 border text-xs {{ $allocation->period->status->badgeClass() }}">{{ $allocation->period->status->label() }}</span></td>
                    </tr>
                @empty
                    <tr><td class="px-3.5 py-6 text-center text-default-500 text-sm" colspan="8">{{ __('Belum ada kuota ekspor yang ditetapkan untuk perusahaan Anda.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            @include('layouts.partials/pagination', ['paginator' => $allocations])
        </div>
    </div>
@endsection
