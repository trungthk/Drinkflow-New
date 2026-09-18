<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CrawlerPreview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PruneCrawlerAndLogs extends Command
{
    /** @var string */
    protected $signature = 'drinkflow:prune-crawler-and-logs';

    /** @var string */
    protected $description = 'Delete expired crawler previews and old log files from storage/logs.';

    /**
     * Remove crawler previews and log files that are past their configured retention period.
     *
     * @return int Process exit code.
     */
    public function handle(): int
    {
        $now = now();
        $previewDays = max(1, (int) config('retention.crawler_previews_days', 2));
        $logDays = max(1, (int) config('retention.log_files_days', 14));

        // 1. Prune crawler previews
        $previews = CrawlerPreview::query()
            ->where(function ($query) use ($now, $previewDays): void {
                $query->where('created_at', '<', $now->copy()->subDays($previewDays))
                    ->orWhere('expires_at', '<', $now);
            })
            ->delete();

        // 2. Prune old log files in storage/logs/*.log
        $prunedLogsCount = 0;
        $logCutoff = $now->copy()->subDays($logDays)->timestamp;
        $logFiles = File::glob(storage_path('logs/*.log'));

        foreach ($logFiles as $logFile) {
            if (File::isFile($logFile) && File::lastModified($logFile) < $logCutoff) {
                File::delete($logFile);
                $prunedLogsCount++;
            }
        }

        $this->info("Pruned {$previews} crawler previews and {$prunedLogsCount} log files.");

        return self::SUCCESS;
    }
}
