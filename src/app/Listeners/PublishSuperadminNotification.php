<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SuperadminNotificationCreated;
use App\Services\Realtime\RealtimeEmitter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Publish a Superadmin platform notification to the realtime gateway.
 *
 * Kept in its own listener instead of a branch of {@see PublishRealtimeEvent} because the event has
 * no room: it is delivered on the recipient account's private channel only, so one Superadmin can
 * never receive another one's alert.
 */
class PublishSuperadminNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public ?string $connection = 'database';

    public int $tries = 3;

    public int $timeout = 5;

    public array $backoff = [1, 5, 15];

    public function __construct(private readonly RealtimeEmitter $emitter)
    {
    }

    /**
     * Publish `superadmin.notification.created` on the recipient's private channel.
     *
     * @param SuperadminNotificationCreated $event Stored notification.
     * @return void
     */
    public function handle(SuperadminNotificationCreated $event): void
    {
        $notification = $event->notification;

        $this->emitter->emit('superadmin.notification.created', 0, [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'data' => $notification->data ?? [],
        ], 'superadmin:'.$notification->superadmin_id);
    }
}
