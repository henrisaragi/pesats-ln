<?php

declare(strict_types=1);

namespace App\Services\Insw;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reusable INSW OAuth2 token handling.
 *
 * Based on a working `accessToken()` reference from a previous KSDAE app (see
 * claude/sinkron-task.md). INSW's "authorize" endpoint is a server-side-only stand-in for a
 * normal browser authorization-code flow: it accepts `provision_key` + `authenticated_userid`
 * as pre-shared trust credentials (no real user redirect happens) and returns a JSON body
 * whose `redirect_uri` field embeds a `?code=...` query string. That code is then exchanged
 * for the actual access token at a second "token" endpoint.
 *
 * The reference POSTs a plain PHP array via curl's CURLOPT_POSTFIELDS, which curl sends as
 * multipart/form-data (not JSON, not urlencoded) - replicated here via Http::asMultipart() to
 * match exactly what's proven to work, since no official INSW API docs were available.
 */
class InswAuthService
{
    private const CACHE_KEY = 'insw_access_token';

    private const EXPIRY_BUFFER_SECONDS = 60;

    public function getAccessToken(bool $forceRefresh = false): string
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $cached = Cache::get(self::CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $code = $this->requestAuthorizationCode();

        return $this->exchangeCodeForToken($code);
    }

    private function requestAuthorizationCode(): string
    {
        $config = config('services.insw');

        foreach (['auth_url', 'client_id', 'provision_key', 'user_id', 'redirect_uri'] as $key) {
            if (empty($config[$key])) {
                throw new InswAuthException("Konfigurasi INSW_{$this->envKey($key)} belum diisi di .env.");
            }
        }

        $response = Http::asMultipart()
            ->timeout(30)
            ->post($config['auth_url'], $this->multipartFields([
                'client_id' => $config['client_id'],
                'response_type' => 'code',
                'scope' => 'session',
                'provision_key' => $config['provision_key'],
                'authenticated_userid' => $config['user_id'],
                'redirect_uri' => $config['redirect_uri'],
            ]));

        if ($response->failed()) {
            Log::error('Gagal request authorize INSW.', ['status' => $response->status(), 'body' => $response->body()]);

            throw new InswAuthException('Gagal menghubungi endpoint authorize INSW (HTTP '.$response->status().').');
        }

        $payload = $response->json();
        $code = $this->extractCode($payload);

        if ($code === null) {
            Log::error('Tidak bisa menemukan kode otorisasi dari respons authorize INSW.', [
                'available_keys' => is_array($payload) ? array_keys($payload) : [],
                'body' => $response->body(),
            ]);

            throw new InswAuthException('Respons authorize INSW tidak dikenali. Cek log untuk struktur respons yang sebenarnya.');
        }

        return $code;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractCode(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        $redirectUri = $payload['redirect_uri'] ?? null;

        if (is_string($redirectUri) && $redirectUri !== '') {
            $query = [];
            parse_str((string) (parse_url($redirectUri, PHP_URL_QUERY) ?? ''), $query);

            if (isset($query['code']) && is_string($query['code']) && $query['code'] !== '') {
                return $query['code'];
            }
        }

        // Fallback in case INSW ever returns the code directly instead of wrapped in redirect_uri.
        $directCode = $payload['code'] ?? null;

        return is_string($directCode) && $directCode !== '' ? $directCode : null;
    }

    private function exchangeCodeForToken(string $code): string
    {
        $config = config('services.insw');

        if (empty($config['token_url']) || empty($config['client_secret'])) {
            throw new InswAuthException('Konfigurasi INSW_TOKEN_URL atau INSW_CLIENT_SECRET belum diisi di .env.');
        }

        $response = Http::asMultipart()
            ->timeout(30)
            ->post($config['token_url'], $this->multipartFields([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
            ]));

        if ($response->failed()) {
            Log::error('Gagal menukar kode otorisasi INSW dengan access token.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new InswAuthException('Gagal menukar kode otorisasi INSW dengan access token (HTTP '.$response->status().').');
        }

        $payload = $response->json();
        $accessToken = $payload['access_token'] ?? $payload['accessToken'] ?? $payload['token'] ?? null;
        $expiresIn = $payload['expires_in'] ?? $payload['expiresIn'] ?? $payload['expire_in'] ?? 3600;

        if (! is_string($accessToken) || $accessToken === '') {
            Log::error('Respons token INSW tidak berisi access_token yang dikenali.', [
                'available_keys' => is_array($payload) ? array_keys($payload) : [],
                'body' => $response->body(),
            ]);

            throw new InswAuthException('Respons token INSW tidak dikenali. Cek log untuk struktur respons yang sebenarnya.');
        }

        $ttl = max(1, (int) $expiresIn - self::EXPIRY_BUFFER_SECONDS);
        Cache::put(self::CACHE_KEY, $accessToken, $ttl);

        return $accessToken;
    }

    /**
     * @param  array<string, string>  $fields
     * @return list<array{name: string, contents: string}>
     */
    private function multipartFields(array $fields): array
    {
        return collect($fields)
            ->map(fn ($value, $name) => ['name' => $name, 'contents' => $value])
            ->values()
            ->all();
    }

    private function envKey(string $configKey): string
    {
        return strtoupper($configKey);
    }
}
