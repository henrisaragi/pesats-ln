<x-mail::message>
# {{ __('Kode Verifikasi Login') }}

{{ __('Gunakan kode berikut untuk menyelesaikan proses login Anda:') }}

<x-mail::panel>
# {{ $otpCode }}
</x-mail::panel>

{{ __('Kode ini berlaku selama :minutes menit dan hanya bisa digunakan satu kali.', ['minutes' => $expiresInMinutes]) }}

{{ __('Jika Anda tidak meminta kode ini, abaikan email ini.') }}

{{ __('Terima kasih,') }}<br>
{{ config('app.name') }}
</x-mail::message>
