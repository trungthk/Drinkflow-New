<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AdminNotification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create an event for a newly persisted admin notification that should reach the admin's open pages live.
     *
     * @param AdminNotification $notification Notification delivered to one admin account.
     */
    public function __construct(public readonly AdminNotification $notification)
    {
    }
}
