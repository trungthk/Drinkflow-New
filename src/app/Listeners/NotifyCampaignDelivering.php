<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CampaignDelivering;
use App\Services\Notification\CampaignNotificationPayloadService;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use App\Services\Notification\UserNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCampaignDelivering implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @param UserNotificationService $userNotificationService Web notification service.
     * @param CampaignNotificationPayloadService $payloadService Channel payload formatter.
     * @param RoomNotificationChannelDispatcher $channelDispatcher Webhook/Bot dispatcher.
     */
    public function __construct(
        private readonly UserNotificationService $userNotificationService,
        private readonly CampaignNotificationPayloadService $payloadService,
        private readonly RoomNotificationChannelDispatcher $channelDispatcher
    ) {
    }

    /**
     * Handle the event.
     *
     * @param CampaignDelivering $event Dispatched event instance.
     */
    public function handle(CampaignDelivering $event): void
    {
        $campaign = $event->campaign->loadMissing(['room', 'orders.roomUser.globalUser']);
        $room = $campaign->room;

        // 1. Gửi thông báo Web In-App đến tất cả thành viên trong Room
        $this->userNotificationService->toRoom(
            room: $room,
            type: 'campaign.delivering',
            title: __('messages.campaign_delivering_title'),
            body: __('messages.campaign_delivering_body', [
                'restaurant' => $campaign->restaurant,
                'code' => $campaign->code,
            ]),
            data: [
                'campaign_id' => $campaign->id,
                'campaign_code' => $campaign->code,
                'restaurant' => $campaign->restaurant,
            ]
        );

        // 2. Gửi thông báo qua Channel Gateway (Telegram / Discord / Slack)
        $payload = $this->payloadService->make($campaign, 'campaign.delivering');
        $this->channelDispatcher->dispatch($room, $payload);
    }
}
