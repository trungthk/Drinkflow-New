<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CampaignCancelled;
use App\Events\CampaignClosed;
use App\Events\CampaignCreated;
use App\Events\CampaignDelivering;
use App\Events\CampaignUpdated;
use App\Events\OrderCreated;
use App\Events\OrderDeleted;
use App\Events\OrderUpdated;
use App\Events\RoomMembershipUpdated;
use App\Events\RoomRealtimeEvent;
use App\Events\UserNotificationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublishRealtimeEvent implements ShouldQueue
{
    use InteractsWithQueue;

    public ?string $connection = 'database';

    public int $tries = 3;

    public int $timeout = 5;

    public array $backoff = [1, 5, 15];

    /**
     * Handle the event and broadcast realtime socket message.
     *
     * @param  object  $event  Dispatched event instance.
     */
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
                [
                    'campaign_id' => $event->campaign->id,
                    'name' => $event->campaign->name,
                    'restaurant' => $event->campaign->restaurant,
                    'deadline' => $event->campaign->deadline?->toIso8601String(),
                    'status' => $event->campaign->status?->value,
                ],
                null,
            ],
            $event instanceof CampaignUpdated => [
                'campaign.updated',
                $event->campaign->room_id,
                [
                    'campaign_id' => $event->campaign->id,
                    'name' => $event->campaign->name,
                    'restaurant' => $event->campaign->restaurant,
                    'deadline' => $event->campaign->deadline?->toIso8601String(),
                    'status' => $event->campaign->status?->value,
                ],
                null,
            ],
            $event instanceof CampaignClosed => [
                'campaign.closed',
                $event->campaign->room_id,
                ['campaign_id' => $event->campaign->id, 'status' => $event->campaign->status?->value],
                null,
            ],
            $event instanceof CampaignCancelled => [
                'campaign.cancelled',
                $event->campaign->room_id,
                ['campaign_id' => $event->campaign->id, 'status' => $event->campaign->status?->value],
                null,
            ],
            $event instanceof CampaignDelivering => [
                'campaign.delivering',
                $event->campaign->room_id,
                [
                    'campaign_id' => $event->campaign->id,
                    'campaign_code' => $event->campaign->code,
                    'restaurant' => $event->campaign->restaurant,
                    'status' => $event->campaign->status?->value,
                    'orders_status' => 'delivering',
                ],
                null,
            ],
            $event instanceof RoomRealtimeEvent => [
                $event->name,
                $event->roomId,
                $event->payload,
                null,
            ],
            $event instanceof UserNotificationCreated => [
                'notification.created',
                $event->notification->roomUser?->room_id ?? 0,
                [
                    'id' => $event->notification->id,
                    'type' => $event->notification->type,
                    'title' => $event->notification->title,
                    'body' => $event->notification->body,
                    'data' => $event->notification->data ?? [],
                ],
                'global_user:'.$event->notification->global_user_id,
            ],
            $event instanceof RoomMembershipUpdated => [
                'room.membership.updated',
                $event->roomUser->room_id,
                [
                    'room_id' => $event->roomUser->room_id,
                    'room_user_id' => $event->roomUser->id,
                    'status' => $event->roomUser->status->value,
                ],
                'global_user:'.$event->roomUser->global_user_id,
            ],
            default => [null, null, [], null],
        };

        if (! $name || ! config('services.realtime.url')) {
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
                ])
                ->throw();
        } catch (\Throwable $exception) {
            Log::warning('DrinkFlow realtime event delivery failed.', [
                'event' => $name,
                'room_id' => $roomId,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Build normalized order payload for realtime broadcasting.
     *
     * @param  object  $order  Order instance.
     * @return array<string, mixed>
     */
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
