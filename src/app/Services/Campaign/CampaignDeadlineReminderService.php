<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Enums\AdminStatus;
use App\Enums\CampaignStatus;
use App\Enums\GlobalUserStatus;
use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Events\AdminNotificationCreated;
use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\Campaign;
use App\Models\CampaignParticipant;
use App\Models\Order;
use App\Models\RoomUser;
use App\Services\Notification\UserNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CampaignDeadlineReminderService
{
    /**
     * Create the service.
     *
     * @param UserNotificationService $userNotifications Persists user notifications and pushes them to the socket.
     */
    public function __construct(
        private readonly UserNotificationService $userNotifications
    ) {
    }

    /**
     * Remind every campaign whose ordering deadline falls within the configured window.
     *
     * @param Carbon|null $now Reference time (defaults to now).
     * @return int Number of campaigns reminded.
     */
    public function remindDueCampaigns(?Carbon $now = null): int
    {
        $minutes = (int) config('campaign.deadline_reminder_minutes', 15);
        if ($minutes <= 0) {
            return 0;
        }

        $now ??= now();
        $reminded = 0;

        $this->dueCampaigns($now, $minutes)->each(function (Campaign $campaign) use ($now, $minutes, &$reminded): void {
            if ($this->remind($campaign, $now, $minutes)) {
                $reminded++;
            }
        });

        return $reminded;
    }

    /**
     * Send the reminder of one campaign, at most once per ordering deadline.
     *
     * The campaign is claimed with a conditional update first, so overlapping scheduler runs (or several servers)
     * never send the same reminder twice.
     *
     * @param Campaign $campaign Campaign that is about to stop taking orders.
     * @param Carbon $now Reference time.
     * @param int $minutes Reminder window in minutes.
     * @return bool False when the campaign was no longer due or another run claimed it first.
     */
    public function remind(Campaign $campaign, Carbon $now, int $minutes): bool
    {
        $claimed = $this->dueQuery($now, $minutes)
            ->whereKey($campaign->id)
            ->update(['deadline_reminder_sent_for' => $campaign->deadline]);
        if ($claimed !== 1) {
            return false;
        }

        $campaign->loadMissing('room');
        $pendingMembers = $this->pendingMembers($campaign);
        $totalMembers = $this->activeMembersQuery($campaign)->count();
        $data = [
            'campaign_id' => $campaign->id,
            'campaign_code' => $campaign->code,
            'campaign_name' => $campaign->name,
            'deadline' => $campaign->deadline->toIso8601String(),
        ];
        $replace = ['campaign' => $campaign->name, 'time' => $this->formatTime($campaign->deadline)];
        $link = route('user.campaigns.order-page', [$campaign->room, $campaign]);

        foreach ($pendingMembers as $roomUser) {
            $this->userNotifications->toRoomUser(
                $roomUser,
                NotificationType::CampaignDeadlineReminder->value,
                __('messages.campaign_deadline_reminder_title'),
                __('messages.campaign_deadline_reminder_body', $replace),
                $data,
                $link
            );
        }

        $this->notifyAdmins($campaign, $data + [
            'pending_count' => $pendingMembers->count(),
            'total_count' => $totalMembers,
        ], $replace);

        return true;
    }

    /**
     * Format a deadline as a wall-clock time in the application timezone.
     *
     * @param Carbon $deadline Ordering deadline.
     * @return string Time such as "11:30".
     */
    public function formatTime(Carbon $deadline): string
    {
        return $deadline->copy()->timezone((string) config('app.timezone'))->format('H:i');
    }

    /**
     * Campaigns that are due for a reminder.
     *
     * @param Carbon $now Reference time.
     * @param int $minutes Reminder window in minutes.
     * @return Collection<int, Campaign>
     */
    private function dueCampaigns(Carbon $now, int $minutes): Collection
    {
        return $this->dueQuery($now, $minutes)->with('room')->get();
    }

    /**
     * Live, unlocked campaigns in an active room whose deadline is within the window and not reminded yet.
     *
     * @param Carbon $now Reference time.
     * @param int $minutes Reminder window in minutes.
     * @return Builder<Campaign>
     */
    private function dueQuery(Carbon $now, int $minutes): Builder
    {
        return Campaign::query()
            ->where('status', CampaignStatus::Active->value)
            ->whereNull('ordering_locked_at')
            ->where('deadline', '>', $now)
            ->where('deadline', '<=', $now->copy()->addMinutes($minutes))
            ->where(static fn (Builder $query) => $query
                ->whereNull('deadline_reminder_sent_for')
                ->orWhereColumn('deadline_reminder_sent_for', '!=', 'deadline'))
            ->whereHas('room', static fn (Builder $query) => $query->where('status', RoomStatus::Active->value));
    }

    /**
     * Active members of the campaign's room whose global account is active too.
     *
     * @param Campaign $campaign Campaign being reminded.
     * @return Builder<RoomUser>
     */
    private function activeMembersQuery(Campaign $campaign): Builder
    {
        return RoomUser::query()
            ->where('room_id', $campaign->room_id)
            ->where('status', RoomUserStatus::Active->value)
            ->whereHas('globalUser', static fn (Builder $query) => $query->where('status', GlobalUserStatus::Active->value));
    }

    /**
     * Active members who have neither a non-cancelled order in the campaign nor declined it.
     *
     * @param Campaign $campaign Campaign being reminded.
     * @return Collection<int, RoomUser>
     */
    private function pendingMembers(Campaign $campaign): Collection
    {
        return $this->activeMembersQuery($campaign)
            ->whereNotIn('id', Order::query()
                ->select('room_user_id')
                ->where('campaign_id', $campaign->id)
                ->where('status', '!=', OrderStatus::Cancelled->value))
            ->whereNotIn('id', CampaignParticipant::query()
                ->select('room_user_id')
                ->where('campaign_id', $campaign->id)
                ->where('status', CampaignParticipant::STATUS_DECLINED))
            ->get();
    }

    /**
     * Store one summary notification per active room admin and push it to their open admin pages.
     *
     * @param Campaign $campaign Campaign being reminded.
     * @param array<string, mixed> $data Structured notification data (including member counts).
     * @param array<string, string> $replace Translation replacements for the campaign name and time.
     */
    private function notifyAdmins(Campaign $campaign, array $data, array $replace): void
    {
        $campaign->room->admins()
            ->where('status', AdminStatus::Active->value)
            ->each(function (AdminAccount $admin) use ($campaign, $data, $replace): void {
                $notification = AdminNotification::create([
                    'admin_id' => $admin->id,
                    'room_id' => $campaign->room_id,
                    'type' => NotificationType::CampaignDeadlineReminder->value,
                    'title' => __('admin.audit_event_campaign_deadline_reminder'),
                    'body' => __('admin.campaign_deadline_reminder_body', $replace + [
                        'pending' => $data['pending_count'],
                        'total' => $data['total_count'],
                    ]),
                    'data' => $data,
                ]);
                AdminNotificationCreated::dispatch($notification);
            });
    }
}
