<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Superadmin "force cancel": take a campaign that has not been finalized out of circulation by
 * archiving it. Orders, debts and notifications are intentionally left untouched.
 */
class ForceArchiveCampaignAction
{
    /** States a superadmin may force-archive; closed/cancelled/archived campaigns are already final. */
    public const ARCHIVABLE_STATUSES = [
        CampaignStatus::Draft,
        CampaignStatus::Scheduled,
        CampaignStatus::Active,
        CampaignStatus::Closing,
    ];

    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * @param Campaign $campaign Campaign to archive.
     * @return Campaign The archived campaign.
     *
     * @throws ValidationException When the campaign is already closed, cancelled or archived.
     */
    public function execute(Campaign $campaign): Campaign
    {
        return DB::transaction(function () use ($campaign): Campaign {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);

            if (! in_array($campaign->status, self::ARCHIVABLE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'campaign' => __('superadmin.actions.campaign_cannot_force_cancel'),
                ]);
            }

            $before = ['status' => $campaign->status->value];
            $campaign->update(['status' => CampaignStatus::Archived]);
            $this->audit->record('campaign.force_cancelled', 'campaign', $campaign->id, $campaign->room_id, $before, ['status' => CampaignStatus::Archived->value]);

            return $campaign->fresh();
        });
    }
}
