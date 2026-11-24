{{--
    Satu template dipakai untuk DUA alur (parameter $mode, lihat claude/auth-task.md):
    - 'otp': verifikasi kode OTP login (6 digit, dikirim ke email) - AuthenticationController::showOtp()/verifyOtp().
    - 'password': input ulang password setelah sesi idle 1 jam, TANPA OTP - ReauthenticateController::show()/store().
    Chrome (dropdown bahasa, panel kanan) disalin dari resources/views/auth/_modern-login.blade.php
    supaya konsisten dengan halaman login yang sudah ada.
--}}
@extends('layouts.base', ['title' => $mode === 'otp' ? __('Verifikasi OTP') : __('Konfirmasi Sesi')])

@section('css')
@endsection

@php
    $activeLocale = app()->getLocale();
    $locales = [
        'id' => ['label' => 'Indonesia', 'flag' => 'indonesia.svg'],
        'en' => ['label' => 'English', 'flag' => 'us.jpg'],
    ];
@endphp

@section('content')
    <div
        class="relative flex flex-row w-full overflow-hidden bg-gradient-to-r from-blue-900 h-screen to-blue-800 dark:to-blue-900 dark:from-blue-950">
        <div class="absolute inset-0 opacity-20">
            <img alt="" src="{{ asset('images/modern.svg') }}"/>
        </div>
        <div class="mx-4 m-4 w-160 py-14 px-10 bg-card flex justify-center rounded-md text-center relative z-10">
            <div class="flex flex-col h-full w-full">
                <div class="flex justify-end">
                    <!-- Language Dropdown Button -->
                    <div class="hs-dropdown [--placement:bottom-right] relative inline-flex">
                        <button aria-expanded="false" aria-haspopup="menu" aria-label="Dropdown"
                                class="hs-dropdown-toggle py-2 px-4 bg-transparent border border-default-200 text-default-600 hover:border-primary rounded-md hover:text-primary font-medium text-sm gap-2 flex items-center"
                                type="button">
                            <img alt="" class="size-5 rounded-full" src="{{ asset('images/flags/'.$locales[$activeLocale]['flag']) }}"/>
                            {{ __($locales[$activeLocale]['label']) }}
                        </button>
                        <div aria-labelledby="dropdown-menu" aria-orientation="vertical" class="hs-dropdown-menu"
                             role="menu">
                            @foreach ($locales as $code => $locale)
                                <a class="flex items-center gap-x-3.5 py-1.5 font-medium px-3 text-default-600 hover:bg-default-150 rounded{{ $code === $activeLocale ? ' bg-default-150' : '' }}"
                                   href="{{ route('locale.switch', $code) }}">
                                    <img alt="" class="size-4 rounded-full" src="{{ asset('images/flags/'.$locale['flag']) }}"/>
                                    {{ __($locale['label']) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="my-auto">
                    <div class="mt-8">
                        @if ($mode === 'otp')
                            <h4 class="mb-2 text-primary text-xl font-semibold">{{ __('Verifikasi Kode OTP') }}</h4>
                            <p class="text-base mb-8 text-default-500">
                                {{ __('Masukkan kode 6 digit yang dikirim ke :email', ['email' => $maskedEmail]) }}
                            </p>
                        @else
                            <h4 class="mb-2 text-primary text-xl font-semibold">{{ __('Sesi Anda Berakhir') }}</h4>
                            <p class="text-base mb-8 text-default-500">
                                {{ __('Sesi Anda berakhir karena tidak ada aktivitas. Masukkan kembali password untuk melanjutkan, :name.', ['name' => $reauthUserName]) }}
                            </p>
                        @endif
                    </div>

                    <div class="w-100 mx-auto text-left">
                        @if ($errors->any())
                            <div class="mb-4 p-3 rounded-md bg-danger/10 text-danger text-sm text-left">
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="mb-4 p-3 rounded-md bg-success/10 text-success text-sm text-left">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($mode === 'otp')
                            <form action="{{ route('login.otp.verify') }}" method="POST" data-otp-form>
                                @csrf
                                <input data-otp-combined name="otp" type="hidden">
                                <div class="grid grid-cols-6 gap-2" data-otp-boxes>
                                    @for ($i = 0; $i < 6; $i++)
                                        <input autocomplete="one-time-code" class="form-input text-center"
                                               data-otp-box inputmode="numeric" maxlength="1" pattern="[0-9]*"
                                               type="text"/>
                                    @endfor
                                </div>
                                <div class="mt-10">
                                    <button class="btn bg-primary text-white w-full" type="submit">
                                        {{ __('Verifikasi') }}
                                    </button>
                                </div>
                            </form>
                            <form action="{{ route('login.otp.resend') }}" class="mt-4 text-center" method="POST">
                                @csrf
                                <button class="text-primary font-medium text-sm underline" type="submit">
                                    {{ __('Kirim ulang kode OTP') }}
                                </button>
                            </form>
                            <div class="mt-4 text-center">
                                <a class="text-sm text-default-500 hover:text-primary transition duration-200"
                                   href="{{ route('login') }}">{{ __('Kembali ke halaman login') }}</a>
                            </div>
                        @else
                            <form action="{{ route('reauthenticate.store') }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label class="block font-medium text-default-900 text-sm mb-2" for="password">
                                        {{ __('Password') }}
                                    </label>
                                    <input autofocus class="form-input" id="password"
                                           name="password" placeholder="{{ __('Masukkan Password') }}"
                                           type="password"/>
                                </div>
                                <div class="mt-10">
                                    <button class="btn bg-primary text-white w-full" type="submit">
                                        {{ __('Lanjutkan') }}
                                    </button>
                                </div>
                            </form>
                            <div class="mt-4 text-center">
                                <a class="text-sm text-default-500 hover:text-primary transition duration-200"
                                   href="{{ route('login') }}">{{ __('Bukan Anda? Masuk dengan akun lain') }}</a>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="mt-5">
<span class="font-base text-sm text-default-500">
<i class="iconify lucide--copyright align-middle"></i>
                        {{ now()->year }} PeSATS - Ditjen KSDAE
</span>
                </div>
            </div>
        </div>
        <div class="relative z-10 flex items-center justify-center min-h-screen px-10 py-14 grow">
            <div>
                <a href="{{ route('dashboards.index') }}">
                    <img alt="" class="h-7 mb-14 mx-auto block" src="{{ asset('images/logo-light.png') }}"/>
                </a>
                <img alt="" class="mx-auto rounded-xl block object-cover w-md" src="{{ asset('images/auth-modern.png') }}"/>
                <div class="mt-10 text-center">
                    <h3 class="mb-3 text-blue-50 text-2xl font-semibold text-center">{{ __('Selamat Datang Kembali') }}</h3>
                    <p class="text-blue-300 text-base w-2xl text-center">{{ __('Masuk untuk mengelola perizinan dan transaksi satwa liar Anda.') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @if ($mode === 'otp')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.querySelector('[data-otp-form]');
                var boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
                var combined = document.querySelector('[data-otp-combined]');

                if (!form || boxes.length === 0 || !combined) {
                    return;
                }

                function syncCombined() {
                    combined.value = boxes.map(function (box) { return box.value; }).join('');
                }

                boxes.forEach(function (box, index) {
                    box.addEventListener('input', function () {
                        box.value = box.value.replace(/[^0-9]/g, '').slice(0, 1);

                        if (box.value && boxes[index + 1]) {
                            boxes[index + 1].focus();
                        }

                        syncCombined();
                    });

                    box.addEventListener('keydown', function (event) {
                        if (event.key === 'Backspace' && !box.value && boxes[index - 1]) {
                            boxes[index - 1].focus();
                        }
                    });

                    box.addEventListener('paste', function (event) {
                        var pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');

                        if (!pasted) {
                            return;
                        }

                        event.preventDefault();

                        pasted.slice(0, boxes.length).split('').forEach(function (char, i) {
                            if (boxes[i]) {
                                boxes[i].value = char;
                            }
                        });

                        var next = boxes[Math.min(pasted.length, boxes.length - 1)];
                        next?.focus();
                        syncCombined();
                    });
                });

                form.addEventListener('submit', syncCombined);
                boxes[0].focus();
            });
        </script>
    @endif
@endsection
