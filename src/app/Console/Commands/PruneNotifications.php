<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AdminNotification;
use App\Models\UserNotification;
use Illuminate\Console\Command;

class PruneNotifications extends Command
{
    /** @var string */
    protected $signature = 'drinkflow:prune-notifications';

    /** @var string */
    protected $description = 'Delete expired read admin and user notifications.';

    /**
     * Remove read notifications that are past their configured retention period.
     *
     * @return int Process exit code.
     */
    public function handle(): int
    {
        $now = now();
        $adminNotificationDays = max(1, (int) config('retention.read_admin_notifications_days', 30));
        $userNotificationDays = max(1, (int) config('retention.read_user_notifications_days', 30));

        // 1. Prune read admin notifications
        $adminNotifications = AdminNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', $now->copy()->subDays($adminNotificationDays))
            ->delete();

        // 2. Prune read user notifications
        $userNotifications = UserNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', $now->copy()->subDays($userNotificationDays))
            ->delete();

        $this->info("Pruned {$adminNotifications} read admin notifications and {$userNotifications} read user notifications.");

        return self::SUCCESS;
    }
}
