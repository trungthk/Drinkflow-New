<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Room;
use App\Models\Superadmin;
use App\Services\Authorization\AgentScope;

/**
 * Superadmin access to rooms from the console: permission plus the Agent scope of the room.
 *
 * With the `managed` scope a room is visible when its owning Agent is assigned to the Superadmin
 * (rooms not mapped to an owner yet fall back to their assigned Admins). Agents themselves get
 * `manageAsOwner` (owner only) and `operate` (owner or collaborator).
 */
class RoomPolicy
{
    public function __construct(private readonly AgentScope $scope) {}

    /**
     * List rooms (the list itself is filtered by the scope).
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @return bool True when allowed.
     */
    public function viewAny(Admin|Superadmin $user): bool
    {
        return $user instanceof Superadmin && $user->hasPermission(Permission::RoomView);
    }

    /**
     * See one room.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Room $room Room.
     * @return bool True when allowed.
     */
    public function view(Admin|Superadmin $user, Room $room): bool
    {
        return $user instanceof Superadmin && $this->visible($user, Permission::RoomView, $room);
    }

    /**
     * Create a room.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @return bool True when allowed.
     */
    public function create(Admin|Superadmin $user): bool
    {
        return $user instanceof Superadmin && $user->hasPermission(Permission::RoomManage);
    }

    /**
     * Update, change the status of, or force-close campaigns in a room.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Room $room Room.
     * @return bool True when allowed.
     */
    public function update(Admin|Superadmin $user, Room $room): bool
    {
        return $user instanceof Superadmin && $this->visible($user, Permission::RoomManage, $room);
    }

    /**
     * Delete a room.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Room $room Room.
     * @return bool True when allowed.
     */
    public function delete(Admin|Superadmin $user, Room $room): bool
    {
        return $this->update($user, $room);
    }

    /**
     * Agent managing the room itself (edit, archive, restore): only the owner, while active.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Room $room Room.
     * @return bool True for the active owning Agent.
     */
    public function manageAsOwner(Admin|Superadmin $user, Room $room): bool
    {
        return $user instanceof Admin && $user->isActive() && $room->isOwnedBy($user);
    }

    /**
     * Agent operating an active room (campaigns, orders, debts…): the owner or a collaborator.
     *
     * @param Admin|Superadmin $user Signed-in account.
     * @param Room $room Room.
     * @return bool True when the active Admin has operational access.
     */
    public function operate(Admin|Superadmin $user, Room $room): bool
    {
        return $user instanceof Admin
            && $user->isActive()
            && ($room->isOwnedBy($user) || $user->rooms()->whereKey($room->id)->exists());
    }

    /**
     * Whether the room belongs to an Agent visible for the permission.
     *
     * @param Superadmin $superadmin Acting superadmin.
     * @param Permission $permission Permission being exercised.
     * @param Room $room Room.
     * @return bool True when visible.
     */
    private function visible(Superadmin $superadmin, Permission $permission, Room $room): bool
    {
        $ids = $this->scope->adminIds($superadmin, $permission);

        if ($ids === null) {
            return true;
        }
        if ($room->owner_admin_id !== null) {
            return in_array((int) $room->owner_admin_id, $ids, true);
        }

        // Legacy room without owner yet (see `rooms:ownership`): fall back to its assigned Admins.
        return $ids !== [] && $room->admins()->whereIn('admins.id', $ids)->exists();
    }
}
