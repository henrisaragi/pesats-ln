<?php

declare(strict_types=1);

namespace App\Services\DokumenVerifikasi;

use Throwable;

class PdfSignatureChecker
{
    /**
     * Window pencarian /Type /Sig di sekitar tiap kemunculan /Contents - dictionary tanda tangan
     * memang bisa punya urutan key berbeda-beda (/Type sebelum atau sesudah /Contents), jadi
     * dicek di sekitarnya, bukan mengasumsikan urutan tetap.
     */
    private const DICTIONARY_WINDOW = 1000;

    /**
     * Deteksi struktural: apakah PDF punya signature dictionary (/Type /Sig) yang dirujuk lewat
     * /ByteRange, pola standar tanda tangan digital PDF (PAdES/PKCS7) - lihat
     * claude/otomatis-verifikasi.md poin "Tanda Tangan Digital". Sengaja tidak memverifikasi
     * validitas kriptografis sertifikatnya di sini - itu tugas extractIssuers().
     */
    public function hasDigitalSignature(string $absolutePath): bool
    {
        $raw = @file_get_contents($absolutePath);

        if ($raw === false) {
            return false;
        }

        return (bool) preg_match('/\/Type\s*\/Sig\b/', $raw) && str_contains($raw, '/ByteRange');
    }

    /**
     * DN Penerbit (issuer) tiap sertifikat X.509 yang disematkan di blob PKCS7/CMS pada
     * dictionary /Sig (field /Contents, hex-encoded DER) - biasanya berisi rantai [sertifikat
     * penandatangan, CA penerbit, ...], jadi dikembalikan semuanya (bukan cuma satu) supaya
     * pemanggil bisa cek "apakah ADA salah satu cert yang diterbitkan oleh CA X" tanpa perlu tahu
     * posisi sertifikat penandatangan di dalam bundle - lihat claude/otomatis-verifikasi.md
     * bagian Revisi Fitur. List kosong kalau tidak ada tanda tangan, /Contents tidak ditemukan,
     * atau PKCS7-nya gagal di-parse.
     *
     * @return list<array{cn: ?string, o: ?string, c: ?string}>
     */
    public function extractIssuers(string $absolutePath): array
    {
        $raw = @file_get_contents($absolutePath);

        if ($raw === false) {
            return [];
        }

        $der = $this->extractSignatureDer($raw);

        return $der !== null ? $this->issuersFromPkcs7Der($der) : [];
    }

    private function extractSignatureDer(string $raw): ?string
    {
        if (! preg_match_all('/\/Contents\s*<([0-9A-Fa-f\s]+)>/', $raw, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        foreach ($matches[1] as [$hexRaw, $offset]) {
            $windowStart = max(0, $offset - self::DICTIONARY_WINDOW);
            $windowEnd = $offset + strlen($hexRaw) + self::DICTIONARY_WINDOW;
            $window = substr($raw, $windowStart, $windowEnd - $windowStart);

            if (! preg_match('/\/Type\s*\/Sig\b/', $window)) {
                continue;
            }

            $hex = preg_replace('/\s+/', '', $hexRaw) ?? '';

            if ($hex === '') {
                continue;
            }

            if (strlen($hex) % 2 !== 0) {
                $hex = substr($hex, 0, -1);
            }

            $der = @hex2bin($hex);

            if ($der !== false) {
                return $der;
            }
        }

        return null;
    }

    /**
     * @return list<array{cn: ?string, o: ?string, c: ?string}>
     */
    private function issuersFromPkcs7Der(string $der): array
    {
        // openssl_pkcs7_read() cuma menerima PKCS7 dalam bentuk PEM-armored, bukan DER mentah
        // ataupun MIME/S-MIME - dibungkus manual di sini.
        $pem = "-----BEGIN PKCS7-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PKCS7-----\n";

        try {
            if (! openssl_pkcs7_read($pem, $certs) || empty($certs)) {
                return [];
            }
        } catch (Throwable) {
            return [];
        }

        $issuers = [];

        foreach ((array) $certs as $cert) {
            $parsed = @openssl_x509_parse($cert);

            if ($parsed !== false && isset($parsed['issuer']) && is_array($parsed['issuer'])) {
                $issuers[] = [
                    'cn' => $parsed['issuer']['CN'] ?? null,
                    'o' => $parsed['issuer']['O'] ?? null,
                    'c' => $parsed['issuer']['C'] ?? null,
                ];
            }
        }

        return $issuers;
    }
}
