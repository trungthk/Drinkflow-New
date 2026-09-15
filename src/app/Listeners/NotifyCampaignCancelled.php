<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CampaignCancelled;
use App\Services\Notification\CampaignNotificationPayloadService;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use App\Services\Notification\UserNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCampaignCancelled implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 15;

    /** Notify room users and configured channels that a campaign was cancelled. */
    public function handle(CampaignCancelled $event): void
    {
        $campaign = $event->campaign;
        $payload = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.cancelled');
        app(UserNotificationService::class)->toRoom($campaign->room, 'campaign.cancelled', $payload['title'], $payload['message'], ['campaign_id' => $campaign->id, 'room_id' => $campaign->room_id]);
        app(RoomNotificationChannelDispatcher::class)->dispatch($campaign->room, $payload);
    }
}
