@extends('layouts.vertical', ['title' => __('Verifikasi Permohonan Ekspor')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => __('Verifikasi Permohonan Ekspor')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Permohonan') }}</h6>
        </div>
        <div class="card-header">
            <form action="{{ route('permohonan-export.verifikasi.index') }}"
                  class="md:flex items-center md:space-y-0 space-y-4 gap-3" method="GET">
                <select class="form-input form-input-sm" name="status" onchange="this.form.submit()">
                    @foreach (\App\Enums\PermohonanStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($activeStatus === $status)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                    <option value="" @selected($activeStatus === null)>{{ __('Semua Status') }}</option>
                </select>
            </form>
        </div>
        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nomor Permohonan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Entitas') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Jumlah SATS-LN') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Diajukan') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($permohonan as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">{{ $item->nomor_permohonan }}</td>
                                    <td class="px-3.5 py-3">{{ $item->entitas?->name }}</td>
                                    <td class="px-3.5 py-3">{{ $item->sats_ln_count }} / 25</td>
                                    <td class="px-3.5 py-3">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $item->status->badgeClass() }}">
                                            {{ $item->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3">{{ $item->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                    <td class="px-3.5 py-3">
                                        <a class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                           href="{{ route('permohonan-export.verifikasi.show', $item) }}" aria-label="{{ __('Lihat Detail') }}">
                                            <i class="size-4" data-lucide="eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="6">
                                        {{ __('Tidak ada permohonan.') }}
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
                    {{ __('Menampilkan') }} <b>{{ $permohonan->count() }}</b> {{ __('dari') }}
                    <b>{{ $permohonan->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $permohonan])
            </div>
        </div>
    </div>
@endsection
