<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\NotificationCategory;
use App\Enums\PermohonanStatus;
use App\Events\PermohonanStatusChanged;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Satu-satunya tempat yang mengirim notifikasi status Permohonan (kategori Permohonan) - dipicu
 * PermohonanStatusChanged dari PermohonanService::changeStatus(), jadi berlaku otomatis untuk
 * SEMUA transisi status (termasuk yang otomatis lewat recomputeStatus() saat SATS-LN
 * dicetak/ditandatangani), tidak perlu dipanggil manual satu-satu di tiap controller.
 */
class SendPermohonanStatusNotification
{
    public function handle(PermohonanStatusChanged $event): void
    {
        $permohonan = $event->permohonan;
        $recipients = $this->recipients($event);

        if ($recipients->isEmpty()) {
            return;
        }

        [$title, $message] = $this->content($event);

        $url = in_array($event->to, [PermohonanStatus::AntrianVerifikasi, PermohonanStatus::AntrianCetak], true)
            ? route('permohonan-export.verifikasi.show', $permohonan)
            : route('permohonan-export.show', $permohonan);

        Notification::send($recipients, new AppNotification(NotificationCategory::Permohonan, $title, $message, $url));

        // Contoh pemakaian KapsoWhatsAppService (lewat SendWhatsAppNotificationJob, yang
        // menangani cek on/off App\Models\WhatsAppSetting) - cukup dispatch, tidak perlu tahu
        // soal toggle atau konfigurasi Kapso di sini.
        foreach ($recipients->whereNotNull('phone_number') as $recipient) {
            SendWhatsAppNotificationJob::dispatch($recipient->phone_number, $message);
        }
    }

    /**
     * Status yang butuh aksi staff diberitahukan ke staff terkait (AntrianVerifikasi ke verifier,
     * AntrianCetak ke Divisi Cetak = pemegang permohonan-export.print); status lain
     * (termasuk Submitted dan progres cetak/tanda tangan) diberitahukan ke user milik entitas
     * pengaju - sama seperti pembagian notifyVerifiers()/notifySubmitter() sebelumnya.
     *
     * @return Collection<int, User>
     */
    private function recipients(PermohonanStatusChanged $event): Collection
    {
        if ($event->to === PermohonanStatus::AntrianVerifikasi) {
            return User::permission('permohonan-export.verify')->get();
        }

        if ($event->to === PermohonanStatus::AntrianCetak) {
            return User::permission('permohonan-export.print')->get();
        }

        $event->permohonan->loadMissing('entitas.users');

        return $event->permohonan->entitas?->users ?? collect();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function content(PermohonanStatusChanged $event): array
    {
        $permohonan = $event->permohonan;
        $nomor = $permohonan->nomor_permohonan;

        return match ($event->to) {
            PermohonanStatus::Draft => [
                __('Permohonan Dikembalikan ke Draft'),
                __('Permohonan :nomor dikembalikan ke status draft.', ['nomor' => $nomor]),
            ],
            PermohonanStatus::Submitted => [
                __('Permohonan Diajukan'),
                __('Permohonan :nomor berhasil diajukan.', ['nomor' => $nomor]),
            ],
            PermohonanStatus::AntrianVerifikasi => [
                __('Permohonan SATS-LN Ekspor Baru'),
                __(':entitas mengajukan permohonan :nomor untuk diverifikasi.', [
                    'entitas' => $permohonan->entitas?->name,
                    'nomor' => $nomor,
                ]),
            ],
            PermohonanStatus::DalamProsesVerifikasi => [
                __('Permohonan Diverifikasi'),
                __('Permohonan :nomor telah diverifikasi dan mulai diproses.', ['nomor' => $nomor]),
            ],
            PermohonanStatus::Ditolak => [
                __('Permohonan Ditolak'),
                __('Permohonan :nomor ditolak.', ['nomor' => $nomor]),
            ],
            PermohonanStatus::AntrianCetak => [
                __('Permohonan Siap Dicetak'),
                __('Permohonan :nomor telah dikirim ke Divisi Cetak.', ['nomor' => $nomor]),
            ],
            PermohonanStatus::DicetakSebagian => [
                __('SATS-LN Dicetak'),
                __('Sebagian SATS-LN pada permohonan :nomor telah dicetak.', ['nomor' => $nomor]),
            ],
            PermohonanStatus::DitandatanganiSebagian => [
                __('SATS-LN Ditandatangani'),
                __('Sebagian SATS-LN pada permohonan :nomor telah ditandatangani.', ['nomor' => $nomor]),
            ],
            PermohonanStatus::SiapDiambil => [
                __('Permohonan Siap Diambil'),
                __('Permohonan :nomor siap untuk diambil.', ['nomor' => $nomor]),
            ],
        };
    }
}
