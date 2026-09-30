<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\AdminSubscription;
use App\Models\Room;

/**
 * Whether a room's tenant (its owning Agent) is allowed to operate it.
 *
 * A room is blocked when its owner is no longer active, or when the owner had a subscription that
 * has ended (expired or cancelled) without a new active one. Rooms of Agents that never had a
 * subscription (accounts from before the SaaS model, or rooms without owner yet) are not blocked:
 * they are reported by `rooms:ownership` and get a subscription from a Superadmin.
 */
class AgentAccessService
{
    public const REASON_OWNER_INACTIVE = 'owner_inactive';

    public const REASON_NO_SUBSCRIPTION = 'no_subscription';

    /**
     * Reason the room cannot be operated, or null when it can.
     *
     * @param Room $room Room.
     * @return string|null One of the REASON_* constants.
     */
    public function roomBlockReason(Room $room): ?string
    {
        $owner = $room->owner_admin_id !== null ? $room->owner()->first() : null;
        if ($owner === null) {
            return null;
        }
        if (! $owner->isActive()) {
            return self::REASON_OWNER_INACTIVE;
        }

        $subscriptions = AdminSubscription::query()->where('admin_id', $owner->id);
        if ((clone $subscriptions)->exists() && ! (clone $subscriptions)->active()->exists()) {
            return self::REASON_NO_SUBSCRIPTION;
        }

        return null;
    }
}
