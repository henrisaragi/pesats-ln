<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Kota;
use App\Services\Insw\InswRegionSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fetches & upserts Kecamatan for a single Kota. Dispatched once per existing Kota row by
 * KecamatanController::sync() instead of looping all of them inline, since this level can mean
 * hundreds of outbound INSW calls - too slow/timeout-prone for a single web request.
 */
class SyncInswDistrictsForCityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    public int $backoff = 30;

    public function __construct(public readonly Kota $kota) {}

    public function handle(InswRegionSyncService $syncService): void
    {
        $summary = $syncService->syncDistrictsForCity($this->kota);

        $context = ['kota' => $this->kota->name, ...$summary];

        if (! empty($summary['errors'])) {
            Log::warning('Sinkron kecamatan selesai dengan error.', $context);

            return;
        }

        Log::info('Sinkron kecamatan selesai.', $context);
    }
}
