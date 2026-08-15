@extends('layouts.base', ['title' => 'Lengkapi Data - Selesai'])

@section('css')

@endsection

@section('content')
    <x-onboarding-shell :title="__('Data Anda Berhasil Disimpan')">
        <div class="text-center py-4" data-tour="complete">
            <div
                class="mx-auto flex items-center justify-center size-16 rounded-full bg-success/10 text-success mb-5">
                <i class="size-8" data-lucide="check-circle-2"></i>
            </div>
            <p class="text-default-500 text-sm mb-8">
                {{ __('Terima kasih, data entitas Anda sudah lengkap. Anda sekarang dapat menggunakan seluruh fitur aplikasi.') }}
            </p>
            <a class="btn bg-primary text-white px-6" href="{{ route('second', ['dashboards', 'index']) }}">
                {{ __('Ke Dashboard') }}
            </a>
        </div>
    </x-onboarding-shell>
@endsection

@section('scripts')
    @vite(['resources/js/onboarding-tour.js'])
@endsection
