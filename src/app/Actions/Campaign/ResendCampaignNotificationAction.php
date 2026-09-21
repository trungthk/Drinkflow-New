<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Events\CampaignCreated;
use App\Models\Campaign;
use App\Services\Audit\AuditService;
use Illuminate\Validation\ValidationException;

class ResendCampaignNotificationAction
{
    /**
     * Create the action instance.
     *
     * @param AuditService $auditService Audit logger service.
     */
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    /**
     * Re-announce a live campaign to room members.
     *
     * Dispatching CampaignCreated again fans out to every delivery path: in-app web notifications,
     * the room's channel gateways (Telegram/Discord/Slack/webhook) and the realtime socket.
     *
     * @param Campaign $campaign Live campaign to announce again.
     * @param int|null $adminId Admin triggering the resend.
     * @return Campaign Campaign with its room relation loaded.
     * @throws ValidationException When the campaign is not live.
     */
    public function execute(Campaign $campaign, ?int $adminId = null): Campaign
    {
        if ($campaign->status !== CampaignStatus::Active) {
            throw ValidationException::withMessages([
                'campaign' => __('admin.campaign_cannot_resend_notification_state'),
            ]);
        }

        $campaign->loadMissing('room');

        $this->auditService->record(
            'campaign.notification_resent',
            'campaign',
            $campaign->id,
            $campaign->room_id,
            [],
            [],
            ['admin_id' => $adminId]
        );

        CampaignCreated::dispatch($campaign);

        return $campaign;
    }
}
