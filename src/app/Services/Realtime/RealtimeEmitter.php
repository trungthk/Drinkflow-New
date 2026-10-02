<?php

declare(strict_types=1);

namespace App\Services\Realtime;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Single place that talks to the realtime gateway's `/internal/emit` endpoint.
 *
 * The gateway only accepts a fixed list of event names and refuses a private event that arrives
 * without a target channel, so callers pass the channel they mean and failures are logged and
 * swallowed here: a notification that could not be pushed live is still stored and shown on the
 * next page load.
 */
class RealtimeEmitter
{
    /**
     * Send one event to the gateway.
     *
     * @param string $event Event name accepted by the gateway.
     * @param int $roomId Room the event belongs to; 0 for events that are not tied to a room.
     * @param array<string, mixed> $payload Event payload.
     * @param string|null $channel Private channel (`admin:{id}`, `superadmin:{id}`…), null for a room event.
     * @return bool True when the gateway accepted the event.
     */
    public function emit(string $event, int $roomId, array $payload, ?string $channel = null): bool
    {
        $url = trim((string) config('services.realtime.url'));
        if ($url === '') {
            return false;
        }

        try {
            Http::timeout(2)
                ->withHeaders([self::HEADER => (string) config('services.realtime.internal_secret')])
                ->post(rtrim($url, '/').'/internal/emit', [
                    'event' => $event,
                    'room_id' => $roomId,
                    'user_channel' => $channel,
                    'payload' => $payload,
                ])
                ->throw();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('DrinkFlow realtime event delivery failed.', [
                'event' => $event,
                'room_id' => $roomId,
                'channel' => $channel,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /** Shared-secret header the gateway authenticates `/internal/emit` with. */
    private const HEADER = 'X-Realtime-Secret';
}
