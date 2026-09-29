<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PermissionScope;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Permission granted to a Superadmin, with its visibility scope (`all` or `managed`).
 *
 * @property int $superadmin_id
 * @property int $permission_id
 * @property PermissionScope $scope
 * @property int|null $granted_by_superadmin_id
 */
class SuperadminPermission extends Pivot
{
    protected $table = 'superadmin_permissions';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'scope' => PermissionScope::class,
        ];
    }
}
