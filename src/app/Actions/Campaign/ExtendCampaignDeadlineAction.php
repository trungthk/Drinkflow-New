<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CampaignStatus;
use App\Events\CampaignUpdated;
use App\Models\Campaign;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExtendCampaignDeadlineAction
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
     * Push the ordering deadline of a live campaign back by the given number of minutes.
     *
     * The extension is added to the current deadline, or to "now" when the deadline has already passed.
     *
     * @param Campaign $campaign Campaign whose deadline is extended.
     * @param int $minutes Number of minutes to add.
     * @param int|null $adminId Admin performing the change.
     * @return Campaign Fresh campaign with the new deadline.
     * @throws ValidationException When the campaign is not live or has no deadline.
     */
    public function execute(Campaign $campaign, int $minutes, ?int $adminId = null): Campaign
    {
        $updated = DB::transaction(function () use ($campaign, $minutes, $adminId): Campaign {
            $locked = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);

            if (! in_array($locked->status, [CampaignStatus::Active, CampaignStatus::Scheduled], true)) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.campaign_cannot_extend_state'),
                ]);
            }
            if ($locked->deadline === null) {
                throw ValidationException::withMessages([
                    'deadline' => __('admin.campaign_extend_requires_deadline'),
                ]);
            }

            $before = $locked->deadline->copy();
            $base = $before->isFuture() ? $before->copy() : now();
            $locked->update(['deadline' => $base->addMinutes($minutes)]);

            $this->auditService->record(
                'campaign.deadline_extended',
                'campaign',
                $locked->id,
                $locked->room_id,
                ['deadline' => $before->toIso8601String()],
                ['deadline' => $locked->deadline->toIso8601String()],
                ['minutes' => $minutes, 'admin_id' => $adminId]
            );

            return $locked->fresh(['room', 'items']);
        });

        CampaignUpdated::dispatch($updated);

        return $updated;
    }
}
