<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PermohonanStatusChanged;
use Illuminate\Support\Facades\Auth;

/**
 * Catat setiap perubahan status Permohonan sebagai milestone di activity_satsln - sama seperti
 * SendPermohonanStatusNotification, berlaku otomatis untuk semua transisi lewat changeStatus().
 */
class RecordPermohonanStatusActivity
{
    public function handle(PermohonanStatusChanged $event): void
    {
        $event->permohonan->activities()->create([
            'user_id' => Auth::id(),
            'messages' => __('Status permohonan berubah dari :from menjadi :to.', [
                'from' => $event->from->label(),
                'to' => $event->to->label(),
            ]),
        ]);
    }
}
