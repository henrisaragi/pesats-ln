<?php

declare(strict_types=1);

namespace App\Services\Insw;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin authenticated GET wrapper around INSW's ref/daerah endpoints, mirroring the
 * requestToINSW() pattern shown in the task spec (bearer token + {error, data, message}
 * response shape) but backed by the reusable InswAuthService instead of a one-off token call.
 */
class InswClient
{
    public function __construct(private readonly InswAuthService $auth) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{error: bool, data: mixed, message: ?string}
     */
    public function get(string $endpoint, array $params = []): array
    {
        $baseUrl = config('services.insw.base_url');

        if (empty($baseUrl)) {
            return ['error' => true, 'data' => null, 'message' => 'INSW_API_BASE_URL belum dikonfigurasi.'];
        }

        try {
            $response = $this->request($endpoint, $params, $this->auth->getAccessToken());

            if ($response->status() === 401) {
                $response = $this->request($endpoint, $params, $this->auth->getAccessToken(forceRefresh: true));
            }

            if ($response->failed()) {
                $payload = $response->json();

                // INSW uses HTTP 404 for a legitimate "zero results for this query" case too,
                // still marked successful via its own code:'01' indicator (e.g. a kota with no
                // kecamatan on file). Only treat it as a real error when that indicator is absent.
                if ($response->status() === 404 && is_array($payload) && ($payload['code'] ?? null) === '01') {
                    return ['error' => false, 'data' => $payload['data'] ?? [], 'message' => null];
                }

                Log::error("Gagal request ke INSW: {$endpoint}", ['status' => $response->status(), 'body' => $response->body()]);

                return ['error' => true, 'data' => null, 'message' => "Gagal mengambil data dari INSW: {$endpoint} (HTTP {$response->status()})."];
            }

            $payload = $response->json();

            // INSW's own success indicator, per the reference implementation: HTTP 200 can still
            // carry a business-level failure via a non-'01' `code` field in the body.
            if (is_array($payload) && array_key_exists('code', $payload) && $payload['code'] !== '01') {
                Log::error("Respons INSW menandakan gagal: {$endpoint}", ['code' => $payload['code'], 'body' => $response->body()]);

                return ['error' => true, 'data' => null, 'message' => $payload['message'] ?? "Respons tidak valid dari INSW: {$endpoint}."];
            }

            return ['error' => false, 'data' => $response->json('data', $payload), 'message' => null];
        } catch (InswAuthException $e) {
            return ['error' => true, 'data' => null, 'message' => $e->getMessage()];
        } catch (ConnectionException $e) {
            Log::error("Koneksi ke INSW gagal: {$endpoint}", ['exception' => $e->getMessage()]);

            return ['error' => true, 'data' => null, 'message' => 'Terjadi masalah koneksi dengan sistem INSW.'];
        }
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function request(string $endpoint, array $params, string $token): Response
    {
        return Http::withHeaders(['Authorization' => "Bearer {$token}"])
            ->timeout(60)
            ->get(rtrim((string) config('services.insw.base_url'), '/')."/{$endpoint}", $params);
    }
}
