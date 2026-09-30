<?php

declare(strict_types=1);

namespace App\Services\Room;

use App\Models\Admin;
use App\Models\AdminSubscription;
use App\Models\Room;
use Illuminate\Validation\ValidationException;

/**
 * Room quota of an Agent, read from the `room_limit_snapshot` of its active subscription.
 *
 * Active and disabled rooms use a slot; archived rooms do not. An Agent without an active
 * subscription has no quota. Every room creation or restore by an Agent must call
 * {@see self::ensureCanAdd()} inside a transaction that has locked the Agent row, so two
 * concurrent requests cannot both take the last slot; hiding the button is only UX.
 */
class RoomQuotaService
{
    /**
     * Room limit of the Agent's current subscription.
     *
     * @param Admin $admin Agent.
     * @return int Allowed rooms (0 without an active subscription).
     */
    public function limit(Admin $admin): int
    {
        return (int) (AdminSubscription::query()->where('admin_id', $admin->id)->active()->value('room_limit_snapshot') ?? 0);
    }

    /**
     * Rooms currently using a quota slot.
     *
     * @param Admin $admin Agent.
     * @return int Owned active + disabled rooms.
     */
    public function used(Admin $admin): int
    {
        return Room::query()->where('owner_admin_id', $admin->id)->countingTowardQuota()->count();
    }

    /**
     * Usage summary for the UI ("Rooms X / Limit").
     *
     * @param Admin $admin Agent.
     * @return array{used: int, limit: int, remaining: int, has_subscription: bool, state: string} Usage; state is ok, near, full or none.
     */
    public function usage(Admin $admin): array
    {
        $hasSubscription = AdminSubscription::query()->where('admin_id', $admin->id)->active()->exists();
        $limit = $this->limit($admin);
        $used = $this->used($admin);
        $remaining = max(0, $limit - $used);
        $state = match (true) {
            ! $hasSubscription => 'none',
            $remaining === 0 => 'full',
            $limit > 0 && $used / $limit >= 0.8 => 'near',
            default => 'ok',
        };

        return ['used' => $used, 'limit' => $limit, 'remaining' => $remaining, 'has_subscription' => $hasSubscription, 'state' => $state];
    }

    /**
     * Whether one more room fits in the quota.
     *
     * @param Admin $admin Agent.
     * @return bool True when a slot is free.
     */
    public function canAdd(Admin $admin): bool
    {
        return $this->used($admin) < $this->limit($admin);
    }

    /**
     * Refuse adding a room (create or restore) when the quota is used up.
     *
     * @param Admin $admin Agent, locked by the caller's transaction.
     * @return void
     * @throws ValidationException When no slot is free or there is no active subscription.
     */
    public function ensureCanAdd(Admin $admin): void
    {
        $usage = $this->usage($admin);
        if (! $usage['has_subscription']) {
            throw ValidationException::withMessages(['room' => __('platform.rooms.no_subscription')]);
        }
        if ($usage['remaining'] === 0) {
            throw ValidationException::withMessages(['room' => __('platform.rooms.quota_reached', ['limit' => $usage['limit']])]);
        }
    }
}
