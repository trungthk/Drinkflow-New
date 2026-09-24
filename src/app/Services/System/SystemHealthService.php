<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Enums\SocketHealthReason;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
     * When the check fails, `reason` tells the superadmin *why* (see SocketHealthReason) instead of a
     * bare "unreachable" (plus a translated `reason_message` for the UI), and the failure is logged
     * so production issues can be traced without ever sending the secret to the browser.
     *
     * @return array<string, mixed>
     */
    public function socket(): array
    {
        $endpoint = config('services.realtime.url');
        $secret = (string) config('services.realtime.internal_secret');
        $fallback = [
            'status' => $endpoint ? 'unreachable' : 'unknown',
            'reason' => $endpoint ? null : SocketHealthReason::NotConfigured->value,
            'reason_message' => $endpoint ? null : SocketHealthReason::NotConfigured->message(),
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

        if ($secret === '') {
            // The gateway rejects /health without X-Realtime-Secret, so don't even try.
            return $this->socketFailure($fallback, 'unauthorized', SocketHealthReason::SecretMissing);
        }

        try {
            $health = Http::timeout(3)
                ->withHeaders(['X-Realtime-Secret' => $secret])
                ->get(rtrim($endpoint, '/').'/health')
                ->throw()
                ->json();

            if (! is_array($health)) {
                return $this->socketFailure($fallback, 'error', SocketHealthReason::InvalidResponse);
            }

            return array_merge($fallback, $health, ['status' => 'healthy', 'reason' => null, 'reason_message' => null]);
        } catch (RequestException $e) {
            $httpStatus = $e->response->status();
            $reason = in_array($httpStatus, [401, 403], true) ? SocketHealthReason::SecretMismatch : SocketHealthReason::HttpError;

            return $this->socketFailure($fallback, $reason === SocketHealthReason::SecretMismatch ? 'unauthorized' : 'error', $reason, $e, $httpStatus);
        } catch (ConnectionException $e) {
            return $this->socketFailure($fallback, 'unreachable', SocketHealthReason::ConnectionFailed, $e);
        } catch (Throwable $e) {
            return $this->socketFailure($fallback, 'error', SocketHealthReason::InvalidResponse, $e);
        }
    }

    /**
     * Build the failed socket snapshot and log the cause (host only — never the secret).
     *
     * @param array<string, mixed> $fallback Base snapshot with empty metrics.
     * @param string $status Health status shown as a pill (unreachable|unauthorized|error).
     * @param SocketHealthReason $reason Machine-readable cause (see SocketHealthReason::message()).
     * @param Throwable|null $e Exception that caused the failure, if any.
     * @param int|null $httpStatus HTTP status returned by the gateway, if it answered.
     * @return array<string, mixed> Snapshot describing the failure.
     */
    private function socketFailure(array $fallback, string $status, SocketHealthReason $reason, ?Throwable $e = null, ?int $httpStatus = null): array
    {
        Log::warning('Realtime gateway health check failed.', [
            'reason' => $reason->value,
            'host' => parse_url((string) $fallback['endpoint'], PHP_URL_HOST),
            'http_status' => $httpStatus,
            'exception' => $e ? $e::class : null,
            'message' => $e?->getMessage(),
        ]);

        return array_merge($fallback, [
            'status' => $status,
            'reason' => $reason->value,
            'reason_message' => $reason->message($httpStatus),
            'http_status' => $httpStatus,
        ]);
    }
}
