<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Mail\OtpMail;
use App\Services\Mail\EmailService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    public function test_sends_the_given_mailable_to_the_given_address(): void
    {
        Mail::fake();

        $result = (new EmailService)->send('someone@example.test', new OtpMail('123456', 10));

        $this->assertFalse($result['error']);
        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo('someone@example.test'));
    }
}
