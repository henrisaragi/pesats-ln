<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\KomoditasEntitasStatus;
use App\Models\KomoditasEntitas;
use App\Services\KomoditasEntitasService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Baris Komoditas Entitas yang masih Verified tapi tanggal_berakhir-nya sudah lewat (atau pas
 * hari ini) dipindah statusnya jadi Expired - lihat claude/status-cek-task.md. Entitas yang
 * rolenya dikecualikan dari aturan masa berlaku (tidak punya permission
 * komoditas-entitas.masa-berlaku) sengaja dilewati, komoditasnya tidak pernah di-expired-kan oleh
 * command ini berapa pun lama tanggal_berakhir-nya sudah lewat.
 */
#[Signature('komoditas-entitas:mark-expired')]
#[Description('Mark Verified Komoditas Entitas past their tanggal_berakhir as Expired')]
class MarkExpiredKomoditasEntitasCommand extends Command
{
    public function handle(KomoditasEntitasService $komoditasEntitasService): int
    {
        $count = KomoditasEntitas::query()
            ->whereIn('entitas_id', $komoditasEntitasService->subjectToMasaBerlakuEntitasIds())
            ->where('status', KomoditasEntitasStatus::Verified)
            ->whereDate('tanggal_berakhir', '<=', Carbon::today())
            ->update(['status' => KomoditasEntitasStatus::Expired]);

        Log::info('Komoditas Entitas ditandai Expired oleh job harian.', ['jumlah' => $count]);
        $this->info("Menandai {$count} Komoditas Entitas sebagai Expired.");

        return self::SUCCESS;
    }
}
