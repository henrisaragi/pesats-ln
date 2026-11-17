<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SubdirectoryDeploymentTest extends TestCase
{
    /**
     * @var array<string, string>
     */
    private const SUBDIRECTORY_SERVER = [
        'SCRIPT_NAME' => '/satsln-v4/index.php',
        'SCRIPT_FILENAME' => __DIR__.'/../../public/index.php',
        'REMOTE_ADDR' => '172.16.3.40',
        'HTTP_X_FORWARDED_HOST' => 'pesats.example.test',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_PREFIX' => '/satsdn-v2',
    ];

    public function test_login_page_links_use_public_host_and_subdirectory_from_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => '172.16.3.40', 'app.asset_url' => null]);

        $response = $this->call('GET', 'http://172.16.3.40:9022/satsln-v4/login', server: self::SUBDIRECTORY_SERVER);

        $response->assertOk();
        $response->assertSee('<meta content="https://pesats.example.test/satsln-v4" name="app-url" />', false);
        $response->assertSee('src="https://pesats.example.test/satsln-v4/images/logo-light.png"', false);
        $response->assertSee('action="https://pesats.example.test/satsln-v4/login"', false);
        $response->assertDontSee('src="/images/', false);
    }

    public function test_forwarded_headers_are_ignored_from_untrusted_proxy(): void
    {
        config(['trustedproxy.proxies' => null, 'app.asset_url' => null]);

        $response = $this->call('GET', 'http://172.16.3.40:9022/satsln-v4/login', server: self::SUBDIRECTORY_SERVER);

        $response->assertOk();
        $response->assertSee('<meta content="http://172.16.3.40:9022/satsln-v4" name="app-url" />', false);
    }
}
