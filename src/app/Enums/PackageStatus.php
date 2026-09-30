<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Availability of a subscription package.
 *
 * `active` packages are offered on the public registration form; `inactive` ones are hidden from
 * new registrations but keep serving existing subscriptions; `archived` packages are retired
 * (a package that already has subscriptions is archived instead of deleted).
 */
enum PackageStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    /**
     * Whether new Agents may pick (or be moved to) a package in this status.
     *
     * @return bool True only for active packages.
     */
    public function isSelectable(): bool
    {
        return $this === self::Active;
    }
}
