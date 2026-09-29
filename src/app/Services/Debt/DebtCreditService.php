<?php

declare(strict_types=1);

namespace App\Services\Debt;

use App\Enums\DebtStatus;
use App\Models\Room;
use App\Models\RoomUser;

/**
 * Personal debt ceiling ("credit limit") rules shared by ordering and the debt pages.
 *
 * The credit in use is the real outstanding balance of the member's campaign debts, including debts
 * waiting in a payment request: a pending request neither pays a debt nor frees credit.
 */
class DebtCreditService
{
    /** Ceiling applied when the room has not configured `personal_debt_ceiling`. */
    public const DEFAULT_CEILING = 150000;

    /**
     * Read the room's debt ceiling policy.
     *
     * @param Room $room Room whose settings apply.
     * @return array{enabled: bool, ceiling: int} Whether ordering is blocked at the ceiling, and the ceiling in VND.
     */
    public function policy(Room $room): array
    {
        $settings = $room->roomSettings()
            ->whereIn('key', ['personal_debt_ceiling', 'auto_lock_on_debt_limit'])
            ->get()
            ->keyBy('key');

        return [
            'enabled' => filter_var($settings->get('auto_lock_on_debt_limit')?->value ?? true, FILTER_VALIDATE_BOOLEAN),
            'ceiling' => (int) ($settings->get('personal_debt_ceiling')?->value ?? self::DEFAULT_CEILING),
        ];
    }

    /**
     * Sum the member's outstanding campaign debt balances, pending ones included.
     *
     * @param RoomUser $roomUser Room member.
     * @return int Credit in use, in VND.
     */
    public function used(RoomUser $roomUser): int
    {
        return (int) $roomUser->debts()
            ->whereIn('status', DebtStatus::outstandingValues())
            ->sum('remaining_amount');
    }

    /**
     * Compute the credit the member can still use before hitting the ceiling.
     *
     * @param Room $room Room whose settings apply.
     * @param RoomUser $roomUser Room member.
     * @return int|null Remaining credit in VND, or null when the room does not enforce a ceiling.
     */
    public function available(Room $room, RoomUser $roomUser): ?int
    {
        $policy = $this->policy($room);
        if (! $policy['enabled']) {
            return null;
        }

        return max(0, $policy['ceiling'] - $this->used($roomUser));
    }
}
