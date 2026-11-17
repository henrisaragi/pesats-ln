@extends('layouts.vertical', ['title' => __('Edit Pertanyaan Assesment')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Master Data'), 'title' => $assesment->judul])

    <div class="mb-4">
        <a class="text-primary hover:underline inline-flex items-center gap-1" href="{{ route('assesment.pertanyaan.index', $assesment) }}">
            <i class="size-4" data-lucide="arrow-left"></i>{{ __('Kembali ke Daftar Pertanyaan') }}
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Edit Pertanyaan') }}</h6>
        </div>
        @include('assesment.pertanyaan._form', [
            'action' => route('assesment.pertanyaan.update', [$assesment, $pertanyaan]),
            'method' => 'PUT',
            'submitLabel' => __('Simpan Perubahan'),
        ])
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/assesment-pertanyaan-form.js'])
@endsection
