<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Kapso\KapsoWhatsAppService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KapsoWhatsAppServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.kapso.api_key' => 'test-api-key',
            'services.kapso.base_url' => 'https://api.kapso.ai',
            'services.kapso.phone_number_id' => '647015955153740',
        ]);
    }

    public function test_sends_a_text_message_with_the_correct_url_headers_and_body(): void
    {
        Http::fake([
            'api.kapso.ai/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200),
        ]);

        $service = new KapsoWhatsAppService;
        $result = $service->send('15551234567', 'Hello from PeSATS');

        $this->assertFalse($result['error']);
        $this->assertNull($result['message']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.kapso.ai/meta/whatsapp/v24.0/647015955153740/messages'
                && $request->header('X-API-Key') === ['test-api-key']
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '15551234567'
                && $request['type'] === 'text'
                && $request['text']['body'] === 'Hello from PeSATS';
        });
    }

    public function test_returns_an_error_when_kapso_responds_with_a_failure_status(): void
    {
        Http::fake([
            'api.kapso.ai/*' => Http::response(['error' => 'invalid number'], 400),
        ]);

        $result = (new KapsoWhatsAppService)->send('invalid', 'Hello');

        $this->assertTrue($result['error']);
        $this->assertNotNull($result['message']);
    }

    public function test_returns_an_error_when_the_connection_fails(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $result = (new KapsoWhatsAppService)->send('15551234567', 'Hello');

        $this->assertTrue($result['error']);
        $this->assertSame('Terjadi masalah koneksi dengan Kapso.', $result['message']);
    }

    public function test_returns_an_error_early_when_configuration_is_missing(): void
    {
        config(['services.kapso.api_key' => null]);

        Http::fake();

        $result = (new KapsoWhatsAppService)->send('15551234567', 'Hello');

        $this->assertTrue($result['error']);
        Http::assertNothingSent();
    }
}
