<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Kecamatan;
use App\Services\Insw\InswRegionSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fetches & upserts Kelurahan for a single Kecamatan. Dispatched once per existing Kecamatan row
 * by KelurahanController::sync() instead of looping all of them inline - this is the largest
 * level (thousands of Kecamatan), so it's the level that most needs to run off the queue.
 */
class SyncInswSubdistrictsForDistrictJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    public int $backoff = 30;

    public function __construct(public readonly Kecamatan $kecamatan) {}

    public function handle(InswRegionSyncService $syncService): void
    {
        $summary = $syncService->syncSubdistrictsForDistrict($this->kecamatan);

        $context = ['kecamatan' => $this->kecamatan->name, ...$summary];

        if (! empty($summary['errors'])) {
            Log::warning('Sinkron kelurahan selesai dengan error.', $context);

            return;
        }

        Log::info('Sinkron kelurahan selesai.', $context);
    }
}
