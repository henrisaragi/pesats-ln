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
                        {{ $nameLabel }}
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
                <div data-tour="entitas-coordinates">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium">{{ __('Titik Koordinat') }}</span>
                        <button class="btn btn-sm bg-primary/10 text-primary" id="onboarding-entitas-locate"
                                type="button">
                            {{ __('Ambil Lokasi Saat Ini') }}
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="inline-block mb-2 text-sm font-medium" for="onboarding-entitas-latitude">
                                {{ __('Latitude') }}
                            </label>
                            <input class="form-input" id="onboarding-entitas-latitude" inputmode="decimal"
                                   name="latitude" placeholder="-6.2000000" type="text"
                                   value="{{ old('latitude', $entity->latitude) }}"/>
                            @if ($errors->has('latitude'))
                                <p class="mt-1 text-xs text-danger">{{ $errors->first('latitude') }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="inline-block mb-2 text-sm font-medium" for="onboarding-entitas-longitude">
                                {{ __('Longitude') }}
                            </label>
                            <input class="form-input" id="onboarding-entitas-longitude" inputmode="decimal"
                                   name="longitude" placeholder="106.8166667" type="text"
                                   value="{{ old('longitude', $entity->longitude) }}"/>
                            @if ($errors->has('longitude'))
                                <p class="mt-1 text-xs text-danger">{{ $errors->first('longitude') }}</p>
                            @endif
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-default-500" id="onboarding-entitas-locate-status"
                       data-locating="{{ __('Mengambil lokasi...') }}"
                       data-success="{{ __('Lokasi berhasil diambil.') }}"
                       data-unsupported="{{ __('Browser Anda tidak mendukung pengambilan lokasi. Isi koordinat secara manual.') }}"
                       data-denied="{{ __('Gagal mengambil lokasi. Izinkan akses lokasi di browser atau isi koordinat secara manual.') }}">
                        {{ __('Klik tombol untuk mengisi otomatis dari lokasi perangkat, atau isi secara manual.') }}
                    </p>
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
    @vite(['resources/js/onboarding-tour.js', 'resources/js/pages/onboarding-entitas.js'])
@endsection
