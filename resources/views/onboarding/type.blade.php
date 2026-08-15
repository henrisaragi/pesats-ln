@extends('layouts.base', ['title' => 'Lengkapi Data - Jenis Entitas'])

@section('css')

@endsection

@php
    $entityTypes = [
        'perusahaan' => ['label' => __('Perusahaan'), 'icon' => 'building-2', 'desc' => __('Badan usaha atau perseroan')],
        'penangkaran' => ['label' => __('Penangkaran'), 'icon' => 'paw-print', 'desc' => __('Fasilitas penangkaran satwa')],
        'perorangan' => ['label' => __('Perorangan'), 'icon' => 'user', 'desc' => __('Individu / perseorangan')],
        'upt_ksda' => ['label' => __('UPT KSDA'), 'icon' => 'landmark', 'desc' => __('Unit Pelaksana Teknis KSDA')],
    ];
@endphp

@section('content')
    <x-onboarding-shell :step="1" :title="__('Lengkapi Data Entitas')"
                         :subtitle="__('Pilih jenis entitas Anda untuk melanjutkan.')">
        <form action="{{ route('onboarding.type.store') }}" data-tour="type-form" method="POST">
            @csrf
            @if ($errors->has('entity_type'))
                <p class="mb-4 text-sm text-danger text-center">{{ $errors->first('entity_type') }}</p>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" data-tour="type-options">
                @foreach ($entityTypes as $value => $meta)
                    <label class="relative block cursor-pointer">
                        <input class="peer sr-only" name="entity_type" type="radio" value="{{ $value }}"
                               @checked(old('entity_type', $entity?->entity_type) === $value)/>
                        <div
                            class="flex flex-col items-center text-center gap-2 p-5 rounded-xl border-2 border-default-200 transition-all peer-checked:border-primary peer-checked:bg-primary/5 hover:border-primary/40">
                            <div class="flex items-center justify-center size-12 rounded-full bg-primary/10 text-primary">
                                <i class="size-6" data-lucide="{{ $meta['icon'] }}"></i>
                            </div>
                            <span class="font-semibold text-default-800">{{ $meta['label'] }}</span>
                            <span class="text-xs text-default-500">{{ $meta['desc'] }}</span>
                        </div>
                        <div
                            class="absolute top-2 end-2 hidden peer-checked:flex items-center justify-center size-5 rounded-full bg-primary text-white">
                            <i class="size-3" data-lucide="check"></i>
                        </div>
                    </label>
                @endforeach
            </div>
            <div class="mt-8 flex justify-end">
                <button class="btn bg-primary text-white px-6" data-tour="type-next" type="submit">
                    {{ __('Selanjutnya') }}
                </button>
            </div>
        </form>
    </x-onboarding-shell>
@endsection

@section('scripts')
    @vite(['resources/js/onboarding-tour.js'])
@endsection
