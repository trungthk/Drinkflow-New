<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SuperadminNotification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A platform notification was stored for one Superadmin and should reach that account's open console live.
 */
class SuperadminNotificationCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param SuperadminNotification $notification Notification delivered to one Superadmin account.
     */
    public function __construct(public readonly SuperadminNotification $notification)
    {
    }
}
