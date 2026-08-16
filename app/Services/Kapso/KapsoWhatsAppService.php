<?php

declare(strict_types=1);

namespace App\Services\Kapso;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reusable WhatsApp broadcast client for Kapso's Meta WhatsApp Business proxy API.
 * Pure "send a message" concern - it does not know about the app-wide WhatsApp on/off
 * toggle (see App\Models\WhatsAppSetting), that gating lives in the caller
 * (App\Jobs\SendWhatsAppNotificationJob), so this class stays safe to call from anywhere.
 */
class KapsoWhatsAppService
{
    /**
     * @return array{error: bool, data: mixed, message: ?string}
     */
    public function send(string $to, string $message): array
    {
        $apiKey = config('services.kapso.api_key');
        $baseUrl = config('services.kapso.base_url');
        $phoneNumberId = config('services.kapso.phone_number_id');

        if (empty($apiKey) || empty($baseUrl) || empty($phoneNumberId)) {
            return ['error' => true, 'data' => null, 'message' => 'Konfigurasi Kapso WhatsApp belum lengkap.'];
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $apiKey])
                ->timeout(30)
                ->post(
                    rtrim((string) $baseUrl, '/')."/meta/whatsapp/v24.0/{$phoneNumberId}/messages",
                    [
                        'messaging_product' => 'whatsapp',
                        'to' => $to,
                        'type' => 'text',
                        'text' => ['body' => $message],
                    ]
                );

            if ($response->failed()) {
                Log::error("Gagal kirim pesan WhatsApp via Kapso ke {$to}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'error' => true,
                    'data' => null,
                    'message' => "Gagal mengirim pesan WhatsApp (HTTP {$response->status()}).",
                ];
            }

            return ['error' => false, 'data' => $response->json(), 'message' => null];
        } catch (ConnectionException $e) {
            Log::error("Koneksi ke Kapso gagal saat mengirim WhatsApp ke {$to}", ['exception' => $e->getMessage()]);

            return ['error' => true, 'data' => null, 'message' => 'Terjadi masalah koneksi dengan Kapso.'];
        }
    }
}
