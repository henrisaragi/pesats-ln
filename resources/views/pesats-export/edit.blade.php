@extends('layouts.vertical', ['title' => __('Permohonan SATS-LN Ekspor')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Layanan Entitas'), 'title' => $permohonan->nomor_permohonan])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                {{ __('Data SATS-LN') }}
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs ms-1 {{ $permohonan->status->badgeClass() }}">
                    {{ $permohonan->status->label() }}
                </span>
            </h6>
            <form action="{{ route('pesats-export.submit', $permohonan) }}" method="POST">
                @csrf
                <button class="btn btn-sm bg-primary text-white" type="submit"
                        @disabled($permohonan->satsLn->isEmpty())>
                    <i class="size-4 me-1" data-lucide="send"></i>{{ __('Ajukan Permohonan') }}
                </button>
            </form>
        </div>
        <div class="p-4">
            <p class="text-sm text-default-500 mb-4">
                {{ __('Jumlah SATS-LN: :count / 25', ['count' => $permohonan->satsLn->count()]) }}
            </p>

            <div aria-label="Tabs" aria-orientation="horizontal" class="flex flex-wrap gap-2 border-b border-default-200" role="tablist">
                @foreach ($permohonan->satsLn as $index => $item)
                    <button aria-controls="tab-sats-ln-{{ $item->id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            class="hs-tab-active:border-primary hs-tab-active:text-primary border-b-2 border-transparent px-4 py-2 text-sm font-medium text-default-600 {{ $loop->first ? 'active' : '' }}"
                            data-hs-tab="#tab-sats-ln-{{ $item->id }}" id="tab-item-sats-ln-{{ $item->id }}"
                            role="tab" type="button">
                        {{ __('SATS-LN') }} #{{ $index + 1 }}
                    </button>
                @endforeach
                @if (! $permohonan->hasReachedMaxSatsLn())
                    <button aria-controls="tab-sats-ln-new" aria-selected="{{ $permohonan->satsLn->isEmpty() ? 'true' : 'false' }}"
                            class="hs-tab-active:border-primary hs-tab-active:text-primary border-b-2 border-transparent px-4 py-2 text-sm font-medium text-primary {{ $permohonan->satsLn->isEmpty() ? 'active' : '' }}"
                            data-hs-tab="#tab-sats-ln-new" id="tab-item-sats-ln-new" role="tab" type="button">
                        <i class="size-4 inline me-1" data-lucide="plus"></i>{{ __('Tambah SATS-LN') }}
                    </button>
                @endif
            </div>

            <div class="mt-4">
                @foreach ($permohonan->satsLn as $index => $item)
                    <div aria-labelledby="tab-item-sats-ln-{{ $item->id }}" class="{{ $loop->first ? '' : 'hidden' }}"
                         id="tab-sats-ln-{{ $item->id }}" role="tabpanel">
                        @include('pesats-export.partials.sats-ln-form', ['satsLn' => $item])
                    </div>
                @endforeach
                @if (! $permohonan->hasReachedMaxSatsLn())
                    <div aria-labelledby="tab-item-sats-ln-new" class="{{ $permohonan->satsLn->isEmpty() ? '' : 'hidden' }}"
                         id="tab-sats-ln-new" role="tabpanel">
                        @include('pesats-export.partials.sats-ln-form', ['satsLn' => null])
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
