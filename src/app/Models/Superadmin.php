<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Enums\SuperadminStatus;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Platform governance account, stored apart from Admins (Agents).
 *
 * Superadmins manage Agents, packages, subscriptions and platform billing; they never own rooms.
 * They sign in through the dedicated `superadmin` guard.
 */
class Superadmin extends Authenticatable
{
    use Notifiable, HasStatus;

    protected $table = 'superadmins';

    protected $fillable = ['name', 'email', 'password', 'status', 'last_login_at', 'avatar_url', 'phone', 'department', 'two_factor_enabled'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => SuperadminStatus::class,
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'phone' => 'encrypted',
        ];
    }

    /** @var array<string, PermissionScope>|null Granted permission keys mapped to their scope, loaded once. */
    private ?array $permissionScopes = null;

    /** @var array<int, int>|null IDs of the assigned Agents, loaded once. */
    private ?array $managedAdminIds = null;

    /**
     * Whether this Superadmin holds the permission (with any scope).
     *
     * @param Permission $permission Permission to check.
     * @return bool True when granted.
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->scopeFor($permission) !== null;
    }

    /**
     * Scope of a granted permission; permissions that cannot be scoped are always `all`.
     *
     * @param Permission $permission Permission to resolve.
     * @return PermissionScope|null Granted scope, or null when not granted.
     */
    public function scopeFor(Permission $permission): ?PermissionScope
    {
        $this->permissionScopes ??= $this->permissions()->get()
            ->mapWithKeys(static fn (PermissionRecord $record): array => [$record->key => $record->pivot->scope])
            ->all();
        $scope = $this->permissionScopes[$permission->value] ?? null;

        return $scope !== null && ! $permission->supportsScope() ? PermissionScope::All : $scope;
    }

    /**
     * IDs of the Agents assigned to this Superadmin (used by the `managed` scope).
     *
     * @return array<int, int> Agent IDs.
     */
    public function managedAdminIds(): array
    {
        return $this->managedAdminIds ??= $this->managedAdmins()->pluck('admins.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Forget the loaded permissions after grants change.
     *
     * @return void
     */
    public function flushPermissionCache(): void
    {
        $this->permissionScopes = null;
    }

    /**
     * Forget the loaded Agent assignments after they change.
     *
     * @return void
     */
    public function flushManagedAdminCache(): void
    {
        $this->managedAdminIds = null;
    }

    /**
     * Role whose permissions were last applied to this Superadmin (informational; grants live in superadmin_permissions).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<SuperadminRole, $this> Role.
     */
    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SuperadminRole::class, 'superadmin_role_id');
    }

    /**
     * Agents assigned to this Superadmin.
     *
     * @return BelongsToMany<Admin, $this> Assigned Agents with the assignment data on the pivot.
     */
    public function managedAdmins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'superadmin_admins', 'superadmin_id', 'admin_id')
            ->using(SuperadminAdmin::class)
            ->withPivot(SuperadminAdmin::PIVOT_COLUMNS)
            ->withTimestamps();
    }

    /**
     * Permissions granted to this Superadmin, each with its scope (`all` or `managed`).
     *
     * @return BelongsToMany<PermissionRecord, $this> Granted permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionRecord::class, 'superadmin_permissions', 'superadmin_id', 'permission_id')
            ->using(SuperadminPermission::class)
            ->withPivot(['scope', 'granted_by_superadmin_id'])
            ->withTimestamps();
    }
}
