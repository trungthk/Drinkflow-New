<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status of a platform Superadmin account.
 */
enum SuperadminStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
