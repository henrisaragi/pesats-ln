<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Models\Country;
use App\Models\Port;

class PortSeedService
{
    private const CHUNK_SIZE = 1000;

    /**
     * Parse database/data/port.sql (INSERT `port` (id_data, id, name, id_country) VALUES ...)
     * and upsert into `ports`, keyed by `code` (unique). Rows whose id_country isn't a
     * recognized ISO2 code, or doesn't match a seeded Country, are skipped and counted
     * rather than failing the whole batch.
     *
     * @return array{upserted: int, skipped: int}
     */
    public function seed(): array
    {
        $countryIds = Country::query()->pluck('id', 'iso_code');

        $rows = [];
        $skipped = 0;

        foreach ($this->parseFile() as $port) {
            $countryId = $countryIds[$port['iso2']] ?? null;

            if ($countryId === null) {
                $skipped++;

                continue;
            }

            // Keyed by code so a duplicate code later in the file overwrites the earlier
            // one instead of appearing twice in the same upsert chunk (which PostgreSQL
            // rejects: "ON CONFLICT DO UPDATE command cannot affect row a second time").
            $rows[$port['code']] = [
                'code' => $port['code'],
                'name' => $port['name'],
                'country_id' => $countryId,
                'type' => 'sea',
            ];
        }

        $upserted = 0;

        foreach (array_chunk(array_values($rows), self::CHUNK_SIZE) as $chunk) {
            Port::query()->upsert($chunk, ['code'], ['name', 'country_id', 'type']);
            $upserted += count($chunk);
        }

        return ['upserted' => $upserted, 'skipped' => $skipped];
    }

    /**
     * @return list<array{code: string, name: string, iso2: string}>
     */
    private function parseFile(): array
    {
        $sql = file_get_contents(database_path('data/port.sql'));

        preg_match_all(
            "/\(\d+,\s*'((?:[^'\\\\]|\\\\.)*)',\s*'((?:[^'\\\\]|\\\\.)*)',\s*'([A-Za-z]{2})'\)/",
            $sql,
            $matches,
            PREG_SET_ORDER
        );

        $rows = [];

        foreach ($matches as $match) {
            $code = trim(str_replace("\\'", "'", $match[1]));
            $name = trim(str_replace("\\'", "'", $match[2]));
            $iso2 = strtoupper($match[3]);

            if ($code === '' || $name === '') {
                continue;
            }

            $rows[] = ['code' => $code, 'name' => $name, 'iso2' => $iso2];
        }

        return $rows;
    }
}
