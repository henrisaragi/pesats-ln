<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Master\CountrySeedService;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Seed the reference country (ISO2, ISO3, kode telepon) data.
     */
    public function run(): void
    {
        app(CountrySeedService::class)->seed();
    }
}
