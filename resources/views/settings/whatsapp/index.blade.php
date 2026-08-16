@extends('layouts.vertical', ['title' => __('Whatsapp')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Settings'), 'title' => __('Whatsapp')])

    <div class="card max-w-lg">
        <div class="card-header">
            <h6 class="card-title">{{ __('Notifikasi WhatsApp') }}</h6>
        </div>
        <form action="{{ route('settings.whatsapp.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="p-4">
                <div class="flex items-center gap-2">
                    <input name="is_enabled" type="hidden" value="0">
                    <input class="form-checkbox" id="whatsapp-is-enabled" name="is_enabled" type="checkbox"
                           value="1" @checked($isEnabled)/>
                    <label class="text-sm font-medium" for="whatsapp-is-enabled">
                        {{ __('Aktifkan Notifikasi WhatsApp') }}
                    </label>
                </div>
                <p class="mt-2 text-xs text-default-400">
                    {{ __('Jika diaktifkan, notifikasi tertentu (mis. permohonan baru, verifikasi) juga akan dikirim via WhatsApp ke nomor pengguna yang terdaftar.') }}
                </p>
            </div>
            <div class="p-4 flex justify-end border-t border-dashed border-default-900/10">
                <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
            </div>
        </form>
    </div>
@endsection
