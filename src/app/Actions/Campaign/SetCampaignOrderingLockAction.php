<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\NotificationType;
use App\Events\RoomRealtimeEvent;
use App\Models\Campaign;
use App\Services\Audit\AuditService;
use App\Services\Notification\UserNotificationService;
use App\Support\Helpers\FormatHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetCampaignOrderingLockAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly UserNotificationService $notifications,
    ) {
    }

    /**
     * Lock or unlock member ordering on a running campaign ("khóa chiến dịch").
     *
     * While locked the campaign stays active, but members can no longer place orders, add to
     * their cart or change their participation; room admins can still order on a member's behalf.
     * Only a campaign that is active and still before its deadline can be locked or unlocked.
     *
     * @param Campaign $campaign Campaign to lock/unlock.
     * @param bool $locked True to lock member ordering, false to reopen it.
     * @param int|null $adminId Admin performing the change (for the audit log).
     * @param bool $notify Whether to send an in-app notification to the room's active members.
     * @return Campaign Fresh campaign.
     * @throws ValidationException When the campaign is not active or its deadline has passed.
     */
    public function execute(Campaign $campaign, bool $locked, ?int $adminId = null, bool $notify = false): Campaign
    {
        $changed = DB::transaction(function () use ($campaign, $locked): bool {
            /** @var Campaign $locking */
            $locking = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if (! $locking->isOpenForOrders()) {
                throw ValidationException::withMessages([
                    'campaign' => __('admin.campaign_lock_not_active'),
                ]);
            }
            if ($locking->isOrderingLocked() === $locked) {
                return false;
            }

            $locking->forceFill(['ordering_locked_at' => $locked ? now() : null])->save();

            return true;
        });

        $fresh = $campaign->fresh('room');
        if (! $changed) {
            return $fresh;
        }

        $this->audit->record(
            $locked ? 'campaign.ordering_locked' : 'campaign.ordering_unlocked',
            'campaign',
            $fresh->id,
            $fresh->room_id,
            ['ordering_locked' => ! $locked],
            ['ordering_locked' => $locked],
            ['admin_id' => $adminId, 'notified' => $notify],
        );

        // Open member pages reload so the order form reflects the new state immediately.
        RoomRealtimeEvent::dispatch('campaign.updated', (int) $fresh->room_id, [
            'campaign_id' => $fresh->id,
            'ordering_locked' => $locked,
        ]);

        if ($notify && $fresh->room !== null) {
            $this->notifyMembers($fresh, $locked);
        }

        return $fresh;
    }

    /**
     * Tell the room's active members that ordering was locked or reopened.
     *
     * @param Campaign $campaign Campaign with its room loaded.
     * @param bool $locked New lock state.
     * @return void
     */
    private function notifyMembers(Campaign $campaign, bool $locked): void
    {
        $title = $locked
            ? __('messages.campaign_ordering_locked_title', ['code' => $campaign->code])
            : __('messages.campaign_ordering_unlocked_title');
        $body = $locked
            ? __('messages.campaign_ordering_locked_body', ['name' => $campaign->name])
            : __('messages.campaign_ordering_unlocked_body', [
                'name' => $campaign->name,
                'deadline' => $campaign->deadline
                    ? FormatHelper::formatDateTime($campaign->deadline, 'H:i d/m/Y')
                    : __('messages.campaign_deadline_not_set'),
            ]);

        $this->notifications->toRoom(
            $campaign->room,
            NotificationType::CampaignUpdated->value,
            $title,
            $body,
            ['campaign_id' => $campaign->id, 'campaign_code' => $campaign->code, 'room_id' => $campaign->room_id, 'ordering_locked' => $locked],
            route('user.campaigns.index', $campaign->room->slug),
        );
    }
}
