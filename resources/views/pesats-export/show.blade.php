@extends('layouts.vertical', ['title' => __('Permohonan SATS-LN Ekspor')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => $permohonan->nomor_permohonan])

    <div class="card mb-4">
        <div class="p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-default-500">{{ __('Status Permohonan') }}</p>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $permohonan->status->badgeClass() }}">
                    {{ $permohonan->status->label() }}
                </span>
            </div>
            @if ($permohonan->status === \App\Enums\PermohonanStatus::Ditolak && $permohonan->catatan_verifikasi)
                <p class="text-sm text-danger max-w-md">{{ $permohonan->catatan_verifikasi }}</p>
            @endif
        </div>
    </div>

    <div class="flex flex-col gap-4">
        @foreach ($permohonan->satsLn as $satsLn)
            @include('pesats-export.partials.sats-ln-detail', ['satsLn' => $satsLn])
        @endforeach
    </div>
@endsection
