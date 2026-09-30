<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Models\PermissionRecord;
use App\Models\Superadmin;
use App\Models\SuperadminRole;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Create, update and delete Superadmin roles, and apply a role to a Superadmin.
 *
 * Applying a role replaces the Superadmin's grants through ManageSuperadminAction::syncPermissions(),
 * so the "last active manager" guard and the permission audit apply exactly as for manual edits.
 */
class ManageSuperadminRoleAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly ManageSuperadminAction $superadmins,
    ) {}

    /**
     * Create a role.
     *
     * @param array{code: string, name: string, description?: string|null} $data Validated data.
     * @param array<string, string> $grants Permission key => scope value.
     * @return SuperadminRole Created role.
     */
    public function create(array $data, array $grants): SuperadminRole
    {
        return DB::transaction(function () use ($data, $grants): SuperadminRole {
            $role = SuperadminRole::create($data);
            $this->syncGrants($role, $grants);
            $this->audit->record('superadmin_role.created', 'superadmin_role', $role->id, null, [], ['code' => $role->code, 'permissions' => $role->grants()]);

            return $role;
        });
    }

    /**
     * Update a role. Superadmins already given the role keep their grants until it is applied again.
     *
     * @param SuperadminRole $role Role.
     * @param array{code: string, name: string, description?: string|null} $data Validated data.
     * @param array<string, string> $grants Permission key => scope value.
     * @return SuperadminRole Updated role.
     */
    public function update(SuperadminRole $role, array $data, array $grants): SuperadminRole
    {
        return DB::transaction(function () use ($role, $data, $grants): SuperadminRole {
            $before = ['code' => $role->code, 'name' => $role->name, 'permissions' => $role->grants()];
            $role->update($data);
            $this->syncGrants($role, $grants);
            $after = ['code' => $role->code, 'name' => $role->name, 'permissions' => $role->grants()];
            if ($before !== $after) {
                $this->audit->record('superadmin_role.updated', 'superadmin_role', $role->id, null, $before, $after);
            }

            return $role;
        });
    }

    /**
     * Delete a role (Superadmins keep their current grants).
     *
     * @param SuperadminRole $role Role.
     * @return void
     */
    public function delete(SuperadminRole $role): void
    {
        DB::transaction(function () use ($role): void {
            $this->audit->record('superadmin_role.deleted', 'superadmin_role', $role->id, null, ['code' => $role->code, 'permissions' => $role->grants()]);
            $role->delete();
        });
    }

    /**
     * Replace a Superadmin's permissions with the role's grants.
     *
     * @param SuperadminRole $role Role to apply.
     * @param Superadmin $superadmin Target Superadmin.
     * @param Superadmin $actor Superadmin applying the role.
     * @return Superadmin Updated Superadmin.
     */
    public function applyTo(SuperadminRole $role, Superadmin $superadmin, Superadmin $actor): Superadmin
    {
        return DB::transaction(function () use ($role, $superadmin, $actor): Superadmin {
            $superadmin = $this->superadmins->syncPermissions($superadmin, $actor, $role->grants());
            $superadmin->forceFill(['superadmin_role_id' => $role->id])->save();
            $this->audit->record('superadmin_role.applied', 'superadmin', $superadmin->id, null, [], ['role' => $role->code]);

            return $superadmin;
        });
    }

    /**
     * Store the role's grants; permissions that cannot be scoped are always `all`.
     *
     * @param SuperadminRole $role Role.
     * @param array<string, string> $grants Permission key => scope value.
     * @return void
     */
    private function syncGrants(SuperadminRole $role, array $grants): void
    {
        $ids = PermissionRecord::query()->whereIn('key', array_keys($grants))->pluck('id', 'key');
        $role->permissions()->sync($ids->mapWithKeys(static fn (int $id, string $key): array => [
            $id => ['scope' => Permission::from($key)->supportsScope() ? $grants[$key] : PermissionScope::All->value],
        ])->all());
    }
}
