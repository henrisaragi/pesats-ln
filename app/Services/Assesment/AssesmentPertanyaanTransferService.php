<?php

declare(strict_types=1);

namespace App\Services\Assesment;

use App\Models\Assesment\Assesment;
use App\Models\Assesment\AssesmentAspek;
use App\Models\Assesment\AssesmentPertanyaan;
use App\Models\Komoditas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Export/import pertanyaan assesment dalam format JSON. Komoditas dirujuk lewat `code`
 * (unik & stabil antar environment), bukan id database.
 */
class AssesmentPertanyaanTransferService
{
    public const string FORMAT = 'pesats-assesment-pertanyaan';

    public const int VERSION = 1;

    /**
     * @return array{format: string, version: int, assesment: string, exported_at: string, pertanyaan: list<array<string, mixed>>}
     */
    public function export(Assesment $assesment): array
    {
        $assesment->load([
            'pertanyaan' => fn ($query) => $query->orderBy('urutan'),
            'pertanyaan.aspek',
            'pertanyaan.komoditas',
            'pertanyaan.jawaban' => fn ($query) => $query->orderBy('urutan'),
            'pertanyaan.jawaban.komoditas',
            'pertanyaan.buktiDukung' => fn ($query) => $query->orderBy('urutan'),
        ]);

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'assesment' => $assesment->judul,
            'exported_at' => now()->toIso8601String(),
            'pertanyaan' => $assesment->pertanyaan->map(fn (AssesmentPertanyaan $pertanyaan): array => [
                'aspek' => $pertanyaan->aspek->nama,
                'pertanyaan' => $pertanyaan->pertanyaan,
                'informasi' => $pertanyaan->informasi,
                'metode_verifikasi' => $pertanyaan->metode_verifikasi,
                'komoditas' => $pertanyaan->komoditas->map(fn (Komoditas $komoditas): array => [
                    'code' => $komoditas->code,
                    'name' => $komoditas->name,
                    'bobot' => (float) $komoditas->pivot->bobot,
                ])->values()->all(),
                'jawaban' => $pertanyaan->jawaban->map(fn ($jawaban): array => [
                    'teks' => $jawaban->teks_jawaban,
                    'skor' => $jawaban->komoditas
                        ->mapWithKeys(fn (Komoditas $komoditas): array => [$komoditas->code => (float) $komoditas->pivot->skor])
                        ->all(),
                ])->values()->all(),
                'bukti_dukung' => $pertanyaan->buktiDukung->pluck('label')->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * Pertanyaan hasil impor DITAMBAHKAN setelah pertanyaan yang sudah ada (tidak menimpa).
     * Data harus sudah tervalidasi (lihat AssesmentPertanyaanImportRequest).
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function import(Assesment $assesment, array $items): int
    {
        $komoditasIdByCode = Komoditas::query()->pluck('id', 'code')->all();

        return DB::transaction(function () use ($assesment, $items, $komoditasIdByCode): int {
            $urutan = $assesment->pertanyaan()->count();

            foreach ($items as $item) {
                $pertanyaan = AssesmentPertanyaan::create([
                    'assesment_id' => $assesment->id,
                    'aspek_id' => $this->resolveAspekId($item['aspek']),
                    'pertanyaan' => $item['pertanyaan'],
                    'informasi' => $item['informasi'] ?? null,
                    'metode_verifikasi' => $item['metode_verifikasi'] ?? null,
                    'urutan' => $urutan++,
                    'created_by' => Auth::id(),
                ]);

                $pertanyaan->komoditas()->sync(collect($item['komoditas'])->mapWithKeys(fn (array $row): array => [
                    $komoditasIdByCode[$row['code']] => ['bobot' => $row['bobot']],
                ])->all());

                foreach ($item['jawaban'] as $index => $row) {
                    $jawaban = $pertanyaan->jawaban()->create([
                        'teks_jawaban' => $row['teks'],
                        'urutan' => $index,
                    ]);

                    $jawaban->komoditas()->sync(collect($row['skor'])->mapWithKeys(fn (float|int|string $skor, int|string $code): array => [
                        $komoditasIdByCode[(string) $code] => ['skor' => $skor],
                    ])->all());
                }

                foreach ($item['bukti_dukung'] as $index => $label) {
                    $pertanyaan->buktiDukung()->create([
                        'label' => $label,
                        'urutan' => $index,
                    ]);
                }
            }

            return count($items);
        });
    }

    private function resolveAspekId(string $nama): int
    {
        $nama = trim($nama);

        $aspek = AssesmentAspek::query()
            ->whereRaw('lower(nama) = ?', [Str::lower($nama)])
            ->first();

        $aspek ??= AssesmentAspek::create([
            'nama' => $nama,
            'created_by' => Auth::id(),
        ]);

        return $aspek->id;
    }
}
