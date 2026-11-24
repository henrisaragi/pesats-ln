@extends('layouts.base', ['title' => 'Login'])

@section('css')
@endsection

@php
    $activeLocale = app()->getLocale();
    $locales = [
        'id' => ['label' => 'Indonesia', 'flag' => 'indonesia.svg'],
        'en' => ['label' => 'English', 'flag' => 'us.jpg'],
    ];
    $currentYear = now()->year;
    $loginYears = range($currentYear, $currentYear - 5);
@endphp

@section('content')
    {{-- Modal "akun login di perangkat lain" (satu sesi aktif per akun, lihat claude/auth-task.md)
         pakai mekanisme flash session('error') yang sudah ada, dipicu otomatis lewat
         data-flash-* di bawah ini. --}}
    <x-alert-modal />

    <div
        class="relative flex flex-row w-full overflow-hidden bg-gradient-to-r from-blue-900 h-screen to-blue-800 dark:to-blue-900 dark:from-blue-950">
        <div class="absolute inset-0 opacity-20">
            <img alt="" src="{{ asset('images/modern.svg') }}" />
        </div>
        <div class="mx-4 m-4 w-160 py-14 px-10 bg-card flex justify-center rounded-md text-center relative z-10">
            <div class="flex flex-col h-full w-full">
                <div class="flex justify-end">
                    <!-- Language Dropdown Button -->
                    <div class="hs-dropdown [--placement:bottom-right] relative inline-flex">
                        <button aria-expanded="false" aria-haspopup="menu" aria-label="Dropdown"
                            class="hs-dropdown-toggle py-2 px-4 bg-transparent border border-default-200 text-default-600 hover:border-primary rounded-md hover:text-primary font-medium text-sm gap-2 flex items-center"
                            type="button">
                            <img alt="" class="size-5 rounded-full"
                                src="{{ asset('images/flags/'.$locales[$activeLocale]['flag']) }}" />
                            {{ __($locales[$activeLocale]['label']) }}
                        </button>
                        <div aria-labelledby="dropdown-menu" aria-orientation="vertical" class="hs-dropdown-menu"
                            role="menu">
                            @foreach ($locales as $code => $locale)
                                <a class="flex items-center gap-x-3.5 py-1.5 font-medium px-3 text-default-600 hover:bg-default-150 rounded{{ $code === $activeLocale ? ' bg-default-150' : '' }}"
                                    href="{{ route('locale.switch', $code) }}">
                                    <img alt="" class="size-4 rounded-full"
                                        src="{{ asset('images/flags/'.$locale['flag']) }}" />
                                    {{ __($locale['label']) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="my-21">
                    <div class="mt-10">
                        <div class="mt-10 w-100 mx-auto">
                            <form action="{{ route('login') }}" class="text-left w-full mt-10" method="POST">
                                @csrf
                                @if ($errors->any())
                                    <div class="mb-4 p-3 rounded-md bg-danger/10 text-danger text-sm text-left">
                                        <ul class="list-disc list-inside">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="mb-4">
                                    <label class="block font-medium text-default-900 text-sm mb-2"
                                        for="tahun-aktif">{{ __('Tahun Aktif') }}</label>
                                    <select class="form-input" id="tahun-aktif">
                                        @foreach ($loginYears as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="block font-medium text-default-900 text-sm mb-2"
                                        for="login">{{ __('Username/Email') }}</label>
                                    <input class="form-input" id="login" name="login"
                                        placeholder="{{ __('Masukkan Username atau Email') }}" type="text"
                                        value="{{ old('login') }}" />
                                </div>
                                <div class="mb-4">
                                    <a class="text-primary font-medium text-sm mb-2 float-end"
                                        href="{{ route('second', ['auth', 'modern-reset-password']) }}">{{ __('Lupa Password ?') }}</a>
                                    <label class="block font-medium text-default-900 text-sm mb-2"
                                        for="password">{{ __('Password') }}</label>
                                    <input class="form-input" id="password" name="password"
                                        placeholder="{{ __('Masukkan Password') }}" type="password" />
                                </div>
                                <div class="flex items-center gap-2 mb-4">
                                    <input class="form-checkbox" id="checkbox-1" name="remember" type="checkbox" />
                                    <label class="text-default-900 text-sm font-medium"
                                        for="checkbox-1">{{ __('Ingat Saya') }}</label>
                                </div>
                                <div class="mt-10 text-center">
                                    <button class="btn bg-primary text-white w-full"
                                        type="submit">{{ __('Masuk') }}</button>
                                </div>
                                <div
                                    class="my-9 relative text-center before:absolute before:top-2.5 before:left-0 before:border-t before:border-t-default-200 before:w-full before:h-0.5 before:right-0 before:-z-0">
                                    <h4
                                        class="relative z-1 py-0.5 px-2 inline-block font-medium bg-card text-default-500 rounded-md">
                                        {{ __('Masuk dengan') }}</h4>
                                </div>

                                <div class="mt-10 text-center">
                                    <p class="text-base text-default-500">{{ __('Belum punya akun ?') }}
                                        <a class="font-semibold underline hover:text-primary transition duration-200"
                                            href="{{ route('register') }}"> {{ __('Daftar') }}</a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="mt-5">
                    <span class="text-sm text-default-500">
                        <i class="iconify lucide--copyright align-middle"></i>
                        2026 PeSATS. Crafted with
                        <i class="iconify tabler--heart-filled align-middle text-danger"></i>
                        by
                        <a class="text-default-800 hover:text-primary transition duration-200 underline"
                            href="https://pesats.ksdae.kehutanan.go.id" target="_blank">Direktorat Konservasi Spesies &
                            Genetik</a>
                    </span>
                </div>
            </div>
        </div>
        <div class="relative z-10 flex items-center justify-center min-h-screen px-10 py-14 grow">
            <div>
                <a class="" href="{{ route('dashboards.index') }}">
                    <img alt="" class="h-7 mb-14 mx-auto block" src="{{ asset('images/logo-light.png') }}" />
                </a>
                <img alt="" class="mx-auto rounded-xl block object-cover w-md" src="{{ asset('images/auth-modern.png') }}" />
                <div class="mt-10 text-center">
                    <h3 class="mb-3 text-blue-50 text-2xl font-semibold text-center">{{ __('Selamat Datang Kembali') }}
                    </h3>
                    <p class="text-blue-300 text-base w-2xl text-center">
                        {{ __('Masuk untuk mengelola perizinan dan transaksi satwa liar Anda.') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var yearSelect = document.getElementById('tahun-aktif');

            if (!yearSelect) {
                return;
            }

            var savedYear = localStorage.getItem('tahun_aktif');

            if (savedYear) {
                yearSelect.value = savedYear;
            }

            // Simpan juga pilihan default (tanpa event change) supaya tahun_aktif tidak pernah null setelah login.
            yearSelect.form?.addEventListener('submit', function() {
                localStorage.setItem('tahun_aktif', yearSelect.value);
            });

            yearSelect.addEventListener('change', function() {
                localStorage.setItem('tahun_aktif', this.value);
            });
        });
    </script>
@endsection
