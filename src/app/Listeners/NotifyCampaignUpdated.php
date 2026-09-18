<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CampaignStatus;
use App\Events\CampaignUpdated;
use App\Services\Notification\CampaignNotificationPayloadService;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use App\Services\Notification\UserNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCampaignUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 15;

    /**
     * Notify room users and configured channels that a live campaign was updated.
     *
     * @param CampaignUpdated $event Campaign update event.
     * @return void
     */
    public function handle(CampaignUpdated $event): void
    {
        $campaign = $event->campaign;
        // Chỉ gửi thông báo khi campaign đang live (active, scheduled, closing)
        if (! in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Scheduled, CampaignStatus::Closing], true)) {
            return;
        }

        $payload = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.updated');
        app(UserNotificationService::class)->toRoom(
            $campaign->room,
            'campaign.updated',
            $payload['title'],
            $payload['message'],
            ['campaign_id' => $campaign->id, 'room_id' => $campaign->room_id]
        );
        app(RoomNotificationChannelDispatcher::class)->dispatch($campaign->room, $payload);
    }
}
