<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Agent (Admin) assigned to a Superadmin; drives the `managed` permission scope.
 *
 * @property int $superadmin_id
 * @property int $admin_id
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property int|null $assigned_by_superadmin_id
 */
class SuperadminAdmin extends Pivot
{
    protected $table = 'superadmin_admins';

    public $incrementing = true;

    /** Attributes carried on the pivot of both relations. */
    public const PIVOT_COLUMNS = ['is_primary', 'assigned_at', 'assigned_by_superadmin_id'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }
}
