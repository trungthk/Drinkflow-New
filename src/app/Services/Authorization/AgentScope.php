<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Models\Superadmin;
use Illuminate\Database\Eloquent\Builder;

/**
 * Visibility of Agent (Admin) data for a Superadmin: permission first, then scope.
 *
 * `all` sees every Agent, `managed` only the Agents assigned to the Superadmin, and no permission
 * sees nothing. Every list, detail check and aggregate over Agent data must go through here so a
 * scoped Superadmin never reads another Agent's data, including through totals.
 */
class AgentScope
{
    /**
     * Agent IDs visible for the permission.
     *
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised.
     * @return array<int, int>|null Null for every Agent (`all`), otherwise the allowed IDs (possibly none).
     */
    public function adminIds(Superadmin $superadmin, Permission $permission): ?array
    {
        return match ($superadmin->scopeFor($permission)) {
            PermissionScope::All => null,
            PermissionScope::Managed => $superadmin->managedAdminIds(),
            null => [],
        };
    }

    /**
     * Restrict a query to the Agents visible for the permission.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query Query over Agent-owned rows.
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised.
     * @param string $adminColumn Column holding the Agent ID (qualified when needed).
     * @return Builder<TModel> Constrained query.
     */
    public function apply(Builder $query, Superadmin $superadmin, Permission $permission, string $adminColumn): Builder
    {
        $ids = $this->adminIds($superadmin, $permission);

        return $ids === null ? $query : $query->whereIn($adminColumn, $ids);
    }

    /**
     * Restrict a room query to rooms owned by Agents visible for the permission.
     *
     * @param Builder<\App\Models\Room> $query Room query.
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised.
     * @return Builder<\App\Models\Room> Constrained query.
     */
    public function applyToRooms(Builder $query, Superadmin $superadmin, Permission $permission): Builder
    {
        $ids = $this->adminIds($superadmin, $permission);

        if ($ids === null) {
            return $query;
        }

        // Ownership decides visibility; rooms not mapped to an owner yet fall back to their assigned Admins.
        return $query->where(static fn (Builder $rooms) => $rooms
            ->whereIn($rooms->qualifyColumn('owner_admin_id'), $ids)
            ->orWhere(static fn (Builder $legacy) => $legacy
                ->whereNull($legacy->qualifyColumn('owner_admin_id'))
                ->whereHas('admins', static fn (Builder $admins) => $admins->whereIn('admins.id', $ids))));
    }

    /**
     * Restrict a query over room-owned rows (campaigns…) to rooms visible for the permission.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query Query with a `room` relation.
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised.
     * @return Builder<TModel> Constrained query.
     */
    public function applyToRoomOwned(Builder $query, Superadmin $superadmin, Permission $permission): Builder
    {
        return $this->adminIds($superadmin, $permission) === null
            ? $query
            : $query->whereHas('room', fn (Builder $rooms) => $this->applyToRooms($rooms, $superadmin, $permission));
    }

    /**
     * Whether every given Agent is visible for the permission.
     *
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised.
     * @param array<int, int|string> $adminIds Agent IDs.
     * @return bool True when all are visible.
     */
    public function allowsAll(Superadmin $superadmin, Permission $permission, array $adminIds): bool
    {
        $ids = $this->adminIds($superadmin, $permission);

        return $ids === null || array_diff(array_map('intval', $adminIds), $ids) === [];
    }

    /**
     * Whether one Agent is visible for the permission.
     *
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised.
     * @param int $adminId Agent ID.
     * @return bool True when visible.
     */
    public function allows(Superadmin $superadmin, Permission $permission, int $adminId): bool
    {
        $ids = $this->adminIds($superadmin, $permission);

        return $ids === null || in_array($adminId, $ids, true);
    }
}
