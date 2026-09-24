<?php

declare(strict_types=1);

namespace App\Services\Assesment;

use App\Models\Assesment\AssesmentIsi;

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
}
