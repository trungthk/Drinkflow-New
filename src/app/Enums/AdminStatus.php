<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of an Admin (Agent) account.
 *
 * A registration starts as `pending` and becomes `active` or `rejected` once reviewed. An active
 * Admin can be `suspended` (temporarily) and reactivated, or `cancelled` (the account is closed).
 * Only `active` Admins can sign in.
 */
enum AdminStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * Whether an Admin in this status may sign in and use the admin area.
     *
     * @return bool True only for active accounts.
     */
    public function canSignIn(): bool
    {
        return $this === self::Active;
    }

    /**
     * Statuses a Superadmin may set directly on an existing account (review decisions use their own flow).
     *
     * @return array<int, string> Status values.
     */
    public static function manageableValues(): array
    {
        return [self::Active->value, self::Suspended->value, self::Cancelled->value];
    }
}
