<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomRealtimeEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create an event that must be delivered to every authorized socket in a room.
     *
     * @param string $name Event name accepted by the realtime gateway.
     * @param int $roomId Target room identifier.
     * @param array<string, mixed> $payload Safe client payload.
     */
    public function __construct(
        public readonly string $name,
        public readonly int $roomId,
        public readonly array $payload,
    ) {
    }
}
