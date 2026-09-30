<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Room lifecycle. `inactive` is a disabled room that still exists; `archived` is retired.
 */
enum RoomStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    /**
     * Statuses that use a slot of the owner's room quota (active and disabled rooms; archived ones do not).
     *
     * @return array<int, string> Status values.
     */
    public static function quotaValues(): array
    {
        return [self::Active->value, self::Inactive->value];
    }

    /**
     * Whether a room in this status uses a quota slot.
     *
     * @return bool True for active and disabled rooms.
     */
    public function countsTowardQuota(): bool
    {
        return in_array($this->value, self::quotaValues(), true);
    }
}
