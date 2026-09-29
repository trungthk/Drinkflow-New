<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Enums\SuperadminStatus;
use App\Models\PermissionRecord;
use App\Models\Superadmin;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Create, update, suspend, delete Superadmins and set their permissions.
 *
 * The platform must always keep one active Superadmin able to manage Superadmins
 * (`superadmin.manage`), and nobody can suspend or delete their own account.
 */
class ManageSuperadminAction
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Create a Superadmin without any permission (least privilege until granted).
     *
     * @param array{name: string, email: string, password: string, status?: string} $data Validated data.
     * @return Superadmin Created account.
     */
    public function create(array $data): Superadmin
    {
        return DB::transaction(function () use ($data): Superadmin {
            $superadmin = Superadmin::create($data + ['status' => SuperadminStatus::Active->value]);
            $this->audit->record('superadmin.created', 'superadmin', $superadmin->id, null, [], $superadmin->only(['name', 'email', 'status']));

            return $superadmin;
        });
    }

    /**
     * Update profile, password and status.
     *
     * @param Superadmin $superadmin Account to update.
     * @param Superadmin $actor Superadmin performing the change.
     * @param array{name?: string, email?: string, password?: string|null, status?: string} $data Validated data.
     * @return Superadmin Updated account.
     * @throws ValidationException When the change would suspend the actor or the last manager.
     */
    public function update(Superadmin $superadmin, Superadmin $actor, array $data): Superadmin
    {
        return DB::transaction(function () use ($superadmin, $actor, $data): Superadmin {
            $superadmin = Superadmin::query()->lockForUpdate()->findOrFail($superadmin->id);
            if (($data['password'] ?? null) === null) {
                unset($data['password']);
            }
            $suspending = isset($data['status']) && $data['status'] !== SuperadminStatus::Active->value && $superadmin->status === SuperadminStatus::Active;
            if ($suspending) {
                $this->guardSelf($superadmin, $actor);
                $this->guardLastManager($superadmin);
            }

            $before = $superadmin->only(['name', 'email', 'status']);
            $superadmin->update($data);
            $this->audit->record('superadmin.updated', 'superadmin', $superadmin->id, null, $before, $superadmin->only(['name', 'email', 'status']), [
                'password_changed' => array_key_exists('password', $data),
            ]);

            return $superadmin;
        });
    }

    /**
     * Replace the permissions of a Superadmin.
     *
     * @param Superadmin $superadmin Account whose permissions change.
     * @param Superadmin $actor Superadmin granting the permissions.
     * @param array<string, string> $grants Permission key => scope value.
     * @return Superadmin Updated account.
     * @throws ValidationException When the last manager would lose `superadmin.manage`.
     */
    public function syncPermissions(Superadmin $superadmin, Superadmin $actor, array $grants): Superadmin
    {
        return DB::transaction(function () use ($superadmin, $actor, $grants): Superadmin {
            $superadmin = Superadmin::query()->lockForUpdate()->findOrFail($superadmin->id);
            $before = $this->grantMap($superadmin);
            if (isset($before[Permission::SuperadminManage->value]) && ! isset($grants[Permission::SuperadminManage->value])) {
                $this->guardLastManager($superadmin);
            }

            $records = PermissionRecord::query()->whereIn('key', array_keys($grants))->pluck('id', 'key');
            $existing = $superadmin->permissions()->get()->mapWithKeys(static fn (PermissionRecord $record): array => [$record->key => $record->pivot->granted_by_superadmin_id])->all();
            $superadmin->permissions()->sync($records->mapWithKeys(function (int $id, string $key) use ($grants, $existing, $before, $actor): array {
                $scope = Permission::from($key)->supportsScope() ? $grants[$key] : PermissionScope::All->value;
                // Keep the original grantor when an existing grant is unchanged.
                $grantor = ($before[$key] ?? null) === $scope ? ($existing[$key] ?? null) : $actor->id;

                return [$id => ['scope' => $scope, 'granted_by_superadmin_id' => $grantor]];
            })->all());
            $superadmin->flushPermissionCache();

            $after = $this->grantMap($superadmin);
            if ($after !== $before) {
                $this->audit->record('superadmin.permissions_updated', 'superadmin', $superadmin->id, null, ['permissions' => $before], ['permissions' => $after]);
            }

            return $superadmin;
        });
    }

    /**
     * Delete a Superadmin account.
     *
     * @param Superadmin $superadmin Account to delete.
     * @param Superadmin $actor Superadmin performing the deletion.
     * @return void
     * @throws ValidationException When deleting the actor or the last manager.
     */
    public function delete(Superadmin $superadmin, Superadmin $actor): void
    {
        DB::transaction(function () use ($superadmin, $actor): void {
            $superadmin = Superadmin::query()->lockForUpdate()->findOrFail($superadmin->id);
            $this->guardSelf($superadmin, $actor);
            $this->guardLastManager($superadmin);
            $this->audit->record('superadmin.deleted', 'superadmin', $superadmin->id, null, $superadmin->only(['name', 'email', 'status']) + ['permissions' => $this->grantMap($superadmin)]);
            $superadmin->delete();
        });
    }

    /**
     * Permission key => scope currently granted.
     *
     * @param Superadmin $superadmin Account.
     * @return array<string, string> Grants sorted by key.
     */
    private function grantMap(Superadmin $superadmin): array
    {
        $map = $superadmin->permissions()->get()
            ->mapWithKeys(static fn (PermissionRecord $record): array => [$record->key => $record->pivot->scope->value])
            ->all();
        ksort($map);

        return $map;
    }

    /**
     * Refuse suspending or deleting one's own account.
     *
     * @param Superadmin $superadmin Target account.
     * @param Superadmin $actor Acting superadmin.
     * @return void
     * @throws ValidationException When target and actor are the same account.
     */
    private function guardSelf(Superadmin $superadmin, Superadmin $actor): void
    {
        if ($superadmin->is($actor)) {
            throw ValidationException::withMessages(['superadmin' => __('superadmin.superadmins.cannot_change_self')]);
        }
    }

    /**
     * Refuse removing the last active Superadmin that can manage Superadmins.
     *
     * The other managers are locked so two concurrent requests cannot remove the last two.
     *
     * @param Superadmin $superadmin Account losing manager rights.
     * @return void
     * @throws ValidationException When no other active manager remains.
     */
    private function guardLastManager(Superadmin $superadmin): void
    {
        $isManager = $superadmin->status === SuperadminStatus::Active
            && $superadmin->permissions()->where('key', Permission::SuperadminManage->value)->exists();
        if (! $isManager) {
            return;
        }

        $others = Superadmin::query()
            ->whereKeyNot($superadmin->id)
            ->where('status', SuperadminStatus::Active->value)
            ->whereHas('permissions', static fn ($query) => $query->where('key', Permission::SuperadminManage->value))
            ->lockForUpdate()
            ->count();
        if ($others === 0) {
            throw ValidationException::withMessages(['superadmin' => __('superadmin.superadmins.last_manager')]);
        }
    }
}
