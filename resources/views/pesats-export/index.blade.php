@extends('layouts.vertical', ['title' => __('Permohonan SATS-LN Ekspor')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => __('Permohonan SATS-LN Ekspor')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Permohonan') }}</h6>
            @can('permohonan.create')
                <form action="{{ route('pesats-export.store') }}" method="POST">
                    @csrf
                    <button class="btn btn-sm bg-primary text-white" type="submit">
                        <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Permohonan Baru') }}
                    </button>
                </form>
            @endcan
        </div>
        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Nomor Permohonan') }}</th>
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
                                    <td class="px-3.5 py-3">{{ $item->sats_ln_count }} / 25</td>
                                    <td class="px-3.5 py-3">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs {{ $item->status->badgeClass() }}">
                                            {{ $item->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3">{{ $item->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @if ($item->status->isEditable())
                                                <a class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                   href="{{ route('pesats-export.edit', $item) }}" aria-label="{{ __('Lanjutkan') }}">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </a>
                                                <form action="{{ route('pesats-export.destroy', $item) }}" method="POST"
                                                      onsubmit="return confirm('{{ __('Hapus permohonan ini?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button aria-label="{{ __('Hapus') }}"
                                                            class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                            type="submit">
                                                        <i class="size-4" data-lucide="trash-2"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                   href="{{ route('pesats-export.show', $item) }}" aria-label="{{ __('Lihat') }}">
                                                    <i class="size-4" data-lucide="eye"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="5">
                                        {{ __('Belum ada permohonan.') }}
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
