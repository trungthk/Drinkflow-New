<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\SystemMetricSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Captures infrastructure readings into system_metric_snapshots (for the superadmin dashboard history)
 * and prunes snapshots past the retention window. Reuses SystemHealthService so the history matches
 * what the live health panel shows.
 */
class SystemMetricsRecorder
{
    /** Socket status reported by SystemHealthService when the gateway answered. */
    private const SOCKET_HEALTHY = 'healthy';

    /** Socket status reported when REALTIME_URL is not configured at all (nothing to measure). */
    private const SOCKET_UNKNOWN = 'unknown';

    public function __construct(
        private readonly SystemHealthService $health,
        private readonly StorageHealthService $storage,
    ) {
    }

    /**
     * Take one snapshot of queue, storage, database and realtime health.
     *
     * @param CarbonImmutable|null $at Capture time (defaults to now).
     * @return SystemMetricSnapshot The stored snapshot.
     */
    public function capture(?CarbonImmutable $at = null): SystemMetricSnapshot
    {
        $queue = $this->health->queue();
        $storage = $this->storage->check();
        $socket = $this->health->socket();
        $socketStatus = (string) ($socket['status'] ?? self::SOCKET_UNKNOWN);

        return SystemMetricSnapshot::create([
            'captured_at' => $at ?? CarbonImmutable::now(),
            'database_ok' => $this->health->database() === 'ok',
            'pending_jobs' => $this->pendingJobs(),
            'failed_jobs' => (int) $queue['failed_jobs'],
            'storage_used_bytes' => $storage['used_bytes'] ?? null,
            'storage_total_bytes' => $storage['total_bytes'] ?? null,
            'socket_ok' => $socketStatus === self::SOCKET_UNKNOWN ? null : $socketStatus === self::SOCKET_HEALTHY,
            'socket_connections' => $this->socketConnections($socket),
        ]);
    }

    /**
     * Delete snapshots older than the configured retention.
     *
     * @return int Number of deleted snapshots.
     */
    public function prune(): int
    {
        $days = max(1, (int) config('retention.system_metrics_days', 30));

        return SystemMetricSnapshot::query()->where('captured_at', '<', CarbonImmutable::now()->subDays($days))->delete();
    }

    /**
     * Jobs waiting on the default queue, or null when the driver cannot report a size.
     */
    private function pendingJobs(): ?int
    {
        try {
            return (int) Queue::size();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Total live connections reported by the gateway (users + admins + superadmins), or null when unknown.
     *
     * @param array<string, mixed> $socket Socket section of the health snapshot.
     */
    private function socketConnections(array $socket): ?int
    {
        $counts = array_filter(
            [$socket['connected_users'] ?? null, $socket['connected_admins'] ?? null, $socket['connected_superadmins'] ?? null],
            static fn(mixed $value): bool => is_numeric($value),
        );

        return $counts === [] ? null : (int) array_sum($counts);
    }
}
