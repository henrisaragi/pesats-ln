<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\WhatsAppSetting;
use App\Services\Kapso\KapsoWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendWhatsAppNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.kapso.api_key' => 'test-api-key',
            'services.kapso.base_url' => 'https://api.kapso.ai',
            'services.kapso.phone_number_id' => '647015955153740',
        ]);
    }

    public function test_does_not_call_kapso_when_whatsapp_notifications_are_disabled(): void
    {
        WhatsAppSetting::setEnabled(false);
        Http::fake();

        (new SendWhatsAppNotificationJob('15551234567', 'Hello'))->handle(new KapsoWhatsAppService);

        Http::assertNothingSent();
    }

    public function test_calls_kapso_when_whatsapp_notifications_are_enabled(): void
    {
        WhatsAppSetting::setEnabled(true);
        Http::fake(['api.kapso.ai/*' => Http::response(['messages' => []], 200)]);

        (new SendWhatsAppNotificationJob('15551234567', 'Hello'))->handle(new KapsoWhatsAppService);

        Http::assertSentCount(1);
    }

    public function test_does_not_call_kapso_when_recipient_number_is_empty(): void
    {
        WhatsAppSetting::setEnabled(true);
        Http::fake();

        (new SendWhatsAppNotificationJob('', 'Hello'))->handle(new KapsoWhatsAppService);

        Http::assertNothingSent();
    }
}
