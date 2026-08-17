<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ServerProcessStatusService;
use Tests\TestCase;

class ServerProcessStatusServiceTest extends TestCase
{
    private function psOutput(array $commandLines): string
    {
        $lines = ['  PID COMMAND'];

        foreach ($commandLines as $i => $command) {
            $lines[] = sprintf('%5d %s', 1000 + $i, $command);
        }

        return implode("\n", $lines);
    }

    public function test_detects_all_three_processes_as_running(): void
    {
        $output = $this->psOutput([
            'php artisan queue:work',
            'php artisan queue:work --queue=notifications --tries=3 --sleep=1',
            'php artisan reverb:start',
        ]);

        $statuses = (new ServerProcessStatusService)->getStatuses($output);

        $this->assertTrue($statuses['queue_default']);
        $this->assertTrue($statuses['queue_notifications']);
        $this->assertTrue($statuses['reverb']);
    }

    public function test_reports_all_false_when_none_are_running(): void
    {
        $output = $this->psOutput(['/sbin/launchd', 'nginx: worker process']);

        $statuses = (new ServerProcessStatusService)->getStatuses($output);

        $this->assertFalse($statuses['queue_default']);
        $this->assertFalse($statuses['queue_notifications']);
        $this->assertFalse($statuses['reverb']);
    }

    /**
     * Worker notifications juga cocok substring "queue:work" biasa - default worker harus
     * dianggap TIDAK jalan kalau satu-satunya proses queue:work yang ada justru worker
     * notifications (mustNotContain exclusion di ServerProcessStatusService::processIsRunning()).
     */
    public function test_notifications_worker_alone_does_not_count_as_the_default_worker(): void
    {
        $output = $this->psOutput([
            'php artisan queue:work --queue=notifications --tries=3 --sleep=1',
        ]);

        $statuses = (new ServerProcessStatusService)->getStatuses($output);

        $this->assertFalse($statuses['queue_default']);
        $this->assertTrue($statuses['queue_notifications']);
    }

    /**
     * Smoke test murni untuk jalur produksi (argumen null -> ambil dari shell_exec("ps ...")
     * sungguhan) - tidak bisa memastikan isi statusnya (tergantung proses OS yang benar-benar
     * berjalan saat test dijalankan), cuma memastikan tidak crash dan bentuk array-nya benar.
     */
    public function test_real_process_list_lookup_does_not_error_and_returns_expected_keys(): void
    {
        $statuses = (new ServerProcessStatusService)->getStatuses(null);

        $this->assertIsArray($statuses);
        $this->assertEqualsCanonicalizing(['queue_default', 'queue_notifications', 'reverb'], array_keys($statuses));
    }
}
