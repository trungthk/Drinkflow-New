<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Maintenance mode was switched on, off or rescheduled.
 *
 * Published to every connected page (public visitors, users, room admins) as the realtime event
 * `system.maintenance` by App\Listeners\PublishRealtimeEvent, which reads the current state when it runs.
 */
class MaintenanceStateChanged
{
    use Dispatchable;
}
