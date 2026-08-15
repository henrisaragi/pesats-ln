<?php

declare(strict_types=1);

namespace App\Services\Insw;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Maps INSW ref/daerah responses onto our provinsi/kota/kecamatan/kelurahan tables.
 *
 * Confirmed against a real response for Provinsi: fields are PascalCase and entity-specific,
 * e.g. `KodeProvinsi`/`NamaProvinsi` (not the generic `kode`/`nama` originally guessed). Kota/
 * Kecamatan/Kelurahan are assumed to follow the same `Kode{Entity}`/`Nama{Entity}` pattern
 * until confirmed - extractCode()/extractName() also fall back to the lowercase/English guesses
 * as a defensive second layer. A sync that comes back with 0 created/updated plus a logged
 * "bentuk data tidak dikenali" or "field kode/nama tidak ditemukan" warning means the pattern
 * needs correcting for that specific level.
 *
 * INSW returns names in ALL CAPS (e.g. "ACEH") - normalized to title case ("Aceh") on the way in.
 */
class InswRegionSyncService
{
    public function __construct(private readonly InswClient $client) {}

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function syncProvinces(): array
    {
        $summary = $this->emptySummary();
        $response = $this->client->get('all-provinsi');

        if ($response['error']) {
            $summary['errors'][] = $response['message'] ?? 'Gagal mengambil data provinsi dari INSW.';

            return $summary;
        }

        foreach ($this->extractItems($response['data']) as $item) {
            $this->upsertItem($item, Provinsi::class, [], ['Provinsi'], $summary);
        }

        return $summary;
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function syncCities(): array
    {
        $summary = $this->emptySummary();
        $provinces = Provinsi::all();

        if ($provinces->isEmpty()) {
            $summary['errors'][] = 'Belum ada data Provinsi. Sinkronkan Provinsi terlebih dahulu.';

            return $summary;
        }

        foreach ($provinces as $provinsi) {
            $response = $this->client->get('kota', ['keyword' => $provinsi->code]);

            if ($response['error']) {
                $summary['errors'][] = "Provinsi {$provinsi->name}: ".($response['message'] ?? 'gagal diambil.');

                continue;
            }

            foreach ($this->extractItems($response['data']) as $item) {
                $this->upsertItem($item, Kota::class, ['provinsi_id' => $provinsi->id], ['Kota', 'KabKota'], $summary);
            }
        }

        return $summary;
    }

    /**
     * Synchronous full sync - loops every existing Kota. Kept for CLI/tinker convenience;
     * the "Sinkron dari INSW" button for Kecamatan dispatches one SyncInswDistrictsForCityJob
     * per Kota instead (queued, see app/Jobs/), since this level involves hundreds of calls.
     *
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function syncDistricts(): array
    {
        $cities = Kota::all();

        if ($cities->isEmpty()) {
            $summary = $this->emptySummary();
            $summary['errors'][] = 'Belum ada data Kota. Sinkronkan Kota terlebih dahulu.';

            return $summary;
        }

        $summary = $this->emptySummary();

        foreach ($cities as $kota) {
            $this->mergeSummary($summary, $this->syncDistrictsForCity($kota), "Kota {$kota->name}");
        }

        return $summary;
    }

    /**
     * Fetches & upserts Kecamatan for a single Kota. Used both by syncDistricts() (full loop)
     * and by SyncInswDistrictsForCityJob (one job per Kota, queued).
     *
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function syncDistrictsForCity(Kota $kota): array
    {
        $summary = $this->emptySummary();
        $response = $this->client->get('kecamatan', ['keyword' => $kota->code]);

        if ($response['error']) {
            $summary['errors'][] = $response['message'] ?? 'Gagal mengambil data kecamatan dari INSW.';

            return $summary;
        }

        foreach ($this->extractItems($response['data']) as $item) {
            $this->upsertItem($item, Kecamatan::class, ['kota_id' => $kota->id], ['Kecamatan'], $summary);
        }

        return $summary;
    }

    /**
     * Synchronous full sync - loops every existing Kecamatan. Kept for CLI/tinker convenience;
     * the "Sinkron dari INSW" button for Kelurahan dispatches one SyncInswSubdistrictsForDistrictJob
     * per Kecamatan instead (queued, see app/Jobs/), since this level involves many calls.
     *
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function syncSubdistricts(): array
    {
        $districts = Kecamatan::all();

        if ($districts->isEmpty()) {
            $summary = $this->emptySummary();
            $summary['errors'][] = 'Belum ada data Kecamatan. Sinkronkan Kecamatan terlebih dahulu.';

            return $summary;
        }

        $summary = $this->emptySummary();

        foreach ($districts as $kecamatan) {
            $this->mergeSummary($summary, $this->syncSubdistrictsForDistrict($kecamatan), "Kecamatan {$kecamatan->name}");
        }

        return $summary;
    }

    /**
     * Fetches & upserts Kelurahan for a single Kecamatan. Used both by syncSubdistricts() (full
     * loop) and by SyncInswSubdistrictsForDistrictJob (one job per Kecamatan, queued).
     *
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function syncSubdistrictsForDistrict(Kecamatan $kecamatan): array
    {
        $summary = $this->emptySummary();
        $response = $this->client->get('kelurahan', ['keyword' => $kecamatan->code]);

        if ($response['error']) {
            $summary['errors'][] = $response['message'] ?? 'Gagal mengambil data kelurahan dari INSW.';

            return $summary;
        }

        foreach ($this->extractItems($response['data']) as $item) {
            $this->upsertItem($item, Kelurahan::class, ['kecamatan_id' => $kecamatan->id], ['Kelurahan', 'Desa'], $summary);
        }

        return $summary;
    }

    /**
     * @param  array{created: int, updated: int, skipped: int, errors: list<string>}  $into
     * @param  array{created: int, updated: int, skipped: int, errors: list<string>}  $from
     */
    private function mergeSummary(array &$into, array $from, string $label): void
    {
        $into['created'] += $from['created'];
        $into['updated'] += $from['updated'];
        $into['skipped'] += $from['skipped'];

        if (! empty($from['errors'])) {
            $into['errors'][] = "{$label}: ".implode('; ', $from['errors']);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  class-string<Provinsi|Kota|Kecamatan|Kelurahan>  $modelClass
     * @param  array<string, mixed>  $extraAttributes
     * @param  list<string>  $entitySuffixes  PascalCase suffixes to try for Kode{Suffix}/Nama{Suffix}, e.g. ['Provinsi']
     * @param  array{created: int, updated: int, skipped: int, errors: list<string>}  $summary
     */
    private function upsertItem(array $item, string $modelClass, array $extraAttributes, array $entitySuffixes, array &$summary): void
    {
        $code = $this->extractCode($item, $entitySuffixes);
        $name = $this->extractName($item, $entitySuffixes);

        if ($code === null || $name === null) {
            $summary['skipped']++;
            Log::warning('Item INSW dilewati karena field kode/nama tidak ditemukan.', [
                'model' => $modelClass,
                'available_keys' => array_keys($item),
            ]);

            return;
        }

        try {
            $model = $modelClass::updateOrCreate(
                ['code' => $code],
                [...$extraAttributes, 'name' => $name]
            );

            $model->wasRecentlyCreated ? $summary['created']++ : $summary['updated']++;
        } catch (Throwable $e) {
            $summary['skipped']++;
            $summary['errors'][] = "Kode {$code}: {$e->getMessage()}";
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $entitySuffixes
     */
    private function extractCode(array $item, array $entitySuffixes): ?string
    {
        foreach ($entitySuffixes as $suffix) {
            $value = $item["Kode{$suffix}"] ?? null;

            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        $value = $item['kode'] ?? $item['code'] ?? null;

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $entitySuffixes
     */
    private function extractName(array $item, array $entitySuffixes): ?string
    {
        $value = null;

        foreach ($entitySuffixes as $suffix) {
            $candidate = $item["Nama{$suffix}"] ?? null;

            if (is_scalar($candidate) && (string) $candidate !== '') {
                $value = $candidate;

                break;
            }
        }

        $value ??= $item['nama'] ?? $item['name'] ?? null;

        if (! is_scalar($value) || (string) $value === '') {
            return null;
        }

        // INSW returns names in ALL CAPS with occasional irregular spacing: repeated whitespace
        // (e.g. "SUMATERA  UTARA") and, in at least one real case (Papua, code 91), every letter
        // individually spaced out ("P A P U A"). Join runs of single-character tokens back into
        // words first, then collapse remaining repeated whitespace, before title-casing.
        $joined = preg_replace_callback(
            '/(?:\b\w\b\s*){2,}/u',
            fn (array $m) => str_replace(' ', '', trim($m[0])),
            (string) $value
        );
        $normalized = preg_replace('/\s+/', ' ', trim((string) $joined));

        return Str::title(strtolower($normalized));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractItems(mixed $data): array
    {
        if (is_array($data) && array_is_list($data)) {
            return $data;
        }

        if (is_array($data)) {
            foreach (['items', 'list', 'rows'] as $key) {
                if (isset($data[$key]) && is_array($data[$key]) && array_is_list($data[$key])) {
                    return $data[$key];
                }
            }

            Log::warning('Bentuk data INSW tidak dikenali (bukan list, tidak ada key items/list/rows).', [
                'available_keys' => array_keys($data),
            ]);
        }

        return [];
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    private function emptySummary(): array
    {
        return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
    }
}
