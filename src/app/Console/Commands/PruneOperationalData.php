<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AdminNotification;
use App\Models\AuditLog;
use App\Models\CrawlerPreview;
use Illuminate\Console\Command;

class PruneOperationalData extends Command
{
    /** @var string */
    protected $signature = 'drinkflow:prune-operational-data';

    /** @var string */
    protected $description = 'Delete expired audit logs, read admin notifications, and crawler previews.';

    /**
     * Remove data that is past its configured retention period.
     *
     * @return int Process exit code.
     */
    public function handle(): int
    {
        $now = now();
        $auditDays = max(1, (int) config('retention.audit_logs_days', 30));
        $notificationDays = max(1, (int) config('retention.read_admin_notifications_days', 30));
        $previewDays = max(1, (int) config('retention.crawler_previews_days', 2));

        $auditLogs = AuditLog::query()->where('created_at', '<', $now->copy()->subDays($auditDays))->delete();
        $notifications = AdminNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', $now->copy()->subDays($notificationDays))
            ->delete();
        $previews = CrawlerPreview::query()
            ->where(function ($query) use ($now, $previewDays): void {
                $query->where('created_at', '<', $now->copy()->subDays($previewDays))
                    ->orWhere('expires_at', '<', $now);
            })
            ->delete();

        $this->info("Pruned {$auditLogs} audit logs, {$notifications} read admin notifications, and {$previews} crawler previews.");

        return self::SUCCESS;
    }
}
