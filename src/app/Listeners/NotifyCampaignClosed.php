<?php

namespace App\Listeners;

use App\Events\CampaignClosed;
use App\Services\Notification\UserNotificationService;
use App\Services\Notification\CampaignNotificationPayloadService;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCampaignClosed implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 15;
    public function handle(CampaignClosed $event): void
    {
        $campaign = $event->campaign;
        $payload = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.closed');
        app(UserNotificationService::class)->toRoom($campaign->room, 'campaign.closed', $payload['title'], $payload['message'], ['campaign_id' => $campaign->id, 'room_id' => $campaign->room_id]);
        app(RoomNotificationChannelDispatcher::class)->dispatch($campaign->room, $payload);
        $campaign->debts()->with('roomUser')->where('remaining_amount', '>', 0)->each(function ($debt): void {
            app(UserNotificationService::class)->toRoomUser($debt->roomUser, 'payment.reminder', __('messages.payment_reminder'), 'Số tiền cần thanh toán: '.$debt->remaining_amount, ['debt_id' => $debt->id, 'campaign_id' => $debt->campaign_id]);
        });
    }
}
