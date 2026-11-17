@extends('layouts.vertical', ['title' => __('Status Server')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Settings'), 'title' => __('Status Server')])

    @php
        $cards = [
            [
                'title' => __('Queue Worker (Default)'),
                'command' => 'php artisan queue:work --queue=default,whatsapp --tries=3 --sleep=3',
                'status' => $statuses['queue_default'],
            ],
            [
                'title' => __('Queue Worker (Notifications)'),
                'command' => 'php artisan queue:work --queue=notifications --tries=3 --sleep=1',
                'status' => $statuses['queue_notifications'],
            ],
            [
                'title' => __('Reverb Server'),
                'command' => 'php artisan reverb:start',
                'status' => $statuses['reverb'],
            ],
        ];
    @endphp

    <div class="mb-4">
        <a class="btn btn-sm bg-default-150 hover:bg-default-200" href="{{ route('settings.server-status.index') }}">
            <i class="size-4 me-1" data-lucide="refresh-cw"></i>{{ __('Refresh') }}
        </a>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
        @foreach ($cards as $card)
            @php
                $status = $card['status'];
                $statusLabel = $status === null ? __('Tidak Diketahui') : ($status ? __('Aktif') : __('Tidak Aktif'));
                $statusClass = $status === null
                    ? 'bg-default-150 text-default-500'
                    : ($status ? 'bg-success/10 text-success border border-success/30' : 'bg-danger/10 text-danger border border-danger/30');
                $dotClass = $status === null ? 'bg-default-400' : ($status ? 'bg-success' : 'bg-danger');
            @endphp
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between gap-2">
                        <h6 class="card-title">{{ $card['title'] }}</h6>
                        <span class="size-2.5 rounded-full {{ $dotClass }} {{ $status ? 'animate-pulse' : '' }}"></span>
                    </div>
                    <code class="mt-2 block text-xs text-default-500 break-all">{{ $card['command'] }}</code>
                    <span class="mt-4 inline-flex items-center gap-x-1.5 py-0.5 px-2.5 rounded text-xs font-medium {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-xs text-default-400">
        {{ __('Status dicek langsung dari daftar proses server setiap halaman ini dimuat. Klik "Refresh" untuk memeriksa ulang.') }}
    </p>
@endsection
