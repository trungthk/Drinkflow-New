<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Row of the `permissions` table (named PermissionRecord so it does not clash with the
 * App\Enums\Permission catalog it mirrors).
 *
 * @property string $key
 * @property string $group
 */
class PermissionRecord extends Model
{
    protected $table = 'permissions';

    protected $fillable = ['key', 'group'];

    /**
     * Catalog entry of this row, or null for a key that no longer exists in the enum.
     *
     * @return Permission|null Enum case.
     */
    public function permission(): ?Permission
    {
        return Permission::tryFrom($this->key);
    }

    /**
     * Superadmins holding this permission, with the granted scope.
     *
     * @return BelongsToMany<Superadmin, $this> Holders.
     */
    public function superadmins(): BelongsToMany
    {
        return $this->belongsToMany(Superadmin::class, 'superadmin_permissions', 'permission_id', 'superadmin_id')
            ->using(SuperadminPermission::class)
            ->withPivot(['scope', 'granted_by_superadmin_id'])
            ->withTimestamps();
    }
}
