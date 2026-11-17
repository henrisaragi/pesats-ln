<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Pembagian Kuota Ekspor') }} {{ $period->year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #222; }
        h1 { font-size: 13pt; margin: 0 0 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #bbb; padding: 4px 5px; }
        th { background: #eee; text-align: left; }
        .r { text-align: right; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
    </style>
</head>
<body>
@php
    $num = fn ($value, int $decimals = 0): string => $value === null ? '-' : number_format((float) $value, $decimals, ',', '.');
    $snapshot = $period->latestSnapshot;
@endphp
<h1>{{ __('Pembagian Kuota Ekspor') }}</h1>
<p>{{ $period->label() }} · {{ __('Komoditas') }}: {{ $period->komoditas?->name }} · {{ __('Status') }}: {{ $period->status->label() }}</p>
<p class="muted">
    Q = {{ $num($period->kuota_tangkap) }}
    @if ($snapshot)
        · c = {{ $num((float) $snapshot->cadangan * 100, 2) }}% · Q_b = {{ $num($snapshot->kuota_dibagi) }}
        · ΣE ({{ $snapshot->tahun_realisasi }}) = {{ $num($snapshot->total_realisasi_ekspor) }} · ΣN = {{ $num($snapshot->total_nilai_evaluasi, 2) }}
        · {{ __('Bobot') }} {{ $num((float) $snapshot->bobot_ekspor * 100) }}/{{ $num((float) $snapshot->bobot_evaluasi * 100) }} · Snapshot v{{ $snapshot->version }}
    @endif
</p>
<table>
    <thead>
    <tr>
        <th>{{ __('Perusahaan') }}</th>
        <th class="r">{{ __('Permohonan') }}</th>
        <th class="r">E</th>
        <th class="r">N</th>
        <th class="r">{{ __('Rekomendasi') }}</th>
        <th class="r">{{ __('Ditetapkan') }}</th>
        <th>{{ __('Alasan Penyesuaian') }}</th>
        <th class="r">{{ __('Realisasi Ekspor') }}</th>
        <th>{{ __('SK Kuota Ekspor') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($period->allocations as $allocation)
        <tr>
            <td>{{ $allocation->entitas?->name }}</td>
            <td class="r">{{ $num($allocation->permohonan) }}</td>
            <td class="r">{{ $num($allocation->realisasi_ekspor) }}</td>
            <td class="r">{{ $num($allocation->nilai_evaluasi, 2) }}</td>
            <td class="r">{{ $num($allocation->rekomendasi_bulat) }}</td>
            <td class="r">{{ $num($allocation->ditetapkan) }}</td>
            <td>{{ $allocation->alasan_penyesuaian }}</td>
            <td class="r">{{ $num($allocation->realisasi_tangkap) }}</td>
            <td>{{ $allocation->sk_tangkap_nomor }} {{ $allocation->sk_tangkap_tanggal?->format('d/m/Y') }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="4">{{ __('Total') }}</td>
        <td class="r">{{ $num($period->allocations->sum('rekomendasi_bulat')) }}</td>
        <td class="r">{{ $num($period->allocations->sum(fn ($a) => (float) $a->ditetapkan)) }}</td>
        <td colspan="3">{{ __('Cadangan aktual') }}: {{ $num($cadanganAktual) }}</td>
    </tr>
    </tfoot>
</table>
<p class="muted">
    @if ($period->submitter){{ __('Diajukan oleh :user pada :tanggal', ['user' => $period->submitter->name, 'tanggal' => $period->submitted_at?->format('d/m/Y H:i')]) }}.@endif
    @if ($period->approver) {{ __('Ditetapkan oleh :user pada :tanggal', ['user' => $period->approver->name, 'tanggal' => $period->approved_at?->format('d/m/Y H:i')]) }}.@endif
    {{ __('Dicetak :tanggal', ['tanggal' => now()->format('d/m/Y H:i')]) }}
</p>
</body>
</html>
