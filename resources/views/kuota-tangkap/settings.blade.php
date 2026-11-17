@extends('layouts.vertical', ['title' => __('Pengaturan Kuota Ekspor')])

@php
    $pct = fn ($fraction): string => rtrim(rtrim(bcmul((string) $fraction, '100', 2), '0'), '.');
@endphp

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Database Kuota'), 'title' => __('Pengaturan Kuota Ekspor')])

    <div class="grid lg:grid-cols-3 gap-5">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h6 class="card-title">{{ __('Bobot & Cadangan (berlaku untuk semua komoditas)') }}</h6>
            </div>
            <form action="{{ route('kuota-tangkap.settings.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body grid md:grid-cols-3 gap-4">
                    @foreach ([
                        'bobot_ekspor' => [__('Bobot Ekspor (%)'), __('Porsi berdasarkan realisasi ekspor tahun sebelumnya.')],
                        'bobot_evaluasi' => [__('Bobot Evaluasi (%)'), __('Porsi berdasarkan Total Nilai assessment.')],
                        'cadangan' => [__('Cadangan (%)'), __('Disisihkan di awal: Q_b = floor(Q × (1 − c)).')],
                    ] as $field => [$label, $help])
                        <div>
                            <label class="inline-block mb-2 text-sm font-medium" for="setting-{{ $field }}">{{ $label }}</label>
                            <input class="form-input" id="setting-{{ $field }}" max="100" min="0" name="{{ $field }}" step="0.01" type="number"
                                   value="{{ old($field, $pct($setting->{$field})) }}">
                            <p class="mt-1 text-xs text-default-400">{{ $help }}</p>
                            @error($field)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="p-4 flex items-center justify-between gap-2 border-t border-dashed border-default-900/10">
                    <p class="text-xs text-default-500">
                        {{ __('Perubahan hanya berlaku untuk perhitungan berikutnya. Snapshot yang sudah ada tidak berubah.') }}
                        @if ($setting->updatedBy) {{ __('Terakhir diubah oleh :user pada :tanggal.', ['user' => $setting->updatedBy->name, 'tanggal' => $setting->updated_at?->format('d/m/Y H:i')]) }}@endif
                    </p>
                    <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                </div>
            </form>
        </div>
        <div class="card">
            <div class="card-header"><h6 class="card-title">{{ __('Riwayat Perubahan') }}</h6></div>
            <div class="card-body space-y-3">
                @forelse ($activities as $activity)
                    <div class="text-sm">
                        <p class="text-default-800">{{ $activity->user?->name ?? '-' }} <span class="text-xs text-default-500">· {{ $activity->created_at?->format('d/m/Y H:i') }}</span></p>
                        <p class="text-xs text-default-500">
                            @foreach (['bobot_ekspor' => __('Ekspor'), 'bobot_evaluasi' => __('Evaluasi'), 'cadangan' => __('Cadangan')] as $key => $label)
                                {{ $label }}: {{ $pct($activity->old_values[$key] ?? 0) }}% → {{ $pct($activity->new_values[$key] ?? 0) }}%@if (! $loop->last), @endif
                            @endforeach
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-default-500">{{ __('Belum ada perubahan.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
