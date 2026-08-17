@php
    $item = $satsLn->items->first();
    $staffView = $staffView ?? false;
@endphp

<div class="card border border-default-200">
    <div class="card-header">
        @if ($satsLn->satsln_number_issued)
            <h6 class="card-title">{{ $satsLn->satsln_number_issued }}</h6>
        @else
            <h6 class="card-title inline-flex items-center gap-1 rounded-full border border-warning/30 bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">
                {{ __('Nomor akan otomatis di-generate') }}
            </h6>
        @endif
        <div class="flex items-center gap-2 text-xs">
            @if ($satsLn->printed_at)
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border border-primary/30 bg-primary/10 text-primary">
                    {{ __('Dicetak :tanggal', ['tanggal' => $satsLn->printed_at->translatedFormat('d M Y H:i')]) }}
                </span>
            @endif
            @if ($satsLn->signed_at)
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border border-success/30 bg-success/10 text-success">
                    {{ __('Ditandatangani :tanggal', ['tanggal' => $satsLn->signed_at->translatedFormat('d M Y H:i')]) }}
                </span>
            @endif
        </div>
    </div>
    <div class="p-4 grid md:grid-cols-2 gap-x-4 gap-y-2 text-sm">
        <p><span class="text-default-500">{{ __('Spesies') }}:</span> {{ $item?->species?->scientific_name }}</p>
        <p><span class="text-default-500">{{ __('Status Asal') }}:</span> {{ $item?->kuotaPerusahaan?->kuotaNasional?->source?->code }}</p>
        <p><span class="text-default-500">{{ __('Jumlah') }}:</span> {{ number_format((float) ($item?->qty ?? 0), 2, ',', '.') }}</p>
        <p><span class="text-default-500">{{ __('Tujuan Transaksi') }}:</span> {{ $satsLn->purposeOfTransaction?->name }}</p>
        <p><span class="text-default-500">{{ __('Perusahaan Pembeli') }}:</span> {{ $satsLn->customerCompany?->name ?? '-' }}</p>
        <p><span class="text-default-500">{{ __('No. PEB') }}:</span> {{ $item?->peb_or_pib ?? '-' }}</p>
        <p><span class="text-default-500">{{ __('Masa Berlaku') }}:</span> {{ $satsLn->valid_until?->translatedFormat('d M Y') ?? '-' }}</p>
    </div>
    @if ($staffView && in_array($permohonan->status, [\App\Enums\PermohonanStatus::DalamProsesVerifikasi, \App\Enums\PermohonanStatus::DicetakSebagian, \App\Enums\PermohonanStatus::DitandatanganiSebagian], true))
        <div class="card-footer flex gap-2">
            @unless ($satsLn->printed_at)
                <form action="{{ route('permohonan-export.verifikasi.mark-printed', [$permohonan, $satsLn]) }}" method="POST">
                    @csrf
                    <button class="btn btn-sm bg-primary/10 text-primary" type="submit">
                        <i class="size-4 me-1" data-lucide="printer"></i>{{ __('Tandai Sudah Dicetak') }}
                    </button>
                </form>
            @endunless
            @unless ($satsLn->signed_at)
                <form action="{{ route('permohonan-export.verifikasi.mark-signed', [$permohonan, $satsLn]) }}" method="POST">
                    @csrf
                    <button class="btn btn-sm bg-success/10 text-success" type="submit">
                        <i class="size-4 me-1" data-lucide="pen-line"></i>{{ __('Tandai Sudah Ditandatangani') }}
                    </button>
                </form>
            @endunless
        </div>
    @endif
</div>
