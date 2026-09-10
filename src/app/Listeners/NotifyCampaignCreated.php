<?php

namespace App\Listeners;

use App\Events\CampaignCreated;
use App\Services\Notification\UserNotificationService;

class NotifyCampaignCreated
{
    public function handle(CampaignCreated $event): void
    {
        $campaign = $event->campaign;
        if (! in_array($campaign->status?->value, ['active', 'scheduled'], true)) {
            return;
        }
        app(UserNotificationService::class)->toRoom($campaign->room, 'campaign.created', 'Campaign mới', $campaign->name, ['campaign_id' => $campaign->id, 'room_id' => $campaign->room_id]);
    }
}
