<?php

declare(strict_types=1);

namespace App\Services\Assesment;

use App\Enums\AssesmentIsiStatus;
use App\Enums\AssesmentKelayakan;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Assesment\AssesmentJawaban;

/**
 * Menghitung skor satu AssesmentIsi. Spec (claude/fitur-assesment.md) tidak memberi rumus
 * eksplisit - formula yang dipakai di sini adalah rata-rata berbobot standar: setiap pertanyaan
 * menyumbang (bobot_pertanyaan_untuk_komoditas / 100) x skor_jawaban_untuk_komoditas ke total.
 *
 * Ada dua versi skor yang sengaja dipisah dan sama-sama disimpan (lihat AssesmentIsi::skor_entitas
 * vs skor_total) supaya jawaban asli entitas dan koreksi verifikator tetap bisa dibandingkan, bukan
 * satu menimpa yang lain:
 * - skor_entitas: murni dari jawaban_entitas_id, dihitung saat submit.
 * - skor_total: dari jawaban final (koreksi verifikator kalau ada, kalau tidak jawaban entitas),
 *   dihitung saat verifikasi selesai.
 */
class AssesmentSkorService
{
    /**
     * @param  bool  $gunakanKoreksiVerifikator  true = pakai jawaban_verifikator_id kalau ada
     *                                           (skor_total resmi), false = selalu pakai
     *                                           jawaban_entitas_id apa adanya (skor_entitas).
     */
    public function hitung(AssesmentIsi $isi, bool $gunakanKoreksiVerifikator = true): float
    {
        $isi->loadMissing(['jawaban.pertanyaan.komoditas', 'jawaban.jawabanEntitas.komoditas', 'jawaban.jawabanVerifikator.komoditas']);

        $total = 0.0;

        foreach ($isi->jawaban as $isiJawaban) {
            $bobot = $isiJawaban->pertanyaan->komoditas->firstWhere('id', $isi->komoditas_id)?->pivot->bobot;

            $jawabanDipakai = $gunakanKoreksiVerifikator
                ? ($isiJawaban->jawabanVerifikator ?? $isiJawaban->jawabanEntitas)
                : $isiJawaban->jawabanEntitas;

            $skor = $jawabanDipakai?->komoditas->firstWhere('id', $isi->komoditas_id)?->pivot->skor;

            if ($bobot === null || $skor === null) {
                continue;
            }

            $total += ((float) $bobot / 100) * (float) $skor;
        }

        return round($total, 2);
    }

    /**
     * Skor tertinggi yang mungkin dicapai: setiap pertanyaan untuk komoditas ini dijawab dengan
     * jawaban berskor tertinggi. Rumusnya sama dengan hitung(), jadi skor / skorMaksimal = 100%
     * kalau semua jawaban berskor tertinggi.
     */
    public function skorMaksimal(AssesmentIsi $isi): float
    {
        $isi->loadMissing(['assesment.pertanyaan.komoditas', 'assesment.pertanyaan.jawaban.komoditas']);

        $total = 0.0;

        foreach ($isi->assesment->pertanyaan as $pertanyaan) {
            $bobot = $pertanyaan->komoditas->firstWhere('id', $isi->komoditas_id)?->pivot->bobot;

            $skorTertinggi = $pertanyaan->jawaban
                ->map(fn (AssesmentJawaban $jawaban): ?float => $jawaban->skorUntukKomoditas($isi->komoditas_id))
                ->filter(fn (?float $skor): bool => $skor !== null)
                ->max();

            if ($bobot === null || $skorTertinggi === null) {
                continue;
            }

            $total += ((float) $bobot / 100) * $skorTertinggi;
        }

        return $total;
    }

    /**
     * Persentase skor entitas & hasil verifikasi terhadap skorMaksimal(), plus kategori kelayakan
     * yang hanya diisi setelah assesment Verified.
     *
     * @return array{entitas: ?float, verifikasi: ?float, kelayakan: ?AssesmentKelayakan}
     */
    public function hasil(AssesmentIsi $isi): array
    {
        $maksimal = $isi->skor_entitas !== null || $isi->skor_total !== null ? $this->skorMaksimal($isi) : 0.0;

        $persentase = fn (?string $skor): ?float => $skor !== null && $maksimal > 0
            ? round(min(100.0, (float) $skor / $maksimal * 100), 2)
            : null;

        $verifikasi = $persentase($isi->skor_total);

        return [
            'entitas' => $persentase($isi->skor_entitas),
            'verifikasi' => $verifikasi,
            'kelayakan' => $isi->status === AssesmentIsiStatus::Verified && $verifikasi !== null
                ? AssesmentKelayakan::fromPersentase($verifikasi)
                : null,
        ];
    }
}
