@extends('layouts.vertical', ['title' => __('Beranda')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Home'), 'title' => __('Beranda')])

    {{-- data-active-year-page: script topbar menyelaraskan "?year=" dengan Tahun Aktif (Controller::activeYear). --}}
    <div class="card-body relative overflow-hidden bg-zinc-900 rounded-md mb-5" data-active-year-page>
        <div class="relative z-10 grid grid-cols-12 items-center">
            <div class="lg:col-span-8 col-span-12">
                <h5 class="mb-3 text-lg text-white">{{ __('Selamat datang, :name', ['name' => $user->name]) }} 🎉</h5>
                <p class="mb-4 text-white/70 text-sm">
                    {{ __('Pantau ringkasan status pengajuan Anda di PeSATS. Gunakan Take Tour untuk mengenal menu-menu yang tersedia di sidebar.') }}
                </p>
                <div class="mb-5 inline-flex items-center gap-2 rounded-md bg-white/10 px-3 py-1.5 text-sm text-white">
                    <i class="size-4" data-lucide="calendar"></i>
                    <span>{{ __('Akses Tahun:') }}</span>
                    <span class="font-semibold">{{ $year }}</span>
                </div>
                <div>
                    <button class="btn bg-primary text-white" data-done-text="{{ __('Selesai') }}"
                        data-next-text="{{ __('Selanjutnya') }}" data-prev-text="{{ __('Sebelumnya') }}"
                        id="dashboard-tour-trigger" type="button">
                        <i class="size-4 me-1" data-lucide="compass"></i>{{ __('Take Tour') }}
                    </button>
                </div>
            </div>
            <div class="col-span-4 ms-auto lg:block hidden">
                <div class="rounded-md bg-white p-3">
                    <img alt="{{ __('Logo Kementerian Kehutanan') }}" class="h-36" src="{{ asset('images/logo-kehutanan.png') }}" />
                </div>
            </div>
        </div>
        <div class="absolute inset-0">
            <svg class="size-full" preserveaspectratio="none" version="1.1" viewbox="0 0 1440 560"
                xmlns="http://www.w3.org/2000/svg" xmlns:svgjs="http://svgjs.dev/svgjs"
                xmlns:xlink="http://www.w3.org/1999/xlink">
                <g fill="none" mask='url("#SvgjsMask1000")'>
                    <use x="0" xlink:href="#SvgjsSymbol1007" y="0"></use>
                    <use x="720" xlink:href="#SvgjsSymbol1007" y="0"></use>
                </g>
                <defs>
                    <mask id="SvgjsMask1000">
                        <rect fill="#ffffff" height="560" width="1440"></rect>
                    </mask>
                    <path d="M-1 0 a1 1 0 1 0 2 0 a1 1 0 1 0 -2 0z" id="SvgjsPath1003"></path>
                    <path d="M-3 0 a3 3 0 1 0 6 0 a3 3 0 1 0 -6 0z" id="SvgjsPath1004"></path>
                    <path d="M-5 0 a5 5 0 1 0 10 0 a5 5 0 1 0 -10 0z" id="SvgjsPath1001"></path>
                    <path d="M2 -2 L-2 2z" id="SvgjsPath1005"></path>
                    <path d="M6 -6 L-6 6z" id="SvgjsPath1002"></path>
                    <path d="M30 -30 L-30 30z" id="SvgjsPath1006"></path>
                </defs>
                <symbol id="SvgjsSymbol1007">
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1001" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1002" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1001" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1003" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1002" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1001" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1002" y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1003" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1001" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="30" xlink:href="#SvgjsPath1002" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1001" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1003" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1001" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1001" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1004" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1003" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1001" y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1001" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1001" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="90" xlink:href="#SvgjsPath1002" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="150" xlink:href="#SvgjsPath1002" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="150" xlink:href="#SvgjsPath1005" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="150" xlink:href="#SvgjsPath1002" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="150" xlink:href="#SvgjsPath1005" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="150" xlink:href="#SvgjsPath1005" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="150" xlink:href="#SvgjsPath1006" y="330">
                    </use>
                    <use stroke="rgba(32, 43, 61, 1)" x="150" xlink:href="#SvgjsPath1004" y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="150" xlink:href="#SvgjsPath1002" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="150" xlink:href="#SvgjsPath1001" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="150" xlink:href="#SvgjsPath1001" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="210" xlink:href="#SvgjsPath1002" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="210" xlink:href="#SvgjsPath1002" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="210" xlink:href="#SvgjsPath1006"
                        y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="210" xlink:href="#SvgjsPath1002" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="210" xlink:href="#SvgjsPath1001" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="210" xlink:href="#SvgjsPath1005" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="210" xlink:href="#SvgjsPath1001" y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="210" xlink:href="#SvgjsPath1002" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="210" xlink:href="#SvgjsPath1006"
                        y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="210" xlink:href="#SvgjsPath1003" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="270" xlink:href="#SvgjsPath1002" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="270" xlink:href="#SvgjsPath1005" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="270" xlink:href="#SvgjsPath1001" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="270" xlink:href="#SvgjsPath1002" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="270" xlink:href="#SvgjsPath1005" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="270" xlink:href="#SvgjsPath1001" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="270" xlink:href="#SvgjsPath1006"
                        y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="270" xlink:href="#SvgjsPath1002" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="270" xlink:href="#SvgjsPath1005" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="270" xlink:href="#SvgjsPath1005" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="330" xlink:href="#SvgjsPath1002" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="330" xlink:href="#SvgjsPath1006"
                        y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="330" xlink:href="#SvgjsPath1002" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="330" xlink:href="#SvgjsPath1002" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="330" xlink:href="#SvgjsPath1006"
                        y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="330" xlink:href="#SvgjsPath1001" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="330" xlink:href="#SvgjsPath1002" y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="330" xlink:href="#SvgjsPath1001" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="330" xlink:href="#SvgjsPath1003" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="330" xlink:href="#SvgjsPath1001" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1004" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1005" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1002" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1005" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1001" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1002" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1002" y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1003" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1002" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="390" xlink:href="#SvgjsPath1001" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1001" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1004" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1002" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1001" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1002" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1001" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1001" y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1002" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1001" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="450" xlink:href="#SvgjsPath1001" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="510" xlink:href="#SvgjsPath1002" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="510" xlink:href="#SvgjsPath1003" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="510" xlink:href="#SvgjsPath1005" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="510" xlink:href="#SvgjsPath1005" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="510" xlink:href="#SvgjsPath1002" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="510" xlink:href="#SvgjsPath1004" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="510" xlink:href="#SvgjsPath1006"
                        y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="510" xlink:href="#SvgjsPath1001" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="510" xlink:href="#SvgjsPath1002" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="510" xlink:href="#SvgjsPath1002" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="570" xlink:href="#SvgjsPath1005" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="570" xlink:href="#SvgjsPath1002" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="570" xlink:href="#SvgjsPath1001" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="570" xlink:href="#SvgjsPath1001" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="570" xlink:href="#SvgjsPath1001" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="570" xlink:href="#SvgjsPath1001" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="570" xlink:href="#SvgjsPath1006"
                        y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="570" xlink:href="#SvgjsPath1005" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="570" xlink:href="#SvgjsPath1001" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="570" xlink:href="#SvgjsPath1002" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="630" xlink:href="#SvgjsPath1002" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="630" xlink:href="#SvgjsPath1005" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="630" xlink:href="#SvgjsPath1005" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="630" xlink:href="#SvgjsPath1002" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="630" xlink:href="#SvgjsPath1001" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="630" xlink:href="#SvgjsPath1006"
                        y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="630" xlink:href="#SvgjsPath1002" y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="630" xlink:href="#SvgjsPath1006"
                        y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="630" xlink:href="#SvgjsPath1001" y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="630" xlink:href="#SvgjsPath1005" y="570"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="690" xlink:href="#SvgjsPath1001" y="30"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="690" xlink:href="#SvgjsPath1005" y="90"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="690" xlink:href="#SvgjsPath1002" y="150"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="690" xlink:href="#SvgjsPath1002" y="210"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="690" xlink:href="#SvgjsPath1005" y="270"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="690" xlink:href="#SvgjsPath1001" y="330"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="690" xlink:href="#SvgjsPath1003" y="390"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="690" xlink:href="#SvgjsPath1003" y="450"></use>
                    <use stroke="rgba(32, 43, 61, 1)" stroke-width="3" x="690" xlink:href="#SvgjsPath1006"
                        y="510"></use>
                    <use stroke="rgba(32, 43, 61, 1)" x="690" xlink:href="#SvgjsPath1003" y="570"></use>
                </symbol>
            </svg>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 grid-cols-1 gap-5">
        @if ($komoditasSummary)
            @include('dashboards.partials.status-card', [
                'summary' => $komoditasSummary,
                'title' => __('Status Verifikasi Komoditas'),
                'icon' => 'package',
            ])
        @endif

        @if ($assesmentSummary)
            @include('dashboards.partials.status-card', [
                'summary' => $assesmentSummary,
                'title' => __('Status Assesment'),
                'icon' => 'clipboard-check',
            ])
        @endif
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/dashboard-tour.js'])
@endsection
