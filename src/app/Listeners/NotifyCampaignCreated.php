<?php

namespace App\Listeners;

use App\Events\CampaignCreated;
use App\Services\Notification\UserNotificationService;
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
        app(UserNotificationService::class)->toRoom($campaign->room, 'campaign.created', 'Campaign mới', $campaign->name, ['campaign_id' => $campaign->id, 'room_id' => $campaign->room_id]);
    }
}
