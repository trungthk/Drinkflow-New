<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Models\PermissionRecord;
use App\Models\Superadmin;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the `permissions` table in line with the App\Enums\Permission catalog.
 */
class PermissionCatalogService
{
    /**
     * Insert catalog permissions missing from the database and optionally remove stale ones.
     *
     * Removing a stale permission also removes it from every Superadmin (cascade), so it only
     * happens when explicitly requested.
     *
     * @param bool $prune Delete rows whose key is no longer in the catalog.
     * @return array{added: array<int, string>, stale: array<int, string>, pruned: bool} Sync result.
     */
    public function sync(bool $prune = false): array
    {
        return DB::transaction(function () use ($prune): array {
            $existing = PermissionRecord::query()->pluck('key')->all();
            $added = array_values(array_diff(Permission::values(), $existing));
            foreach ($added as $key) {
                PermissionRecord::create(['key' => $key, 'group' => Permission::from($key)->group()]);
            }

            $stale = array_values(array_diff($existing, Permission::values()));
            if ($prune && $stale !== []) {
                PermissionRecord::query()->whereIn('key', $stale)->delete();
            }

            return ['added' => $added, 'stale' => $stale, 'pruned' => $prune && $stale !== []];
        });
    }

    /**
     * Give a Superadmin every catalog permission with the `all` scope (platform owner setup).
     *
     * Existing grants keep their scope; only missing permissions are added.
     *
     * @param Superadmin $superadmin Account to grant.
     * @param Superadmin|null $grantedBy Superadmin making the grant, null for system setup.
     * @return int Number of permissions added.
     */
    public function grantAll(Superadmin $superadmin, ?Superadmin $grantedBy = null): int
    {
        $this->sync();
        $missing = PermissionRecord::query()
            ->whereIn('key', Permission::values())
            ->whereNotIn('id', $superadmin->permissions()->select('permissions.id'))
            ->pluck('id');

        $superadmin->permissions()->attach($missing->mapWithKeys(static fn (int $id): array => [$id => [
            'scope' => PermissionScope::All->value,
            'granted_by_superadmin_id' => $grantedBy?->id,
        ]])->all());

        return $missing->count();
    }
}
