@extends('layouts.vertical', ['title' => __('Detail Kuota Ekspor')])

@php
    use App\Enums\KuotaPeriodStatus;
    use App\Models\KuotaTangkap\KuotaAllocation;

    $num = fn ($value, int $decimals = 0): string => $value === null ? '-' : number_format((float) $value, $decimals, ',', '.');
    $snapshot = $period->latestSnapshot;
    $canAllocate = auth()->user()->can('allocate', $period);
    $canUpdate = auth()->user()->can('update', $period);
    $tabs = ['alokasi' => __('Alokasi'), 'peserta' => __('Peserta')];
    if (in_array($period->status, [KuotaPeriodStatus::Ditetapkan, KuotaPeriodStatus::Ditutup], true)) {
        $tabs['realisasi'] = __('Realisasi & SK');
    }
    $tabs['snapshot'] = __('Riwayat Perhitungan');
    $tabs['audit'] = __('Audit Log');
    $activeTab = array_key_exists(request('tab'), $tabs) ? request('tab') : 'alokasi';
    $sumDitetapkan = $period->allocations->reduce(fn ($carry, $a) => bcadd($carry, (string) ($a->ditetapkan ?? '0'), 4), '0');
    $cadanganPersen = $snapshot ? rtrim(rtrim(bcmul((string) $snapshot->cadangan, '100', 2), '0'), '.') : null;
@endphp

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Pembagian Kuota Ekspor'), 'title' => __('Detail Periode')])

    {{-- Header & ringkasan --}}
    <div class="card mb-5" data-kuota-period>
        <div class="card-header flex-wrap gap-3">
            <div>
                <h6 class="card-title flex items-center gap-2">
                    {{ $period->provinsi?->name }} · <span class="italic">{{ $period->species?->scientific_name }}</span> · {{ $period->tradeCode?->name }} · {{ $period->year }}
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.75 border text-xs {{ $period->status->badgeClass() }}" data-period-status>{{ $period->status->label() }}</span>
                </h6>
                <p class="text-default-500 text-sm mt-1">{{ __('Komoditas') }}: {{ $period->komoditas?->name }}@if ($period->catatan) · {{ $period->catatan }}@endif</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @can('update', $period)
                    <button class="btn btn-sm bg-default-150" data-hs-overlay="#kuota-period-edit-modal" type="button"><i class="size-4 me-1" data-lucide="edit"></i>{{ __('Ubah') }}</button>
                @endcan
                @can('delete', $period)
                    <button class="btn btn-sm bg-danger/10 text-danger" data-hs-overlay="#kuota-period-delete-modal" type="button"><i class="size-4 me-1" data-lucide="trash-2"></i>{{ __('Hapus') }}</button>
                @endcan
                @can('calculate', $period)
                    <form action="{{ route('kuota-tangkap.calculate', $period) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm bg-info text-white" type="submit"><i class="size-4 me-1" data-lucide="calculator"></i>{{ $snapshot ? __('Hitung Ulang') : __('Hitung Rekomendasi') }}</button>
                    </form>
                @endcan
                @can('submit', $period)
                    <button class="btn btn-sm bg-warning text-white" data-hs-overlay="#kuota-period-submit-modal" type="button"><i class="size-4 me-1" data-lucide="send"></i>{{ __('Ajukan') }}</button>
                @endcan
                @can('approve', $period)
                    <button class="btn btn-sm bg-success text-white" data-hs-overlay="#kuota-period-approve-modal" type="button"><i class="size-4 me-1" data-lucide="badge-check"></i>{{ __('Setujui & Tetapkan') }}</button>
                @endcan
                @can('return', $period)
                    <button class="btn btn-sm bg-danger/10 text-danger" data-hs-overlay="#kuota-period-return-modal" type="button"><i class="size-4 me-1" data-lucide="undo-2"></i>{{ __('Kembalikan') }}</button>
                @endcan
                @can('close', $period)
                    <button class="btn btn-sm bg-dark text-white" data-hs-overlay="#kuota-period-close-modal" type="button"><i class="size-4 me-1" data-lucide="lock"></i>{{ __('Tutup Periode') }}</button>
                @endcan
                @can('export', $period)
                    <a class="btn btn-sm bg-default-150" href="{{ route('kuota-tangkap.export.csv', $period) }}"><i class="size-4 me-1" data-lucide="file-spreadsheet"></i>CSV</a>
                    <a class="btn btn-sm bg-default-150" href="{{ route('kuota-tangkap.export.pdf', $period) }}"><i class="size-4 me-1" data-lucide="file-text"></i>PDF</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-4">
                @foreach ([
                    [__('Kuota Ekspor (Q)'), $num($period->kuota_tangkap)],
                    [__('Cadangan (c)'), $snapshot ? $cadanganPersen.'%' : '-'],
                    [__('Kuota Dibagi (Q_b)'), $snapshot ? $num($snapshot->kuota_dibagi) : '-'],
                    [__('ΣE Realisasi :tahun', ['tahun' => $period->year - 1]), $snapshot ? $num($snapshot->total_realisasi_ekspor) : '-'],
                    [__('ΣN Nilai Evaluasi'), $snapshot ? $num($snapshot->total_nilai_evaluasi, 2) : '-'],
                    [__('Snapshot'), $snapshot ? 'v'.$snapshot->version : '-'],
                    [__('Cadangan Aktual'), $num($cadanganAktual)],
                ] as [$label, $value])
                    <div class="rounded-lg border border-default-200 p-3">
                        <p class="text-xs text-default-500">{{ $label }}</p>
                        <p class="text-lg font-semibold text-default-800" @if ($loop->last) data-cadangan-aktual-summary @endif>{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 space-y-2">
                @if ($errors->has('period'))
                    <div class="rounded-lg border border-danger/30 bg-danger/10 text-danger text-sm p-3">{{ $errors->first('period') }}</div>
                @endif
                @if ($errors->has('allocations') || collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'allocations.')))
                    <div class="rounded-lg border border-danger/30 bg-danger/10 text-danger text-sm p-3" data-allocation-errors>
                        <ul class="list-disc ms-4">
                            @foreach ($errors->getMessages() as $key => $messages)
                                @if (str_starts_with($key, 'allocations'))
                                    @foreach ($messages as $message)<li>{{ $message }}</li>@endforeach
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (session('kuota_warning'))
                    <div class="rounded-lg border border-warning/30 bg-warning/10 text-warning text-sm p-3" data-cadangan-warning>{{ session('kuota_warning') }}</div>
                @endif
                @if ($period->catatan_pengembalian && $period->status === KuotaPeriodStatus::Dihitung)
                    <div class="rounded-lg border border-danger/30 bg-danger/10 text-danger text-sm p-3"><b>{{ __('Dikembalikan') }}:</b> {{ $period->catatan_pengembalian }}</div>
                @endif
                @if ($period->status === KuotaPeriodStatus::Draft && $snapshot)
                    <div class="rounded-lg border border-info/30 bg-info/10 text-info text-sm p-3">{{ __('Data periode berubah setelah perhitungan terakhir. Hitung ulang sebelum melanjutkan penetapan.') }}</div>
                @endif
                @if ($snapshot)
                    @foreach ($snapshot->warningMessages() as $warning)
                        <div class="rounded-lg border border-warning/30 bg-warning/10 text-warning text-sm p-3 flex gap-2"><i class="size-4 shrink-0 mt-0.5" data-lucide="triangle-alert"></i>{{ $warning }}</div>
                    @endforeach
                @endif
                @if ($period->submitter || $period->approver)
                    <p class="text-xs text-default-500">
                        @if ($period->submitter){{ __('Diajukan oleh :user pada :tanggal', ['user' => $period->submitter->name, 'tanggal' => $period->submitted_at?->format('d/m/Y H:i')]) }}@endif
                        @if ($period->approver) · {{ __('Ditetapkan oleh :user pada :tanggal', ['user' => $period->approver->name, 'tanggal' => $period->approved_at?->format('d/m/Y H:i')]) }}@endif
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Tab --}}
    <div class="card">
        <div class="card-header">
            <nav class="flex flex-wrap gap-1" role="tablist">
                @foreach ($tabs as $key => $label)
                    <a class="px-3 py-1.5 rounded-md text-sm {{ $activeTab === $key ? 'bg-primary text-white' : 'text-default-600 hover:bg-default-150' }}"
                       href="{{ route('kuota-tangkap.show', [$period, 'tab' => $key]) }}" role="tab">{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        @if ($activeTab === 'alokasi')
            @if ($snapshot === null)
                <div class="p-6 text-center text-default-500 text-sm">{{ __('Belum ada perhitungan. Tambahkan peserta di tab Peserta lalu klik "Hitung Rekomendasi".') }}</div>
            @else
                <form action="{{ route('kuota-tangkap.alokasi.update', $period) }}" method="POST" data-allocation-form
                      data-kuota="{{ (string) $period->kuota_tangkap }}" data-cadangan="{{ (string) $snapshot->cadangan }}">
                    @csrf
                    @method('PUT')
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start">{{ __('Perusahaan') }}</th>
                                <th class="px-3.5 py-3 text-end">{{ __('Permohonan') }}</th>
                                <th class="px-3.5 py-3 text-end">{{ __('E (Ekspor :tahun)', ['tahun' => $period->year - 1]) }}</th>
                                <th class="px-3.5 py-3 text-end">{{ __('N (Nilai)') }}</th>
                                <th class="px-3.5 py-3 text-end">{{ __('Rekomendasi') }}</th>
                                <th class="px-3.5 py-3 text-start">{{ __('Ditetapkan') }}</th>
                                <th class="px-3.5 py-3 text-start">{{ __('Alasan Penyesuaian') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($period->allocations as $allocation)
                                @php
                                    $ditetapkanValue = old("allocations.{$allocation->id}.ditetapkan", $allocation->ditetapkan !== null ? rtrim(rtrim((string) $allocation->ditetapkan, '0'), '.') : '');
                                    $alasanValue = old("allocations.{$allocation->id}.alasan_penyesuaian", $allocation->alasan_penyesuaian);
                                    $needsReason = KuotaAllocation::requiresReason($ditetapkanValue === '' ? null : (string) $ditetapkanValue, $allocation->rekomendasi_bulat);
                                @endphp
                                <tr class="text-default-800 text-sm whitespace-nowrap align-top" data-allocation-row data-rekomendasi="{{ $allocation->rekomendasi_bulat }}">
                                    <td class="px-3.5 py-3">{{ $allocation->entitas?->name }}</td>
                                    <td class="px-3.5 py-3 text-end text-default-500">{{ $num($allocation->permohonan) }}</td>
                                    <td class="px-3.5 py-3 text-end">{{ $num($allocation->realisasi_ekspor) }}</td>
                                    <td class="px-3.5 py-3 text-end">{{ $num($allocation->nilai_evaluasi, 2) }}</td>
                                    <td class="px-3.5 py-3 text-end">
                                        <span class="font-semibold" data-rekomendasi-bulat>{{ $num($allocation->rekomendasi_bulat) }}</span>
                                        <span class="block text-xs text-default-400" title="{{ __('Komp. Ekspor') }} + {{ __('Komp. Evaluasi') }}">{{ $num($allocation->komponen_ekspor, 4) }} + {{ $num($allocation->komponen_evaluasi, 4) }} = {{ $num($allocation->rekomendasi, 4) }}</span>
                                    </td>
                                    <td class="px-3.5 py-3">
                                        @if ($canAllocate)
                                            <input aria-label="{{ __('Ditetapkan') }} {{ $allocation->entitas?->name }}" class="form-input form-input-sm w-32 text-end @error("allocations.{$allocation->id}.ditetapkan") border-danger @enderror"
                                                   data-ditetapkan min="0" name="allocations[{{ $allocation->id }}][ditetapkan]" step="any" type="number" value="{{ $ditetapkanValue }}">
                                        @else
                                            <span class="font-semibold">{{ $num($allocation->ditetapkan) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3.5 py-3 whitespace-normal min-w-48">
                                        @if ($canAllocate)
                                            <textarea aria-label="{{ __('Alasan Penyesuaian') }} {{ $allocation->entitas?->name }}" class="form-input form-input-sm {{ $needsReason && blank($alasanValue) ? 'border-danger ring-1 ring-danger' : '' }}"
                                                      data-alasan name="allocations[{{ $allocation->id }}][alasan_penyesuaian]" placeholder="{{ $needsReason ? __('Wajib diisi') : '' }}" rows="1">{{ $alasanValue }}</textarea>
                                        @else
                                            <span class="text-default-600">{{ $allocation->alasan_penyesuaian ?? '-' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot class="bg-default-100 text-sm font-semibold">
                            <tr>
                                <td class="px-3.5 py-3">{{ __('Total') }}</td>
                                <td class="px-3.5 py-3 text-end">{{ $num($period->allocations->sum(fn ($a) => (float) $a->permohonan)) }}</td>
                                <td class="px-3.5 py-3 text-end">{{ $num($snapshot->total_realisasi_ekspor) }}</td>
                                <td class="px-3.5 py-3 text-end">{{ $num($snapshot->total_nilai_evaluasi, 2) }}</td>
                                <td class="px-3.5 py-3 text-end" data-total-rekomendasi>{{ $num($period->allocations->sum('rekomendasi_bulat')) }}</td>
                                <td class="px-3.5 py-3" data-total-ditetapkan>{{ $num($sumDitetapkan) }}</td>
                                <td></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-default-200 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        <span>{{ __('Cadangan aktual = Q − Σ ditetapkan =') }} <b data-cadangan-aktual>{{ $num($cadanganAktual) }}</b></span>
                        <span class="text-danger hidden" data-over-quota>{{ __('Total ditetapkan melebihi kuota ekspor.') }}</span>
                        <span class="text-warning {{ $cadanganBelowMinimum ? '' : 'hidden' }}" data-below-cadangan>{{ __('Cadangan aktual di bawah :persen%.', ['persen' => $cadanganPersen]) }}</span>
                        <span class="text-xs text-default-500 ms-auto">{{ __('Angka kecil di bawah rekomendasi: komponen ekspor + komponen evaluasi = rekomendasi desimal.') }}</span>
                    </div>
                    @if ($canAllocate)
                        <div class="p-4 flex flex-wrap justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" form="kuota-copy-recommendation-form" type="submit"><i class="size-4 me-1" data-lucide="copy"></i>{{ __('Salin Rekomendasi') }}</button>
                            <button class="btn bg-primary text-white" type="submit"><i class="size-4 me-1" data-lucide="save"></i>{{ __('Simpan Penetapan') }}</button>
                        </div>
                    @endif
                </form>
                @if ($canAllocate)
                    <form action="{{ route('kuota-tangkap.alokasi.copy', $period) }}" id="kuota-copy-recommendation-form" method="POST">@csrf</form>
                @endif
            @endif
        @endif

        @if ($activeTab === 'peserta')
            @if ($canUpdate)
                <div class="p-4 border-b border-default-200 flex flex-wrap items-end gap-3">
                    <form action="{{ route('kuota-tangkap.peserta.store', $period) }}" class="flex flex-wrap items-end gap-3 grow" method="POST">
                        @csrf
                        <div class="min-w-64 grow">
                            <label class="inline-block mb-2 text-sm font-medium" for="kp-peserta-entity">{{ __('Perusahaan') }}</label>
                            <select class="form-input" data-choice id="kp-peserta-entity" name="entity_id">
                                <option value="">{{ __('Pilih Perusahaan') }}</option>
                                @foreach ($entitasOptions as $option)
                                    <option value="{{ $option->id }}" @selected((int) old('entity_id') === $option->id)>{{ $option->name }}</option>
                                @endforeach
                            </select>
                            @error('entity_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div class="w-40">
                            <label class="inline-block mb-2 text-sm font-medium" for="kp-peserta-permohonan">{{ __('Permohonan') }}</label>
                            <input class="form-input" id="kp-peserta-permohonan" min="0" name="permohonan" step="any" type="number" value="{{ old('permohonan') }}">
                        </div>
                        <button class="btn bg-primary text-white" type="submit"><i class="size-4 me-1" data-lucide="user-plus"></i>{{ __('Tambah Peserta') }}</button>
                    </form>
                    <form action="{{ route('kuota-tangkap.peserta.candidates', $period) }}" method="POST">
                        @csrf
                        <button class="btn bg-default-150" title="{{ __('Perusahaan dengan assessment terverifikasi untuk komoditas ini atau realisasi ekspor tahun sebelumnya') }}" type="submit">
                            <i class="size-4 me-1" data-lucide="wand-sparkles"></i>{{ __('Tambah Kandidat Otomatis') }}
                        </button>
                    </form>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-default-200">
                    <thead class="bg-default-150">
                    <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                        <th class="px-3.5 py-3 text-start">{{ __('Perusahaan') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Provinsi') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Permohonan') }}</th>
                        @if ($canUpdate)<th class="px-3.5 py-3 text-start">{{ __('Aksi') }}</th>@endif
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($period->allocations as $allocation)
                        <tr class="text-default-800 text-sm whitespace-nowrap">
                            <td class="px-3.5 py-3">{{ $allocation->entitas?->name }}</td>
                            <td class="px-3.5 py-3 text-default-500">{{ $allocation->entitas?->provinsi?->name ?? '-' }}</td>
                            <td class="px-3.5 py-3">
                                @if ($canUpdate)
                                    <form action="{{ route('kuota-tangkap.peserta.update', [$period, $allocation]) }}" class="flex items-center gap-2" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input aria-label="{{ __('Permohonan') }} {{ $allocation->entitas?->name }}" class="form-input form-input-sm w-32 text-end" min="0" name="permohonan" step="any" type="number"
                                               value="{{ $allocation->permohonan !== null ? rtrim(rtrim((string) $allocation->permohonan, '0'), '.') : '' }}">
                                        <button aria-label="{{ __('Simpan') }}" class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500" type="submit"><i class="size-4" data-lucide="check"></i></button>
                                    </form>
                                @else
                                    {{ $num($allocation->permohonan) }}
                                @endif
                            </td>
                            @if ($canUpdate)
                                <td class="px-3.5 py-3">
                                    <button aria-label="{{ __('Hapus') }}" class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                            data-hs-overlay="#kuota-peserta-delete-{{ $allocation->id }}" type="button"><i class="size-4" data-lucide="trash-2"></i></button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td class="px-3.5 py-6 text-center text-default-500 text-sm" colspan="4">{{ __('Belum ada peserta.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <p class="p-4 text-xs text-default-500">{{ __('Permohonan hanya informasi dan tidak membatasi rekomendasi. Mengubah peserta atau data periode setelah dihitung mengembalikan status ke Draft.') }}</p>
        @endif

        @if ($activeTab === 'realisasi')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-default-200">
                    <thead class="bg-default-150">
                    <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                        <th class="px-3.5 py-3 text-start">{{ __('Perusahaan') }}</th>
                        <th class="px-3.5 py-3 text-end">{{ __('Ditetapkan') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Realisasi Ekspor') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Nomor SK Kuota Ekspor') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Tanggal SK') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('File SK (PDF)') }}</th>
                        <th class="px-3.5 py-3"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($period->allocations as $allocation)
                        @can('realisasi', $period)
                            <tr class="text-default-800 text-sm whitespace-nowrap">
                                <td class="px-3.5 py-3">{{ $allocation->entitas?->name }}</td>
                                <td class="px-3.5 py-3 text-end font-semibold">{{ $num($allocation->ditetapkan) }}</td>
                                <td class="px-3.5 py-3"><input class="form-input form-input-sm w-24" form="realisasi-{{ $allocation->id }}" min="0" name="realisasi_tangkap" step="any" type="number" value="{{ $allocation->realisasi_tangkap !== null ? rtrim(rtrim((string) $allocation->realisasi_tangkap, '0'), '.') : '' }}"></td>
                                <td class="px-3.5 py-3"><input class="form-input form-input-sm w-48" form="realisasi-{{ $allocation->id }}" name="sk_tangkap_nomor" type="text" value="{{ $allocation->sk_tangkap_nomor }}"></td>
                                <td class="px-3.5 py-3"><input class="form-input form-input-sm w-36" form="realisasi-{{ $allocation->id }}" name="sk_tangkap_tanggal" type="date" value="{{ $allocation->sk_tangkap_tanggal?->toDateString() }}"></td>
                                <td class="px-3.5 py-3">
                                    <input accept="application/pdf" class="text-xs w-44 file:me-2 file:rounded file:border-0 file:bg-default-150 file:px-2 file:py-1" form="realisasi-{{ $allocation->id }}" name="sk_tangkap_file" type="file">
                                    @if ($allocation->sk_tangkap_path)
                                        <a class="block text-xs text-primary mt-1" href="{{ route('kuota-tangkap.realisasi.sk', [$period, $allocation]) }}">{{ __('Unduh SK') }}</a>
                                    @endif
                                </td>
                                <td class="px-3.5 py-3">
                                    <form action="{{ route('kuota-tangkap.realisasi.update', [$period, $allocation]) }}" enctype="multipart/form-data" id="realisasi-{{ $allocation->id }}" method="POST">
                                        @csrf
                                        <button class="btn btn-sm bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @else
                            <tr class="text-default-800 text-sm whitespace-nowrap">
                                <td class="px-3.5 py-3">{{ $allocation->entitas?->name }}</td>
                                <td class="px-3.5 py-3 text-end font-semibold">{{ $num($allocation->ditetapkan) }}</td>
                                <td class="px-3.5 py-3">{{ $num($allocation->realisasi_tangkap) }}</td>
                                <td class="px-3.5 py-3">{{ $allocation->sk_tangkap_nomor ?? '-' }}</td>
                                <td class="px-3.5 py-3">{{ $allocation->sk_tangkap_tanggal?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-3.5 py-3">
                                    @if ($allocation->sk_tangkap_path)
                                        <a class="text-primary" href="{{ route('kuota-tangkap.realisasi.sk', [$period, $allocation]) }}">{{ __('Unduh SK') }}</a>
                                    @else - @endif
                                </td>
                                <td></td>
                            </tr>
                        @endcan
                    @endforeach
                    </tbody>
                </table>
            </div>
            @foreach (['realisasi_tangkap', 'sk_tangkap_nomor', 'sk_tangkap_tanggal', 'sk_tangkap_file'] as $field)
                @error($field)<p class="px-4 pb-2 text-xs text-danger">{{ $message }}</p>@enderror
            @endforeach
        @endif

        @if ($activeTab === 'snapshot')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-default-200">
                    <thead class="bg-default-150">
                    <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                        <th class="px-3.5 py-3 text-start">{{ __('Versi') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Waktu') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Oleh') }}</th>
                        <th class="px-3.5 py-3 text-end">Q</th>
                        <th class="px-3.5 py-3 text-end">{{ __('Bobot E/N') }}</th>
                        <th class="px-3.5 py-3 text-end">c</th>
                        <th class="px-3.5 py-3 text-end">Q_b</th>
                        <th class="px-3.5 py-3 text-end">{{ __('Peringatan') }}</th>
                        <th class="px-3.5 py-3"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($period->snapshots as $item)
                        <tr class="text-default-800 text-sm whitespace-nowrap">
                            <td class="px-3.5 py-3 font-semibold">v{{ $item->version }} @if ($item->id === $period->latest_snapshot_id)<span class="text-xs text-success">({{ __('terbaru') }})</span>@endif</td>
                            <td class="px-3.5 py-3">{{ $item->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-3.5 py-3">{{ $item->calculator?->name ?? '-' }}</td>
                            <td class="px-3.5 py-3 text-end">{{ $num($item->kuota_tangkap) }}</td>
                            <td class="px-3.5 py-3 text-end">{{ $num((float) $item->bobot_ekspor * 100) }}% / {{ $num((float) $item->bobot_evaluasi * 100) }}%</td>
                            <td class="px-3.5 py-3 text-end">{{ $num((float) $item->cadangan * 100) }}%</td>
                            <td class="px-3.5 py-3 text-end">{{ $num($item->kuota_dibagi) }}</td>
                            <td class="px-3.5 py-3 text-end">{{ count($item->warnings ?? []) }}</td>
                            <td class="px-3.5 py-3"><a class="text-primary text-sm" href="{{ route('kuota-tangkap.snapshot', [$period, $item]) }}">{{ __('Lihat') }}</a></td>
                        </tr>
                    @empty
                        <tr><td class="px-3.5 py-6 text-center text-default-500 text-sm" colspan="9">{{ __('Belum ada perhitungan.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if ($activeTab === 'audit')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-default-200">
                    <thead class="bg-default-150">
                    <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                        <th class="px-3.5 py-3 text-start">{{ __('Waktu') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Pengguna') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Aksi') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Perusahaan') }}</th>
                        <th class="px-3.5 py-3 text-start">{{ __('Perubahan') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($period->activities as $activity)
                        <tr class="text-default-800 text-sm align-top">
                            <td class="px-3.5 py-3 whitespace-nowrap">{{ $activity->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-3.5 py-3 whitespace-nowrap">{{ $activity->user?->name ?? '-' }}</td>
                            <td class="px-3.5 py-3 whitespace-nowrap">{{ $activity->actionLabel() }}</td>
                            <td class="px-3.5 py-3 whitespace-nowrap">{{ $activity->allocation?->entitas?->name ?? '-' }}</td>
                            <td class="px-3.5 py-3 text-xs text-default-600">
                                @foreach (($activity->new_values ?? []) as $field => $newValue)
                                    <div><b>{{ \App\Models\KuotaTangkap\KuotaActivity::fieldLabel($field) }}</b>: @if (is_array($activity->old_values) && array_key_exists($field, $activity->old_values)){{ json_encode($activity->old_values[$field], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }} → @endif{{ json_encode($newValue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</div>
                                @endforeach
                                @if ($activity->keterangan)<div>{{ $activity->keterangan }}</div>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-3.5 py-6 text-center text-default-500 text-sm" colspan="5">{{ __('Belum ada aktivitas.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Modal --}}
    @can('update', $period)
        <x-kuota-tangkap.modal id="kuota-period-edit-modal" :title="__('Ubah Periode Kuota Ekspor')">
            <form action="{{ route('kuota-tangkap.update', $period) }}" method="POST">
                @csrf
                @method('PUT')
                <input name="_modal" type="hidden" value="kuota-period-edit-modal">
                <div class="p-4">
                    @include('kuota-tangkap.partials.period-form-fields', ['prefix' => 'kp-edit', 'modalId' => 'kuota-period-edit-modal', 'period' => $period, 'activeYear' => $period->year])
                </div>
                <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                    <button class="btn bg-default-150" data-hs-overlay="#kuota-period-edit-modal" type="button">{{ __('Batal') }}</button>
                    <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                </div>
            </form>
        </x-kuota-tangkap.modal>
        @foreach ($period->allocations as $allocation)
            <x-kuota-tangkap.confirm-modal :action="route('kuota-tangkap.peserta.destroy', [$period, $allocation])" confirm-class="bg-danger" :confirm-label="__('Ya, Hapus!')"
                                           id="kuota-peserta-delete-{{ $allocation->id }}" method="DELETE"
                                           :message="__(':entitas akan dihapus dari daftar peserta.', ['entitas' => $allocation->entitas?->name])" :title="__('Hapus peserta?')"/>
        @endforeach
    @endcan
    @can('delete', $period)
        <x-kuota-tangkap.confirm-modal :action="route('kuota-tangkap.destroy', $period)" confirm-class="bg-danger" :confirm-label="__('Ya, Hapus!')"
                                       id="kuota-period-delete-modal" method="DELETE" :message="__('Periode ini beserta peserta dan riwayatnya akan dihapus.')" :title="__('Anda yakin?')"/>
    @endcan
    @can('submit', $period)
        <x-kuota-tangkap.confirm-modal :action="route('kuota-tangkap.submit', $period)" confirm-class="bg-warning" :confirm-label="__('Ya, Ajukan')"
                                       id="kuota-period-submit-modal" :message="__('Setelah diajukan, penetapan tidak dapat diubah kecuali dikembalikan oleh pejabat penetap.')" :title="__('Ajukan periode?')"/>
    @endcan
    @can('approve', $period)
        <x-kuota-tangkap.confirm-modal :action="route('kuota-tangkap.approve', $period)" confirm-class="bg-success" :confirm-label="__('Ya, Tetapkan')"
                                       id="kuota-period-approve-modal" :message="__('Kuota yang ditetapkan akan dikunci dan dapat dilihat oleh perusahaan.')" :title="__('Setujui & tetapkan kuota?')"/>
        <x-kuota-tangkap.confirm-modal :action="route('kuota-tangkap.return', $period)" confirm-class="bg-danger" :confirm-label="__('Kembalikan')"
                                       id="kuota-period-return-modal" :message="__('Periode dikembalikan ke status Dihitung untuk diperbaiki.')" :title="__('Kembalikan periode?')"
                                       textarea="catatan_pengembalian" :textarea-label="__('Catatan Pengembalian')"/>
    @endcan
    @can('close', $period)
        <x-kuota-tangkap.confirm-modal :action="route('kuota-tangkap.close', $period)" confirm-class="bg-dark" :confirm-label="__('Ya, Tutup')"
                                       id="kuota-period-close-modal" :message="__('Periode yang ditutup menjadi read-only, termasuk realisasi ekspor.')" :title="__('Tutup periode?')"/>
    @endcan
@endsection

@section('scripts')
    <script>
        // Hitung Σ ditetapkan & cadangan aktual secara langsung, tandai alasan yang wajib. Validasi final tetap di server.
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-allocation-form]');
            if (!form || !form.querySelector('[data-ditetapkan]')) {
                return;
            }
            const kuota = parseFloat(form.dataset.kuota);
            const minimum = kuota * parseFloat(form.dataset.cadangan);
            const fmt = new Intl.NumberFormat('id-ID', {maximumFractionDigits: 4});

            function refresh() {
                let total = 0;
                form.querySelectorAll('[data-allocation-row]').forEach(function (row) {
                    const input = row.querySelector('[data-ditetapkan]');
                    const alasan = row.querySelector('[data-alasan]');
                    const value = input.value === '' ? null : parseFloat(input.value);
                    total += value ?? 0;
                    const needsReason = value !== null && value !== parseFloat(row.dataset.rekomendasi);
                    const missing = needsReason && alasan.value.trim() === '';
                    ['border-danger', 'ring-1', 'ring-danger'].forEach((c) => alasan.classList.toggle(c, missing));
                    alasan.placeholder = needsReason ? @json(__('Wajib diisi')) : '';
                });
                const cadangan = kuota - total;
                form.querySelector('[data-total-ditetapkan]').textContent = fmt.format(total);
                form.querySelector('[data-cadangan-aktual]').textContent = fmt.format(cadangan);
                form.querySelector('[data-over-quota]').classList.toggle('hidden', cadangan >= 0);
                form.querySelector('[data-below-cadangan]').classList.toggle('hidden', !(cadangan >= 0 && cadangan < minimum));
            }

            form.addEventListener('input', refresh);
            refresh();
        });
    </script>
@endsection
