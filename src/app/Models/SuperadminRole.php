<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PermissionScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Named set of Superadmin permissions with their scope (a template applied to Superadmins).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 */
class SuperadminRole extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    /**
     * Permissions of the role, with the scope on the pivot.
     *
     * @return BelongsToMany<PermissionRecord, $this> Permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionRecord::class, 'superadmin_role_permissions', 'superadmin_role_id', 'permission_id')
            ->withPivot('scope')
            ->withTimestamps();
    }

    /**
     * Superadmins whose permissions were last set from this role.
     *
     * @return HasMany<Superadmin, $this> Superadmins.
     */
    public function superadmins(): HasMany
    {
        return $this->hasMany(Superadmin::class, 'superadmin_role_id');
    }

    /**
     * Permission key => scope value of the role.
     *
     * @return array<string, string> Grants sorted by key.
     */
    public function grants(): array
    {
        $grants = $this->permissions()->get()
            ->mapWithKeys(static fn (PermissionRecord $record): array => [$record->key => (string) ($record->pivot->scope instanceof PermissionScope ? $record->pivot->scope->value : $record->pivot->scope)])
            ->all();
        ksort($grants);

        return $grants;
    }
}
