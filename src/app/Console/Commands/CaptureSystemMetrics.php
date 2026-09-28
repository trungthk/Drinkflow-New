<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\System\SystemMetricsRecorder;
use Illuminate\Console\Command;

class CaptureSystemMetrics extends Command
{
    /** @var string */
    protected $signature = 'drinkflow:capture-system-metrics';

    /** @var string */
    protected $description = 'Store a queue/storage/socket health snapshot for the superadmin dashboard and prune old snapshots.';

    /**
     * Capture one snapshot, then drop snapshots past the retention window.
     *
     * @param SystemMetricsRecorder $recorder Snapshot writer.
     * @return int Process exit code.
     */
    public function handle(SystemMetricsRecorder $recorder): int
    {
        $snapshot = $recorder->capture();
        $pruned = $recorder->prune();

        $this->info("Captured system metrics snapshot #{$snapshot->id}; pruned {$pruned} old snapshots.");

        return self::SUCCESS;
    }
}
