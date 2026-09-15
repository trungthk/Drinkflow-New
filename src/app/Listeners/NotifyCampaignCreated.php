<?php

namespace App\Listeners;

use App\Events\CampaignCreated;
use App\Services\Notification\UserNotificationService;
use App\Services\Notification\CampaignNotificationPayloadService;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCampaignCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 10;
    public function handle(CampaignCreated $event): void
    {
        $campaign = $event->campaign;
        if (! in_array($campaign->status?->value, ['active', 'scheduled'], true)) {
            return;
        }
        $payload = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.created');
        app(UserNotificationService::class)->toRoom($campaign->room, 'campaign.created', $payload['title'], $payload['message'], ['campaign_id' => $campaign->id, 'room_id' => $campaign->room_id, 'deadline' => $payload['campaign']['deadline'], 'order_url' => $payload['campaign']['order_url']]);
        app(RoomNotificationChannelDispatcher::class)->dispatch($campaign->room, $payload);
    }
}
