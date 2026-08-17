<?php

declare(strict_types=1);

namespace App\Services\Mail;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Reusable "send this Mailable to this address" wrapper - pure send concern, sama seperti
 * App\Services\Kapso\KapsoWhatsAppService untuk WhatsApp. Dipakai pertama kali untuk OTP login
 * (lihat App\Mail\OtpMail), tapi tidak tahu apa-apa soal OTP - siap dipakai ulang untuk email
 * lain nanti (reset password, notifikasi, dst) tanpa perlu bikin service baru lagi.
 */
class EmailService
{
    /**
     * @return array{error: bool, message: ?string}
     */
    public function send(string $to, Mailable $mailable): array
    {
        try {
            Mail::to($to)->send($mailable);

            return ['error' => false, 'message' => null];
        } catch (Throwable $e) {
            Log::error("Gagal mengirim email ke {$to}", ['exception' => $e->getMessage()]);

            return ['error' => true, 'message' => 'Gagal mengirim email.'];
        }
    }
}
