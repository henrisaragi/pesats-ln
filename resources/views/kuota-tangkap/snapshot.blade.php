@extends('layouts.vertical', ['title' => __('Snapshot Perhitungan')])

@php
    $num = fn ($value, int $decimals = 0): string => $value === null ? '-' : number_format((float) $value, $decimals, ',', '.');
@endphp

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Pembagian Kuota Ekspor'), 'title' => __('Snapshot Perhitungan v:version', ['version' => $snapshot->version])])

    <div class="card">
        <div class="card-header">
            <div>
                <h6 class="card-title">{{ $period->label() }}</h6>
                <p class="text-xs text-default-500 mt-1">{{ __('Dihitung oleh :user pada :tanggal', ['user' => $snapshot->calculator?->name ?? '-', 'tanggal' => $snapshot->created_at?->format('d/m/Y H:i')]) }}</p>
            </div>
            <a class="btn btn-sm bg-default-150" href="{{ route('kuota-tangkap.show', [$period, 'tab' => 'snapshot']) }}"><i class="size-4 me-1" data-lucide="arrow-left"></i>{{ __('Kembali') }}</a>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-4">
                @foreach ([
                    ['Q', $num($snapshot->kuota_tangkap)],
                    [__('Cadangan (c)'), $num((float) $snapshot->cadangan * 100, 2).'%'],
                    [__('Bobot Ekspor / Evaluasi'), $num((float) $snapshot->bobot_ekspor * 100).'% / '.$num((float) $snapshot->bobot_evaluasi * 100).'%'],
                    ['Q_b', $num($snapshot->kuota_dibagi)],
                    [__('ΣE (:tahun)', ['tahun' => $snapshot->tahun_realisasi]), $num($snapshot->total_realisasi_ekspor)],
                    ['ΣN', $num($snapshot->total_nilai_evaluasi, 2)],
                ] as [$label, $value])
                    <div class="rounded-lg border border-default-200 p-3">
                        <p class="text-xs text-default-500">{{ $label }}</p>
                        <p class="text-lg font-semibold text-default-800">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
            @foreach ($snapshot->warningMessages() as $warning)
                <div class="rounded-lg border border-warning/30 bg-warning/10 text-warning text-sm p-3 mb-2">{{ $warning }}</div>
            @endforeach
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-default-200">
                <thead class="bg-default-150">
                <tr class="text-sm text-default-700 whitespace-nowrap">
                    <th class="px-3.5 py-3 text-start">{{ __('Perusahaan') }}</th>
                    <th class="px-3.5 py-3 text-end">{{ __('Permohonan') }}</th>
                    <th class="px-3.5 py-3 text-end">E</th>
                    <th class="px-3.5 py-3 text-end">N</th>
                    <th class="px-3.5 py-3 text-start">{{ __('Assessment diverifikasi') }}</th>
                    <th class="px-3.5 py-3 text-end">{{ __('Komp. Ekspor') }}</th>
                    <th class="px-3.5 py-3 text-end">{{ __('Komp. Evaluasi') }}</th>
                    <th class="px-3.5 py-3 text-end">{{ __('Rekomendasi') }}</th>
                    <th class="px-3.5 py-3 text-end">{{ __('Bulat') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($snapshot->rows as $row)
                    <tr class="text-sm text-default-800 whitespace-nowrap">
                        <td class="px-3.5 py-3">{{ $row['nama'] }}</td>
                        <td class="px-3.5 py-3 text-end">{{ $num($row['permohonan']) }}</td>
                        <td class="px-3.5 py-3 text-end">{{ $num($row['realisasi_ekspor']) }}</td>
                        <td class="px-3.5 py-3 text-end">{{ $num($row['nilai_evaluasi'], 2) }}</td>
                        <td class="px-3.5 py-3">{{ $row['assesment_verified_at'] ?? '-' }}</td>
                        <td class="px-3.5 py-3 text-end">{{ $num($row['komponen_ekspor'], 4) }}</td>
                        <td class="px-3.5 py-3 text-end">{{ $num($row['komponen_evaluasi'], 4) }}</td>
                        <td class="px-3.5 py-3 text-end">{{ $num($row['rekomendasi'], 4) }}</td>
                        <td class="px-3.5 py-3 text-end font-semibold">{{ $num($row['rekomendasi_bulat']) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="bg-default-100 text-sm font-semibold">
                <tr>
                    <td class="px-3.5 py-3" colspan="7">{{ __('Total') }}</td>
                    <td class="px-3.5 py-3 text-end">{{ $num(collect($snapshot->rows)->sum(fn ($r) => (float) $r['rekomendasi']), 4) }}</td>
                    <td class="px-3.5 py-3 text-end">{{ $num(collect($snapshot->rows)->sum('rekomendasi_bulat')) }}</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
