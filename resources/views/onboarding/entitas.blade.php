@extends('layouts.base', ['title' => 'Lengkapi Data - Data Entitas'])

@section('css')

@endsection

@section('content')
    <x-onboarding-shell :step="2" :title="__('Data Entitas')"
                         :subtitle="__('Lengkapi nama dan alamat entitas Anda.')">
        <form action="{{ route('onboarding.entitas.store') }}" data-tour="entitas-form" method="POST">
            @csrf
            <div class="flex flex-col gap-y-4">
                <div>
                    <label class="inline-block mb-2 text-sm font-medium" for="onboarding-entitas-name">
                        {{ __('Nama Entitas') }}
                    </label>
                    <input class="form-input" data-tour="entitas-name" id="onboarding-entitas-name" name="name"
                           type="text" value="{{ old('name', $entity->name) }}"/>
                    @if ($errors->has('name'))
                        <p class="mt-1 text-xs text-danger">{{ $errors->first('name') }}</p>
                    @endif
                </div>
                <div>
                    <label class="inline-block mb-2 text-sm font-medium" for="onboarding-entitas-address">
                        {{ __('Alamat') }}
                    </label>
                    <textarea class="form-input" data-tour="entitas-address" id="onboarding-entitas-address"
                              name="address" rows="2">{{ old('address', $entity->address) }}</textarea>
                    @if ($errors->has('address'))
                        <p class="mt-1 text-xs text-danger">{{ $errors->first('address') }}</p>
                    @endif
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" data-tour="entitas-region">
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="onboarding-entitas-provinsi">
                            {{ __('Provinsi') }}
                        </label>
                        <x-province-select id="onboarding-entitas-provinsi" name="provinsi_id"
                                            :selected="old('provinsi_id', $entity->provinsi_id)"/>
                        @if ($errors->has('provinsi_id'))
                            <p class="mt-1 text-xs text-danger">{{ $errors->first('provinsi_id') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="onboarding-entitas-kota">
                            {{ __('Kota') }}
                        </label>
                        <x-city-select depends-on="onboarding-entitas-provinsi" id="onboarding-entitas-kota"
                                        name="kota_id" :selected="old('kota_id', $entity->kota_id)"/>
                        @if ($errors->has('kota_id'))
                            <p class="mt-1 text-xs text-danger">{{ $errors->first('kota_id') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="onboarding-entitas-kecamatan">
                            {{ __('Kecamatan') }}
                        </label>
                        <x-district-select depends-on="onboarding-entitas-kota" id="onboarding-entitas-kecamatan"
                                            name="kecamatan_id" :selected="old('kecamatan_id', $entity->kecamatan_id)"/>
                        @if ($errors->has('kecamatan_id'))
                            <p class="mt-1 text-xs text-danger">{{ $errors->first('kecamatan_id') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="onboarding-entitas-kelurahan">
                            {{ __('Kelurahan') }}
                        </label>
                        <x-subdistrict-select depends-on="onboarding-entitas-kecamatan"
                                               id="onboarding-entitas-kelurahan" name="kelurahan_id"
                                               :selected="old('kelurahan_id', $entity->kelurahan_id)"/>
                        @if ($errors->has('kelurahan_id'))
                            <p class="mt-1 text-xs text-danger">{{ $errors->first('kelurahan_id') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="mt-8 flex justify-between">
                <a class="btn bg-default-150" href="{{ route('onboarding.type.show') }}">
                    {{ __('Sebelumnya') }}
                </a>
                <button class="btn bg-primary text-white px-6" data-tour="entitas-next" type="submit">
                    {{ __('Selanjutnya') }}
                </button>
            </div>
        </form>
    </x-onboarding-shell>
@endsection

@section('scripts')
    @vite(['resources/js/onboarding-tour.js'])
@endsection
