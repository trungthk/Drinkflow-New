<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CampaignClosed;
use App\Models\Debt;
use App\Services\Notification\UserNotificationService;
use App\Services\Notification\CampaignNotificationPayloadService;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use App\Support\Helpers\FormatHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyCampaignClosed implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 15;

    /**
     * Notify room members about a closed campaign and outstanding debts.
     *
     * @param CampaignClosed $event Campaign closure event.
     * @return void
     */
    public function handle(CampaignClosed $event): void
    {
        $campaign = $event->campaign;
        $payload = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.closed');
        app(UserNotificationService::class)->toRoom($campaign->room, 'campaign.closed', $payload['title'], $payload['message'], ['campaign_id' => $campaign->id, 'room_id' => $campaign->room_id]);
        app(RoomNotificationChannelDispatcher::class)->dispatch($campaign->room, $payload);
        $campaign->debts()->with('roomUser')->where('remaining_amount', '>', 0)->each(function (Debt $debt): void {
            app(UserNotificationService::class)->toRoomUser($debt->roomUser, 'payment.reminder', __('messages.payment_reminder'), 'Số tiền cần thanh toán: '.FormatHelper::formatCurrency((int) $debt->remaining_amount), ['debt_id' => $debt->id, 'campaign_id' => $debt->campaign_id]);
        });
    }
}
