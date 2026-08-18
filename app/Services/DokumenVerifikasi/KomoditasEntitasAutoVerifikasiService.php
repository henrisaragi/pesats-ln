<?php

declare(strict_types=1);

namespace App\Services\DokumenVerifikasi;

use App\Enums\DokumenPemeriksaanStatus;
use App\Enums\KomoditasEntitasStatus;
use App\Models\KomoditasEntitas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Menjalankan pemeriksaan otomatis dokumen sertifikat standar yang diunggah pada Komoditas
 * Entitas - lihat claude/otomatis-verifikasi.md. Semua pemeriksaan lolos -> status langsung
 * Verified (verified_by dibiarkan null, penanda diverifikasi sistem, lihat
 * KomoditasEntitas::isVerifiedOtomatis()). Ada yang gagal/dilewati -> status dibiarkan Pending,
 * summary_cek dipakai user berpermission komoditas-entitas.verify sebagai petunjuk verifikasi
 * manual.
 */
class KomoditasEntitasAutoVerifikasiService
{
    private const RUANG_LINGKUP_EXPECTED = 'PEREDARAN JENIS TUMBUHAN DAN SATWA LIAR LUAR NEGERI';

    private const BULAN_INDONESIA = [
        'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4, 'MEI' => 5, 'JUNI' => 6,
        'JULI' => 7, 'AGUSTUS' => 8, 'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
    ];

    public function __construct(
        private readonly PdfSignatureChecker $signatureChecker,
        private readonly PdfTextExtractor $textExtractor,
    ) {}

    public function verifikasi(KomoditasEntitas $komoditasEntitas): void
    {
        $absolutePath = Storage::disk('local')->path($komoditasEntitas->file_path);

        if (! is_file($absolutePath)) {
            $this->simpanHasil($komoditasEntitas, [$this->buildCheck(
                'dokumen',
                __('Dokumen'),
                DokumenPemeriksaanStatus::Gagal,
                __('Berkas dokumen tidak ditemukan di penyimpanan.'),
            )]);

            return;
        }

        $checks = [];

        $hasSignature = $this->signatureChecker->hasDigitalSignature($absolutePath);
        $checks[] = $this->buildCheck(
            'tanda_tangan_digital',
            __('Tanda Tangan Digital'),
            $hasSignature ? DokumenPemeriksaanStatus::Lolos : DokumenPemeriksaanStatus::Gagal,
            $hasSignature
                ? __('Dokumen memiliki tanda tangan digital.')
                : __('Dokumen tidak memiliki tanda tangan digital.'),
        );

        $text = $hasSignature ? $this->normalize($this->extractText($absolutePath)) : '';

        $signedByOss = $hasSignature && $this->containsAny($text, $this->ossSignerKeywords());
        $checks[] = $this->buildCheck(
            'penandatangan_oss',
            __('Penandatangan oleh Lembaga OSS'),
            match (true) {
                ! $hasSignature => DokumenPemeriksaanStatus::Dilewati,
                $signedByOss => DokumenPemeriksaanStatus::Lolos,
                default => DokumenPemeriksaanStatus::Gagal,
            },
            match (true) {
                ! $hasSignature => __('Dilewati karena dokumen tidak memiliki tanda tangan digital.'),
                $signedByOss => __('Dokumen ditandatangani oleh Lembaga OSS.'),
                default => __('Tidak ditemukan penanda tangan Lembaga OSS pada dokumen.'),
            },
        );

        $canCheckIsi = $signedByOss;

        $checks[] = $this->checkNamaPelakuUsaha($text, $komoditasEntitas, $canCheckIsi);
        $checks[] = $this->checkNomorSertifikat($text, $komoditasEntitas, $canCheckIsi);
        $checks[] = $this->checkJenisDiedarkan($text, $komoditasEntitas, $canCheckIsi);
        $checks[] = $this->checkRuangLingkup($text, $canCheckIsi);
        $checks[] = $this->checkTanggalTerbit($text, $komoditasEntitas, $canCheckIsi);

        $this->simpanHasil($komoditasEntitas, $checks);
    }

    private function extractText(string $absolutePath): string
    {
        try {
            return $this->textExtractor->extract($absolutePath);
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * @param  list<array{key: string, label: string, status: string, detail: string}>  $checks
     */
    private function simpanHasil(KomoditasEntitas $komoditasEntitas, array $checks): void
    {
        $allPassed = collect($checks)->isNotEmpty()
            && collect($checks)->every(fn (array $check) => $check['status'] === DokumenPemeriksaanStatus::Lolos->value);

        $komoditasEntitas->update([
            'summary_cek' => [
                'valid' => $allPassed,
                'checked_at' => now()->toIso8601String(),
                'checks' => $checks,
            ],
            ...($allPassed ? [
                'status' => KomoditasEntitasStatus::Verified,
                'verified_by' => null,
                'verified_at' => now(),
            ] : []),
        ]);
    }

    private function checkNamaPelakuUsaha(string $text, KomoditasEntitas $komoditasEntitas, bool $canCheck): array
    {
        if (! $canCheck) {
            return $this->skipped('nama_pelaku_usaha', __('Nama Pelaku Usaha'));
        }

        $namaEntitas = $komoditasEntitas->entitas?->name ?? '';
        $matched = $namaEntitas !== '' && str_contains($text, $this->normalize($namaEntitas));

        return $this->buildCheck(
            'nama_pelaku_usaha',
            __('Nama Pelaku Usaha'),
            $matched ? DokumenPemeriksaanStatus::Lolos : DokumenPemeriksaanStatus::Gagal,
            $matched
                ? __('Nama pelaku usaha ":nama" ditemukan pada dokumen.', ['nama' => $namaEntitas])
                : __('Nama pelaku usaha ":nama" tidak ditemukan pada dokumen.', ['nama' => $namaEntitas]),
        );
    }

    private function checkNomorSertifikat(string $text, KomoditasEntitas $komoditasEntitas, bool $canCheck): array
    {
        if (! $canCheck) {
            return $this->skipped('nomor_sertifikat', __('Nomor Sertifikat Standar'));
        }

        $nomor = (string) $komoditasEntitas->nomor_sertifikat;
        $normalizedNomor = preg_replace('/\s+/', '', $this->normalize($nomor)) ?? '';
        $normalizedText = preg_replace('/\s+/', '', $text) ?? '';
        $matched = $normalizedNomor !== '' && str_contains($normalizedText, $normalizedNomor);

        return $this->buildCheck(
            'nomor_sertifikat',
            __('Nomor Sertifikat Standar'),
            $matched ? DokumenPemeriksaanStatus::Lolos : DokumenPemeriksaanStatus::Gagal,
            $matched
                ? __('Nomor sertifikat ":nomor" ditemukan pada dokumen.', ['nomor' => $nomor])
                : __('Nomor sertifikat ":nomor" tidak ditemukan pada dokumen.', ['nomor' => $nomor]),
        );
    }

    private function checkJenisDiedarkan(string $text, KomoditasEntitas $komoditasEntitas, bool $canCheck): array
    {
        if (! $canCheck) {
            return $this->skipped('jenis_diedarkan', __('Jenis yang Diedarkan'));
        }

        $namaKomoditas = $komoditasEntitas->komoditas?->name ?? '';
        $matched = $namaKomoditas !== '' && $this->containsNear($text, 'JENIS', $this->normalize($namaKomoditas));

        return $this->buildCheck(
            'jenis_diedarkan',
            __('Jenis yang Diedarkan'),
            $matched ? DokumenPemeriksaanStatus::Lolos : DokumenPemeriksaanStatus::Gagal,
            $matched
                ? __('Jenis ":jenis" ditemukan pada dokumen.', ['jenis' => $namaKomoditas])
                : __('Jenis ":jenis" tidak ditemukan pada dokumen.', ['jenis' => $namaKomoditas]),
        );
    }

    private function checkRuangLingkup(string $text, bool $canCheck): array
    {
        if (! $canCheck) {
            return $this->skipped('ruang_lingkup', __('Ruang Lingkup'));
        }

        $matched = str_contains($text, self::RUANG_LINGKUP_EXPECTED);

        return $this->buildCheck(
            'ruang_lingkup',
            __('Ruang Lingkup'),
            $matched ? DokumenPemeriksaanStatus::Lolos : DokumenPemeriksaanStatus::Gagal,
            $matched
                ? __('Ruang lingkup "Peredaran jenis tumbuhan dan satwa liar luar negeri" ditemukan pada dokumen.')
                : __('Ruang lingkup "Peredaran jenis tumbuhan dan satwa liar luar negeri" tidak ditemukan pada dokumen.'),
        );
    }

    private function checkTanggalTerbit(string $text, KomoditasEntitas $komoditasEntitas, bool $canCheck): array
    {
        if (! $canCheck) {
            return $this->skipped('tanggal_terbit', __('Tanggal Terbit'));
        }

        $found = $this->extractTanggalTerbit($text);
        $expected = $komoditasEntitas->tanggal_terbit;
        $matched = $found !== null && $expected !== null && $found->isSameDay($expected);

        $detail = match (true) {
            $matched => __('Tanggal terbit pada dokumen (:ditemukan) sesuai dengan input (:diinput).', [
                'ditemukan' => $found->format('d/m/Y'),
                'diinput' => $expected?->format('d/m/Y'),
            ]),
            $found !== null => __('Tanggal terbit pada dokumen (:ditemukan) tidak sesuai dengan input (:diinput).', [
                'ditemukan' => $found->format('d/m/Y'),
                'diinput' => $expected?->format('d/m/Y'),
            ]),
            default => __('Tanggal terbit tidak ditemukan pada dokumen.'),
        };

        return $this->buildCheck(
            'tanggal_terbit',
            __('Tanggal Terbit'),
            $matched ? DokumenPemeriksaanStatus::Lolos : DokumenPemeriksaanStatus::Gagal,
            $detail,
        );
    }

    private function extractTanggalTerbit(string $text): ?Carbon
    {
        if (! preg_match('/DITERBITKAN\s*TANGGAL\s*[:.]?\s*([^\n.]{6,40})/', $text, $matches)) {
            return null;
        }

        $raw = trim($matches[1]);

        if (preg_match('/(\d{1,2})\s+([A-Z]+)\s+(\d{4})/', $raw, $m) && isset(self::BULAN_INDONESIA[$m[2]])) {
            return Carbon::create((int) $m[3], self::BULAN_INDONESIA[$m[2]], (int) $m[1]);
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $raw);
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Cari $needle dalam window teks setelah tiap kemunculan $label (mis. "JENIS ... MAMALIA
     * RUSA TIMOR (CERVUS TIMORENSIS)") - lihat claude/otomatis-verifikasi.md poin Jenis
     * Diedarkan: "cukup ambil teks Mamalia untuk mencocokkan".
     */
    private function containsNear(string $text, string $label, string $needle, int $window = 200): bool
    {
        $offset = 0;

        while (($pos = mb_strpos($text, $label, $offset)) !== false) {
            if (str_contains(mb_substr($text, $pos, $window), $needle)) {
                return true;
            }

            $offset = $pos + mb_strlen($label);
        }

        return false;
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function ossSignerKeywords(): array
    {
        return array_map(
            fn (string $keyword) => $this->normalize($keyword),
            (array) config('services.document_verification.oss_signer_keywords', []),
        );
    }

    /**
     * Uppercase, buang titik, rapikan spasi - biar "PT SYLVA RESTU ABADI" cocok dengan "PT.
     * SYLVA RESTU ABADI" (lihat claude/otomatis-verifikasi.md poin Nama Pelaku Usaha).
     */
    private function normalize(string $value): string
    {
        $value = str_replace('.', '', $value);
        $value = mb_strtoupper($value);

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function skipped(string $key, string $label): array
    {
        return $this->buildCheck(
            $key,
            $label,
            DokumenPemeriksaanStatus::Dilewati,
            __('Dilewati karena pemeriksaan sebelumnya tidak lolos.'),
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string}
     */
    private function buildCheck(string $key, string $label, DokumenPemeriksaanStatus $status, string $detail): array
    {
        return ['key' => $key, 'label' => $label, 'status' => $status->value, 'detail' => $detail];
    }
}
