<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsAppSetting;
use App\Services\Kapso\KapsoWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Single dispatch point controllers use to send a WhatsApp notification - wraps
 * KapsoWhatsAppService with the app-wide on/off toggle (App\Models\WhatsAppSetting) so callers
 * never need to check the toggle themselves, just dispatch this job.
 *
 * Usage: SendWhatsAppNotificationJob::dispatch($user->phone_number, $message);
 */
class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $to,
        public readonly string $message,
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(KapsoWhatsAppService $service): void
    {
        if ($this->to === '' || ! WhatsAppSetting::isEnabled()) {
            return;
        }

        $result = $service->send($this->to, $this->message);

        if ($result['error']) {
            Log::error('Gagal kirim notifikasi WhatsApp.', ['to' => $this->to, 'message' => $result['message']]);
        }
    }
}
