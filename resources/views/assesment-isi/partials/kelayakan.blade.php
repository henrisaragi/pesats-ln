{{-- $isi: ?AssesmentIsi, $hasil: ?array dari AssesmentSkorService::hasil() --}}
@if ($hasil['kelayakan'] ?? null)
    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $hasil['kelayakan']->badgeClass() }}">
        {{ $hasil['kelayakan']->label() }}
    </span>
@elseif ($isi && $isi->status !== \App\Enums\AssesmentIsiStatus::Draft)
    <span class="text-default-500 text-xs">{{ __('Dalam proses verifikasi') }}</span>
@else
    -
@endif
