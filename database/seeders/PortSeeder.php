<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Master\PortSeedService;
use Illuminate\Database\Seeder;

class PortSeeder extends Seeder
{
    /**
     * Seed the reference port data. Requires countries to already be seeded
     * (each port is matched to its country by ISO2 code).
     */
    public function run(): void
    {
        app(PortSeedService::class)->seed();
    }
}
