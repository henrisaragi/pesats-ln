@extends('layouts.base', ['title' => 'Lengkapi Data - Detail'])

@section('css')

@endsection

@section('content')
    <x-onboarding-shell :step="3" :title="__('Detail Entitas')"
                         :subtitle="__('Lengkapi data pendukung sesuai jenis entitas Anda.')">
        <form action="{{ route('onboarding.detail.store') }}" data-tour="detail-form" method="POST">
            @csrf
            <div class="flex flex-col gap-y-4" data-tour="detail-fields">
                @forelse ($fields as $field => $meta)
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="onboarding-detail-{{ $field }}">
                            {{ __($meta['label']) }}
                        </label>
                        <input class="form-input" id="onboarding-detail-{{ $field }}" name="{{ $field }}"
                               type="text" value="{{ old($field, $profile?->{$field}) }}"/>
                        @if ($errors->has($field))
                            <p class="mt-1 text-xs text-danger">{{ $errors->first($field) }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-default-500">{{ __('Tidak ada data tambahan yang diperlukan.') }}</p>
                @endforelse
            </div>
            <div class="mt-8 flex justify-between">
                <a class="btn bg-default-150" href="{{ route('onboarding.entitas.show') }}">
                    {{ __('Sebelumnya') }}
                </a>
                <button class="btn bg-primary text-white px-6" data-tour="detail-next" type="submit">
                    {{ __('Selanjutnya') }}
                </button>
            </div>
        </form>
    </x-onboarding-shell>
@endsection

@section('scripts')
    @vite(['resources/js/onboarding-tour.js'])
@endsection
