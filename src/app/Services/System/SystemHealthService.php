<?php

declare(strict_types=1);

namespace App\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Single source of truth for the superadmin "system health" snapshot (dashboard + system settings page),
 * so both controllers report the exact same numbers instead of duplicating each check.
 */
class SystemHealthService
{
    public function __construct(
        private readonly MailHealthService $mail,
        private readonly StorageHealthService $storage,
        private readonly SupervisorHealthService $supervisor,
    ) {
    }

    /**
     * @return array<string, mixed> Every infrastructure signal the superadmin monitoring pages render.
     *  `supervisor` is omitted entirely (not just null) when it should stay hidden — see SupervisorHealthService.
     */
    public function snapshot(): array
    {
        $snapshot = [
            'database' => ['status' => $this->database()],
            'queue' => $this->queue(),
            'socket' => $this->socket(),
            'mail' => $this->mail->check(),
            'storage' => $this->storage->check(),
        ];

        $supervisor = $this->supervisor->check();
        if ($supervisor !== null) {
            $snapshot['supervisor'] = $supervisor;
        }

        return $snapshot;
    }

    /**
     * @return 'ok'|'error'
     */
    public function database(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    /**
     * @return array{connection: string, failed_jobs: int}
     */
    public function queue(): array
    {
        return [
            'connection' => (string) config('queue.default'),
            'failed_jobs' => (int) DB::table('failed_jobs')->count(),
        ];
    }

    /**
     * Query the realtime gateway's `/health` endpoint. Moved here (from SocketMonitoringController)
     * so the dashboard snapshot and the dedicated Socket.IO page report identical data.
     *
     * @return array<string, mixed>
     */
    public function socket(): array
    {
        $endpoint = config('services.realtime.url');
        $fallback = [
            'status' => $endpoint ? 'unreachable' : 'unknown',
            'endpoint' => $endpoint,
            'connected_users' => null,
            'connected_admins' => null,
            'connected_superadmins' => null,
            'connections_by_room' => [],
            'recent_disconnects' => [],
            'authentication_failures' => null,
        ];

        if (! $endpoint) {
            return $fallback;
        }

        try {
            $health = Http::timeout(2)
                ->withHeaders(['X-Realtime-Secret' => (string) config('services.realtime.internal_secret')])
                ->get(rtrim($endpoint, '/').'/health')
                ->throw()
                ->json();

            return array_merge($fallback, $health, ['status' => 'healthy']);
        } catch (Throwable) {
            return $fallback;
        }
    }
}
