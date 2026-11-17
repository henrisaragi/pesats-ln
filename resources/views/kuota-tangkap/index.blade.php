@extends('layouts.vertical', ['title' => __('Pembagian Kuota Ekspor')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Database Kuota'), 'title' => __('Pembagian Kuota Ekspor')])

    <div class="card" data-active-year-page>
        <div class="card-header">
            <h6 class="card-title">
                {{ __('Daftar Periode Kuota Ekspor') }}
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border border-primary/30 bg-primary/10 text-primary text-xs ms-1">{{ __('Tahun :year', ['year' => $activeYear]) }}</span>
            </h6>
            <div class="flex items-center gap-2">
                @can('kuota.settings')
                    <a class="btn btn-sm bg-default-150" href="{{ route('kuota-tangkap.settings.edit') }}">
                        <i class="size-4 me-1" data-lucide="settings-2"></i>{{ __('Pengaturan') }}
                    </a>
                @endcan
                @can('kuota.create')
                    <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#kuota-period-create-modal" type="button">
                        <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Periode') }}
                    </button>
                @endcan
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-default-200">
                <thead class="bg-default-150">
                <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Provinsi') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Komoditas') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Jenis TSL') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Kode Perdagangan') }}</th>
                    <th class="px-3.5 py-3 text-end" scope="col">{{ __('Kuota Ekspor (Q)') }}</th>
                    <th class="px-3.5 py-3 text-end" scope="col">{{ __('Peserta') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status') }}</th>
                    <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($periods as $period)
                    <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                        <td class="px-3.5 py-3">{{ $period->provinsi?->name }}</td>
                        <td class="px-3.5 py-3">{{ $period->komoditas?->name }}</td>
                        <td class="px-3.5 py-3 italic">{{ $period->species?->scientific_name }}</td>
                        <td class="px-3.5 py-3">{{ $period->tradeCode?->name }}</td>
                        <td class="px-3.5 py-3 text-end">{{ number_format((float) $period->kuota_tangkap, 0, ',', '.') }}</td>
                        <td class="px-3.5 py-3 text-end">{{ $period->allocations_count }}</td>
                        <td class="px-3.5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.75 border text-xs {{ $period->status->badgeClass() }}">{{ $period->status->label() }}</span>
                        </td>
                        <td class="px-3.5 py-3">
                            <a aria-label="{{ __('Lihat') }}" class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                               href="{{ route('kuota-tangkap.show', $period) }}">
                                <i class="size-4" data-lucide="eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3.5 py-6 text-center text-default-500" colspan="8">{{ __('Belum ada periode kuota ekspor.') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <p class="text-default-500 text-sm">
                {{ __('Menampilkan') }} <b>{{ $periods->count() }}</b> {{ __('dari') }} <b>{{ $periods->total() }}</b> {{ __('Data') }}
            </p>
            @include('layouts.partials/pagination', ['paginator' => $periods])
        </div>
    </div>

    @can('kuota.create')
        <x-kuota-tangkap.modal id="kuota-period-create-modal" :title="__('Tambah Periode Kuota Ekspor')">
            <form action="{{ route('kuota-tangkap.store') }}" method="POST">
                @csrf
                <input name="_modal" type="hidden" value="kuota-period-create-modal">
                <div class="p-4">
                    @include('kuota-tangkap.partials.period-form-fields', ['prefix' => 'kp-create', 'modalId' => 'kuota-period-create-modal', 'period' => null])
                </div>
                <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                    <button class="btn bg-default-150" data-hs-overlay="#kuota-period-create-modal" type="button">{{ __('Batal') }}</button>
                    <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                </div>
            </form>
        </x-kuota-tangkap.modal>
    @endcan
@endsection
