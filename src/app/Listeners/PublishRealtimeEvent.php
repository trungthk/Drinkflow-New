<?php

namespace App\Listeners;

use App\Events\CampaignClosed;
use App\Events\CampaignCreated;
use App\Events\OrderCreated;
use App\Events\OrderDeleted;
use App\Events\OrderUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

class PublishRealtimeEvent implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 5;
    public function handle(object $event): void
    {
        [$name, $roomId, $payload, $userChannel] = match (true) {
            $event instanceof OrderCreated => [
                'order.created',
                $event->order->room_id,
                $this->orderPayload($event->order),
                null,
            ],
            $event instanceof OrderUpdated => [
                'order.updated',
                $event->order->room_id,
                $this->orderPayload($event->order) + ['previous_status' => $event->previousStatus],
                null,
            ],
            $event instanceof OrderDeleted => [
                'order.deleted',
                $event->order['room_id'] ?? null,
                $event->order,
                isset($event->order['room_user_id']) ? 'user:'.$event->order['room_user_id'] : null,
            ],
            $event instanceof CampaignCreated => [
                'campaign.created',
                $event->campaign->room_id,
                ['campaign_id' => $event->campaign->id, 'status' => $event->campaign->status?->value],
                null,
            ],
            $event instanceof CampaignClosed => [
                'campaign.closed',
                $event->campaign->room_id,
                ['campaign_id' => $event->campaign->id, 'status' => $event->campaign->status?->value],
                null,
            ],
            default => [null, null, [], null],
        };

        if (! $name || ! $roomId || ! config('services.realtime.url')) {
            return;
        }

        try {
            Http::timeout(2)
                ->withHeaders(['X-Realtime-Secret' => (string) config('services.realtime.internal_secret')])
                ->post(rtrim(config('services.realtime.url'), '/').'/internal/emit', [
                    'event' => $name,
                    'room_id' => (int) $roomId,
                    'user_channel' => $userChannel,
                    'payload' => $payload,
                ]);
        } catch (\Throwable) {
            // Realtime delivery must not roll back a successful database transaction.
        }
    }

    private function orderPayload(object $order): array
    {
        return [
            'order_id' => $order->id,
            'room_id' => $order->room_id,
            'room_user_id' => $order->room_user_id,
            'campaign_id' => $order->campaign_id,
            'status' => $order->status?->value,
        ];
    }
}
