<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Visibility scope of a permission granted to a Superadmin.
 *
 * `all` covers every Agent on the platform; `managed` only the Agents assigned to that Superadmin.
 */
enum PermissionScope: string
{
    case All = 'all';
    case Managed = 'managed';
}
