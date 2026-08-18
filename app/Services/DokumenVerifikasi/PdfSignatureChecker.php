<?php

declare(strict_types=1);

namespace App\Services\DokumenVerifikasi;

class PdfSignatureChecker
{
    /**
     * Deteksi struktural: apakah PDF punya signature dictionary (/Type /Sig) yang dirujuk lewat
     * /ByteRange, pola standar tanda tangan digital PDF (PAdES/PKCS7) - lihat
     * claude/otomatis-verifikasi.md poin "Tanda Tangan Digital". Sengaja tidak memverifikasi
     * validitas kriptografis sertifikatnya (siapa penandatangan dicek lewat teks dokumen di
     * KomoditasEntitasAutoVerifikasiService, bukan lewat parsing sertifikat X.509).
     */
    public function hasDigitalSignature(string $absolutePath): bool
    {
        $raw = @file_get_contents($absolutePath);

        if ($raw === false) {
            return false;
        }

        return (bool) preg_match('/\/Type\s*\/Sig\b/', $raw) && str_contains($raw, '/ByteRange');
    }
}
