<?php

declare(strict_types=1);

namespace App\Services\System;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Reads the real status of the queue-worker program(s) from Supervisor via `supervisorctl status`.
 *
 * Only meaningful on a server that actually runs `php artisan queue:work` under Supervisor; see
 * config/services.php `supervisor.*`. Every value that reaches the shell command comes from
 * config()/.env, never from a request, so there is no injection surface here.
 */
class SupervisorHealthService
{
    private const CACHE_KEY = 'superadmin:supervisor-health';

    private const CACHE_SECONDS = 15;

    /**
     * @return array{program: string, status: string, raw: string}|null Null means "hide the card":
     *  not enabled, not configured, or running on a local dev machine with no real Supervisor.
     */
    public function check(): ?array
    {
        if (app()->environment('local') || ! config('services.supervisor.enabled')) {
            return null;
        }

        $program = trim((string) config('services.supervisor.program', ''));
        if ($program === '') {
            return null;
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn (): array => $this->queryStatus($program));
    }

    /**
     * @param string $program Supervisor program (or group) name from config.
     * @return array{program: string, status: string, raw: string}
     */
    private function queryStatus(string $program): array
    {
        $supervisorctl = (string) config('services.supervisor.supervisorctl_path', '/usr/bin/supervisorctl');
        if (! is_file($supervisorctl)) {
            return ['program' => $program, 'status' => 'unreachable', 'raw' => ''];
        }

        $command = sprintf('%s status %s 2>&1', escapeshellarg($supervisorctl), escapeshellarg($program));
        $output = @shell_exec($command);

        if (! is_string($output) || trim($output) === '') {
            Log::warning('Supervisor health check returned no output.', ['program' => $program]);

            return ['program' => $program, 'status' => 'unreachable', 'raw' => ''];
        }

        return ['program' => $program, 'status' => $this->parseStatus($output), 'raw' => trim($output)];
    }

    /**
     * Reduce `supervisorctl status` output to a single worst-case status label across all matched processes.
     *
     * @param string $output Raw supervisorctl output, one process per line.
     * @return string One of running|starting|stopped|fatal|unknown.
     */
    private function parseStatus(string $output): string
    {
        $rank = ['FATAL' => 4, 'BACKOFF' => 4, 'STOPPED' => 3, 'EXITED' => 3, 'STARTING' => 2, 'RUNNING' => 1];
        $worst = null;

        foreach (explode("\n", $output) as $line) {
            $parts = preg_split('/\s+/', trim($line), 3);
            $state = strtoupper($parts[1] ?? '');
            if (! isset($rank[$state])) {
                continue;
            }
            if ($worst === null || $rank[$state] > $rank[$worst]) {
                $worst = $state;
            }
        }

        return match ($worst) {
            'RUNNING' => 'running',
            'STARTING' => 'starting',
            'STOPPED', 'EXITED' => 'stopped',
            'FATAL', 'BACKOFF' => 'fatal',
            default => 'unknown',
        };
    }
}
