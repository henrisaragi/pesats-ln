<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\PermohonanStatus;
use App\Models\Permohonan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dipicu setiap kali PermohonanService::changeStatus() benar-benar mengubah status Permohonan -
 * satu-satunya sumber untuk notifikasi status (lihat App\Listeners\SendPermohonanStatusNotification),
 * supaya SETIAP perubahan status (manual maupun otomatis lewat PermohonanService::recomputeStatus())
 * selalu terkirim notifikasinya tanpa perlu dipanggil manual di tiap controller.
 */
class PermohonanStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Permohonan $permohonan,
        public readonly PermohonanStatus $from,
        public readonly PermohonanStatus $to,
    ) {}
}
