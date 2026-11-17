<?php

declare(strict_types=1);

namespace App\Services;

class ServerProcessStatusService
{
    /**
     * Cek apakah tiga proses background yang disebut di CLAUDE.md ("Run Queues & Reverb")
     * masih berjalan di server, lewat daftar proses OS (`ps`) - bukan heartbeat/cache, supaya
     * status yang ditampilkan benar-benar mencerminkan proses OS saat ini, bukan "terakhir kali
     * lapor". $processListOutput bisa di-inject manual untuk testing (lihat
     * tests/Unit/ServerProcessStatusServiceTest.php); null berarti ambil langsung dari OS.
     *
     * Nilai `null` per proses berarti status tidak bisa ditentukan (mis. shell_exec dinonaktifkan
     * di server), bukan berarti proses itu mati.
     */
    public function getStatuses(?string $processListOutput = null): array
    {
        $output = $processListOutput ?? $this->fetchProcessListOutput();

        if ($output === null) {
            return [
                'queue_default' => null,
                'queue_notifications' => null,
                'reverb' => null,
            ];
        }

        return [
            'queue_default' => $this->processIsRunning($output, 'artisan queue:work', ['--queue=notifications']),
            'queue_notifications' => $this->processIsRunning($output, 'artisan queue:work --queue=notifications'),
            'reverb' => $this->processIsRunning($output, 'artisan reverb:start'),
        ];
    }

    /**
     * @param  string  $procPath  Root procfs Linux; bisa diarahkan ke folder palsu untuk testing.
     */
    public function __construct(private readonly string $procPath = '/proc') {}

    private function fetchProcessListOutput(): ?string
    {
        return $this->readProcFilesystem() ?? $this->runPs();
    }

    /**
     * Baca command line tiap proses langsung dari /proc/<pid>/cmdline (Linux). Tidak butuh
     * shell_exec, yang di server produksi (aaPanel) dinonaktifkan lewat disable_functions.
     */
    private function readProcFilesystem(): ?string
    {
        $cmdlineFiles = @glob($this->procPath.'/[0-9]*/cmdline');

        if ($cmdlineFiles === false || $cmdlineFiles === []) {
            return null;
        }

        $lines = [];

        foreach ($cmdlineFiles as $file) {
            $cmdline = @file_get_contents($file);

            if ($cmdline !== false && $cmdline !== '') {
                $lines[] = basename(dirname($file)).' '.trim(str_replace("\0", ' ', $cmdline));
            }
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    private function runPs(): ?string
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (! function_exists('shell_exec') || in_array('shell_exec', $disabled, true)) {
            return null;
        }

        $output = @shell_exec('ps -eo pid,command 2>/dev/null');

        return $output !== null && $output !== '' ? $output : null;
    }

    private function processIsRunning(string $processListOutput, string $mustContain, array $mustNotContain = []): bool
    {
        foreach (explode("\n", $processListOutput) as $line) {
            if (! str_contains($line, $mustContain)) {
                continue;
            }

            $isExcluded = false;

            foreach ($mustNotContain as $excludedString) {
                if (str_contains($line, $excludedString)) {
                    $isExcluded = true;

                    break;
                }
            }

            if (! $isExcluded) {
                return true;
            }
        }

        return false;
    }
}
