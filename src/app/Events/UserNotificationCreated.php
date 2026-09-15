<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\UserNotification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create an event for a newly persisted user notification.
     *
     * @param UserNotification $notification Notification delivered to one global user.
     */
    public function __construct(public readonly UserNotification $notification)
    {
    }
}
